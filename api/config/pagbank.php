<?php

require_once __DIR__ . '/../../includes/site.php';

function pg_env_normalize_value(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return $value;
    }

    $first = $value[0];
    $last = $value[strlen($value) - 1];
    if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
        $value = substr($value, 1, -1);
    }

    return trim($value);
}

function pg_env_set_if_missing(string $key, string $value): void
{
    $current = getenv($key);
    if ($current !== false && trim((string)$current) !== '') {
        return;
    }

    putenv($key . '=' . $value);
    $_ENV[$key] = $value;
}

function pg_env_bool(string $key, bool $default = false): bool
{
    $raw = getenv($key);
    if ($raw === false) {
        return $default;
    }
    $parsed = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return $parsed ?? $default;
}

$envFile = function_exists('site_env_path') ? site_env_path() : (__DIR__ . '/../../.env');
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim((string)$line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue; 
        }

        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim((string)$key);
            $key = preg_replace('/^\xEF\xBB\xBF/', '', $key);
            $value = pg_env_normalize_value((string)$value);

            pg_env_set_if_missing($key, $value);
        }
    }
}

$apiUrl = trim((string)(getenv('PAGBANK_API_URL') ?: ''));
$sandbox = pg_env_bool('PAGBANK_SANDBOX', false);

if ($apiUrl === '') {
    $apiUrl = $sandbox
        ? 'https://sandbox.api.pagseguro.com/orders'
        : 'https://api.pagseguro.com/orders';
}

$PAGBANK_CONFIG = [
    'email'          => trim((string)(getenv('PAGBANK_EMAIL') ?: 'seu_email@pagbank.com.br')),
    'token'          => trim((string)(getenv('PAGBANK_TOKEN') ?: 'seu_token_aqui')),
    'api_url'        => $apiUrl,
    'sandbox'        => $sandbox,
];

return $PAGBANK_CONFIG;
