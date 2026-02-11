<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/pagbank.php';

echo json_encode([
    'success' => true,
    'public_key' => getenv('PAGBANK_PUBLIC_KEY') ?: '',
    'sandbox' => getenv('PAGBANK_SANDBOX') === 'true'
]);
