<?php
/*******************************************************************************
 * citrus.rewriteurls - SEO rewrite and replace urls
 * Copyright 2017 Semenov Roman
 * MIT License
 ******************************************************************************/

namespace Citrus\Rewriteurls;

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
		"ignore_query" => "Y",
		"apply_with_panel" => "Y",
	);
	return $result;
}

// TODO get path and type for page url
function RouteUrl() {
	//...
}

function RewriteUrl() {
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
	if ($options["ignore_query"] == "Y") {
		$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
	} else {
		$uri = $_SERVER["REQUEST_URI"];
	}
	if (!isset($rewriteUrls[$uri])) {
		return;
	}
	$newUri = $rewriteUrls[$uri];

	// use redirect
	//if (0) {
	//	header("HTTP/1.1 301 Moved Permanently");
	//	header("Location: " . $newUri);
	//	exit;
	//}

	// ignore static page
	if ((substr($newUri, -4) == ".php"
				|| substr($newUri, -4) == ".htm"
				|| substr($newUri, -5) == ".html")
			&& file_exists($_SERVER["DOCUMENT_ROOT"] . $newUri)) {
		return;
	}
	if (file_exists($_SERVER["DOCUMENT_ROOT"] . $newUri . "/index.php")) {
		return;
	}
	return $newUri;
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
