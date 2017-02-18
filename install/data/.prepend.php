<?php

namespace Citrus\RewriteurlsPrepend;

define(__NAMESPACE__ . "\ID", "citrus.rewriteurls");

define(__NAMESPACE__ . "\SITE", substr($_SERVER["SERVER_NAME"], 0, 4) == "www."?
	substr($_SERVER["SERVER_NAME"], 4) : $_SERVER["SERVER_NAME"]);

define(__NAMESPACE__ . "\CONFIG",
	$_SERVER["DOCUMENT_ROOT"] . "/upload/." . ID . "." . SITE);

define(__NAMESPACE__ . "\FILE_OPTIONS", CONFIG . ".php");
define(__NAMESPACE__ . "\FILE_REWRITE_URLS", CONFIG . ".rewrite.php");
define(__NAMESPACE__ . "\FILE_REPLACE_URLS", CONFIG . ".replace.php");

function Options() {
	$result = is_readable(FILE_OPTIONS)? include FILE_OPTIONS : array(
		"rewrite_urls" => "Y",
		"replace_urls" => "Y",
	);
	return $result;
}

function init() {
	if (($_SERVER["REQUEST_METHOD"] != "GET" && $_SERVER["REQUEST_METHOD"] != "HEAD")) { // ignore non GET and HEAD requests
		return;
	}
	$options = Options();
	if ($options["rewrite_urls"] != "Y") {
		return;
	}
	if (!is_readable(FILE_REWRITE_URLS)) {
		return;
	}
	$rewriteUrls = include FILE_REWRITE_URLS;
	if (empty($rewriteUrls)) {
		return;
	}
	$uri = $_SERVER["REQUEST_URI"];

	var_dump($uri);

	// TODO ?remove params from $uri

	if (isset($rewriteUrls[$uri])) {
		// FIX use redirect
		//if (0) {
		//	header("HTTP/1.1 301 Moved Permanently");
		//	header("Location: " . $CUSTOM_SEO_REWRITE_URLS[$_SERVER["REQUEST_URI"]]);
		//	exit;
		//}

		// FIX use rewrite
		$_SERVER["REQUEST_URI"] = $_SERVER["REDIRECT_URL"]
			= $rewriteUrls[$uri];
		$_SERVER["PHP_SELF"] = $_SERVER["SCRIPT_NAME"] = "/bitrix/urlrewrite.php";
		$_SERVER["SCRIPT_FILENAME"] = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/urlrewrite.php";
		$_SERVER["REDIRECT_STATUS"] = "200";
		require $_SERVER["SCRIPT_FILENAME"];
		exit;
	}
}

init();
