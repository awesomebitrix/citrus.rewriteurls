<?php
/*******************************************************************************
 * citrus.rewriteurls - SEO rewrite and replace urls
 * Copyright 2017 Semenov Roman
 * MIT License
 ******************************************************************************/

namespace Citrus\Rewriteurls;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\EventManager;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

define(__NAMESPACE__ . "\ID", "citrus.rewriteurls");

define(__NAMESPACE__ . "\SITE", substr($_SERVER["SERVER_NAME"], 0, 4) == "www."?
	substr($_SERVER["SERVER_NAME"], 4) : $_SERVER["SERVER_NAME"]);

define(__NAMESPACE__ . "\CONFIG",
	$_SERVER["DOCUMENT_ROOT"] . "/upload/." . ID . "." . SITE);

define(__NAMESPACE__ . "\FILE_OPTIONS", CONFIG . ".php");
define(__NAMESPACE__ . "\FILE_REWRITE_URLS", CONFIG . ".rewrite.php");
define(__NAMESPACE__ . "\FILE_REPLACE_URLS", CONFIG . ".replace.php");

class ctx {
	static $replaceUrls;
}

function Options() {
	$result = is_readable(FILE_OPTIONS)? include FILE_OPTIONS : array(
		"rewrite_urls" => "Y",
		"replace_urls" => "Y",
	);
	return $result;
}

function ReplaceUrls($m) {
	if (strpos($m[1], 'href=') === false) {
		return $m[0];
	}
	if (strpos($m[1], 'data-fixed=') !== false) {
		return $m[0];
	}
	if (strpos($m[1], 'href=""') !== false || strpos($m[1], "href=''") !== false) {
		return $m[0];
	}
	if (!preg_match('{href=[\'\"]+(.+?)[\'\"]+}i', $m[1], $mattrs)) {
		return $m[0];
	}
	// if is internal link
	if ((strpos($mattrs[1], 'http://') === false
				&& strpos($mattrs[1], 'https://') === false) ||
			strpos($mattrs[1], $_SERVER['SERVER_NAME']) !== false ||
			strpos($mattrs[1], 'www.' . $_SERVER['SERVER_NAME']) !== false) {
		$url = $mattrs[1];
		$u = parse_url($url);
		$newUrl = $url;
		// fix link - add / at end
		if ($u['path'] != '' && $u['path'] != '/' &&
				substr($u['path'], -1) != '/' &&
				($u['scheme'] == '' || $u['scheme'] == 'http' || $u['scheme'] == 'https')) {
			$p = pathinfo($u['path']);
			if (empty($p['extension'])) {
				$newUrl = $url . '/';
				$m[0] = str_replace($url, $newUrl, $m[0]);
				//echo '<!-- ' . $u['path'] . ' - ' . print_r($p, true) . ' -->' . PHP_EOL;
			}
		}
		// rewrite url if defined
		if (!empty(ctx::$replaceUrls)) {
			if (isset(ctx::$replaceUrls[$newUrl])) {
				$m[0] = str_replace($newUrl, ctx::$replaceUrls[$newUrl], $m[0]);
			}
		}
	}
	return $m[0];
}

function init() {
	global $APPLICATION;
	if ($APPLICATION->showPanelWasInvoked) { // ignore for admin panel
		return;
	}
	if (($_SERVER["REQUEST_METHOD"] != "GET" && $_SERVER["REQUEST_METHOD"] != "HEAD")
			|| \CSite::InDir("/bitrix/")) { // ignore non GET and HEAD requests and admin pages
		return;
	}
	$options = Options();

	// rewrite urls
	if ($options["rewrite_urls"] == "Y") {
		EventManager::getInstance()->addEventHandler("main", "OnFileRewrite", function (Event $e) {
			$path = $e->getParameter("path");
			if (!is_readable(FILE_REWRITE_URLS)) {
				return;
			}
			$rewriteUrls = include FILE_REWRITE_URLS;
			if (empty($rewriteUrls)) {
				return;
			}
			$uri = $_SERVER["REQUEST_URI"];

			// TODO ?remove params from $uri

			if (isset($rewriteUrls[$uri])) {
				$newUri = $rewriteUrls[$uri];
				// static page
				if ((substr($newUri, -4) == ".php"
							|| substr($newUri, -4) == ".htm"
							|| substr($newUri, -5) == ".html")
						&& file_exists($_SERVER["DOCUMENT_ROOT"] . $newUri)) {
					return new EventResult(EventResult::SUCCESS, $newUri);
				}
				if (file_exists($_SERVER["DOCUMENT_ROOT"] . $newUri . "/index.php")) {
					return new EventResult(EventResult::SUCCESS, $newUri . "/index.php");
				}
				/*
				// virtual page
				$_SERVER["REQUEST_URI"] = $_SERVER["REDIRECT_URL"] = $newUri;
				// not work
				require $_SERVER["SCRIPT_FILENAME"];
				exit;
				// not work
				return new EventResult(EventResult::SUCCESS, $newUri); // $newUri . "/index.php"
				*/
			}
		});
	}

	// replace urls
	if ($options["replace_urls"] == "Y") {
		EventManager::getInstance()->addEventHandler("main", "OnEndBufferContent", function (&$content) {
			if (!is_readable(FILE_REPLACE_URLS)) {
				return;
			}
			ctx::$replaceUrls = include FILE_REPLACE_URLS;
			if (empty(ctx::$replaceUrls)) {
				return;
			}
			ctx::$replaceUrls = array_flip(ctx::$replaceUrls);
			$content = preg_replace_callback(
				'{<a([^>]*)>}is',
				__NAMESPACE__ . "\ReplaceUrls",
				$content
			);
		});
	}
}

init();
