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

if (!defined(__NAMESPACE__ . "\ID")) {
	require __DIR__ . "/lib/.init.php";
}

function init() {
	if (($_SERVER["REQUEST_METHOD"] != "GET" && $_SERVER["REQUEST_METHOD"] != "HEAD")
			|| \CSite::InDir("/bitrix/")) { // ignore non GET and HEAD requests and admin pages
		return;
	}
	$options = Options();
	// rewrite urls
	if ($options["rewrite_urls"] == "Y") {
		EventManager::getInstance()->addEventHandler("main", "OnFileRewrite", function (Event $e) {
			global $APPLICATION;
			$options = Options();
			if ($options["apply_with_panel"] != "Y"
					|| !$APPLICATION->showPanelWasInvoked) { // ignore for admin panel
				return;
			}
			$path = $e->getParameter("path");
			if (!is_readable(FILE_REWRITE_URLS)) {
				return;
			}
			$rewriteUrls = include FILE_REWRITE_URLS;
			if (empty($rewriteUrls)) {
				return;
			}
			if ($options["ignore_query"] == "Y") {
				$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
			} else {
				$uri = $_SERVER["REQUEST_URI"];
			}
			if (isset($rewriteUrls[$uri])) {
				global $CITRUS_REWRITEURLS;
				$newUri = $rewriteUrls[$uri];
				// static page
				if ((substr($newUri, -4) == ".php"
							|| substr($newUri, -4) == ".htm"
							|| substr($newUri, -5) == ".html")
						&& file_exists($_SERVER["DOCUMENT_ROOT"] . $newUri)) {
					$CITRUS_REWRITEURLS["current_path"] = $newUri;
					var_dump($CITRUS_REWRITEURLS);
					return new EventResult(EventResult::SUCCESS, $newUri);
				}
				if (file_exists($_SERVER["DOCUMENT_ROOT"] . $newUri . "/index.php")) {
					$CITRUS_REWRITEURLS["current_path"] = $newUri;
					var_dump($CITRUS_REWRITEURLS);
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
			global $APPLICATION;
			$options = Options();
			if ($options["apply_with_panel"] != "Y"
					|| !$APPLICATION->showPanelWasInvoked) { // ignore for admin panel
				return;
			}
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
