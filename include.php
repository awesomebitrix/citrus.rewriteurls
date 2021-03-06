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
	//if (($_SERVER["REQUEST_METHOD"] != "GET" && $_SERVER["REQUEST_METHOD"] != "HEAD")) { // ignore non GET and HEAD requests
	//	return;
	//}
	if (\CSite::InDir("/bitrix/")) { // ignore  admin pages
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
				ctx::$fixBreadcrumbs = true;
				return new EventResult(EventResult::SUCCESS, $uri);
			}
		});
	}

	// replace urls
	if ($options["rewrite_urls"] == "Y" && $options["replace_urls"] == "Y") {
		EventManager::getInstance()->addEventHandler("main", "OnEndBufferContent", function (&$content) {
			global $APPLICATION;
			$options = Options();
			if ($options["apply_with_panel"] != "Y"
					|| !$APPLICATION->showPanelWasInvoked) { // ignore for admin panel
				return;
			}
			if (!empty(ctx::$rewriteUrls)) {
				ctx::$replaceUrls = array_flip(ctx::$rewriteUrls);
			}
			if (!empty(ctx::$rewriteUrlsParts)) {
				ctx::$replaceUrlsParts = array_flip(ctx::$rewriteUrlsParts);
			}
			if (empty(ctx::$replaceUrls) && empty(ctx::$replaceUrlsParts)) {
				return;
			}
			ctx::$ignoreQuery = $options["ignore_query"] == "Y";
			$content = preg_replace_callback(
				'{<a([^>]*)>}is',
				__NAMESPACE__ . "\ReplaceUrls",
				$content
			);
		});
		// FIX for breadcrumbs
		EventManager::getInstance()->addEventHandler("main", "OnEpilog", function () {
			if (\CSite::InDir("/bitrix/")) {
				return;
			}
			global $APPLICATION;
			if (!empty(ctx::$fixBreadcrumbs)) {
				$APPLICATION->AddChainItem($APPLICATION->GetTitle());
			}
			//if (!empty($CITRUS_CUSTOM["last_chain"])) {
			//	$APPLICATION->AddChainItem($CITRUS_CUSTOM["last_chain"]);
			//}
		});
	}
}

init();
