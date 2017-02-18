<?php
/*******************************************************************************
 * custom.rewriteurls - SEO rewrite and replace urls
 * Copyright 2017 Semenov Roman
 * MIT License
 ******************************************************************************/

namespace Custom\Rewriteurls;

defined("B_PROLOG_INCLUDED") and (B_PROLOG_INCLUDED === true) or die();

define(__NAMESPACE__ . "\ID", "custom.rewriteurls");

define(__NAMESPACE__ . "\SITE", substr($_SERVER["SERVER_NAME"], 0, 4) == "www."?
	substr($_SERVER["SERVER_NAME"], 4) : $_SERVER["SERVER_NAME"]);

define(__NAMESPACE__ . "\CONFIG",
	$_SERVER["DOCUMENT_ROOT"] . "/upload/." . ID . "." . SITE);

define(__NAMESPACE__ . "\FILE_REWRITE_URLS", CONFIG . ".php");
define(__NAMESPACE__ . "\FILE_REPLACE_URLS", CONFIG . ".replace.php");

use Bitrix\Main\EventManager;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class ctx {
	static $replaceUrls;
}

// rewrite urls
EventManager::getInstance()->addEventHandler("main", "OnFileRewrite", function (Event $e) {
	$path = $e->getParameter("path");
	$rewriteUrls = include FILE_REWRITE_URLS;

	// TODO remove params?

	if (isset($rewriteUrls[$_SERVER["REQUEST_URI"]])) {
		return new EventResult(EventResult::SUCCESS, $rewriteUrls[$_SERVER["REQUEST_URI"]]);
	}
});

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
		// fix link
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
		// rewrite url
		if (!empty(ctx::$replaceUrls)) {
			if (isset(ctx::$replaceUrls[$newUrl])) {
				$m[0] = str_replace($newUrl, ctx::$replaceUrls[$newUrl], $m[0]);
			}
		}
	}

	return $m[0];
}

// replace urls
EventManager::getInstance()->addEventHandler("main", "OnEndBufferContent",
	function (&$content) {
		global $APPLICATION;
		if ($APPLICATION->showPanelWasInvoked) { // ignore for admin panel
			return;
		}
		if (($_SERVER["REQUEST_METHOD"] != "GET" && $_SERVER["REQUEST_METHOD"] != "HEAD")
				|| \CSite::InDir("/bitrix/")) {
			return;
		}

		ctx::$replaceUrls = include FILE_REPLACE_URLS;
		if (empty(ctx::$replaceUrls)) {
			return;
		}
		ctx::$replaceUrls = array_flip(ctx::$replaceUrls);
		$content = preg_replace_callback(
			'{<a([^>]*)>}is',
			__NAMESPACE__ . '\ReplaceUrls',
			$content
		);
	}
);
