<?php

require_once __DIR__ . '/../../includes/site.php';

$apiBase = rtrim(site_base_url(), '/') . '/api/index.php?route=';

define('ADMIN_BASE', site_base_path() . '/admin');
define('SITE_NAME', 'Intelecto Profissionalizantes');

function site_base(): string {
	return site_base_path();
}

function asset_url(string $path): string {
	$path = ltrim($path, '/');

	if ($path === '') {
		return site_path('');
	}

	if (function_exists('site_asset_path') && strpos($path, 'assets/') === 0) {
		return site_asset_path($path);
	}

	return site_path($path);
}
