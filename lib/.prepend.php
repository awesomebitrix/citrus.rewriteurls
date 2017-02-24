<?php
/*******************************************************************************
 * citrus.rewriteurls - SEO rewrite and replace urls
 * Copyright 2017 Semenov Roman
 * MIT License
 ******************************************************************************/

namespace Citrus\Rewriteurls;

require __DIR__ . "/.init.php";

// init rewrite urls
if (is_readable(FILE_REWRITE_URLS)) {
	ctx::$rewriteUrls = include FILE_REWRITE_URLS;
}
if (is_readable(FILE_REWRITE_URLS_PARTS)) {
	ctx::$rewriteUrlsParts = include FILE_REWRITE_URLS_PARTS;
}
if (empty(ctx::$rewriteUrls) && empty(ctx::$rewriteUrlsParts)) {
	return;
}
// rewrite url
$res = Route();
if (!empty($res) && $res[1] === false) { // is virtual page
	$_SERVER["REQUEST_URI"] = $_SERVER["REDIRECT_URL"] = $res[0];
	$_SERVER["PHP_SELF"] = $_SERVER["SCRIPT_NAME"] = "/bitrix/urlrewrite.php";
	$_SERVER["SCRIPT_FILENAME"] = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/urlrewrite.php";
	$_SERVER["REDIRECT_STATUS"] = "200";
	require $_SERVER["SCRIPT_FILENAME"];
	exit;
}
