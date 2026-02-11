<?php

function site_is_localhost(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
    if ($host === '') {
        return false;
    }
    $host = strtolower($host);
    $host = preg_replace('/:\d+$/', '', $host);
    return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
}

function site_base_path(): string
{
    return site_is_localhost() ? '/intelecto-site' : '';
}

function site_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        ? 'https'
        : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . site_base_path();
}

function site_env_path(): string
{
    $root = __DIR__ . '/..';
    $local = $root . '/.env.local';
    $default = $root . '/.env';
    if (site_is_localhost() && file_exists($local)) {
        return $local;
    }
    return $default;
}

function site_env_content(): ?string
{
    $path = site_env_path();
    if (!file_exists($path)) {
        return null;
    }
    $content = file_get_contents($path);
    if ($content === false) {
        return null;
    }
    $content = str_replace("\r\n", "\n", $content);
    $content = str_replace("\r", "\n", $content);
    return $content;
}

function site_path(string $path = ''): string
{
    $base = site_base_path();
    $clean = ltrim($path, '/');
    if ($clean === '') {
        return $base === '' ? '/' : $base . '/';
    }
    return $base . '/' . $clean;
}

function site_asset_path(string $path = ''): string
{
    $clean = ltrim($path, '/');
    if ($clean === '') {
        return site_path('');
    }

    if (strpos($clean, 'assets/') === 0) {
        $root = __DIR__ . '/..';
        $localAsset = $root . '/frontend/public/' . $clean;
        if (file_exists($localAsset)) {
            return site_path('frontend/public/' . $clean);
        }
        return site_path($clean);
    }

    return site_path($clean);
}
