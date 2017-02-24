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
			//$options = Options();
			//if ($options["apply_with_panel"] != "Y"
			//		|| !$APPLICATION->showPanelWasInvoked) { // ignore for admin panel
			//	return;
			//}
			//$path = $e->getParameter("path");
			list($uri, $isStaticPage) = Route();
			if ($isStaticPage) {
				var_dump("static", $uri);
				return new EventResult(EventResult::SUCCESS, $uri);
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
