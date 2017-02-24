<?php
/*******************************************************************************
 * citrus.rewriteurls - SEO rewrite and replace urls
 * Copyright 2017 Semenov Roman
 * MIT License
 ******************************************************************************/

namespace Citrus\Rewriteurls;

require __DIR__ . "/.init.php";

$res = RewriteUrl();
if (!empty($res)) {
	$_SERVER["REQUEST_URI"] = $_SERVER["REDIRECT_URL"] = $res;
	$_SERVER["PHP_SELF"] = $_SERVER["SCRIPT_NAME"] = "/bitrix/urlrewrite.php";
	$_SERVER["SCRIPT_FILENAME"] = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/urlrewrite.php";
	$_SERVER["REDIRECT_STATUS"] = "200";
	require $_SERVER["SCRIPT_FILENAME"];
	exit;
}
