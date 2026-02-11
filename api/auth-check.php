<?php

header('Content-Type: application/json');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    header("Access-Control-Allow-Origin: {$origin}");
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

$logado = isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true;

if ($logado) {
    echo json_encode([
        'logado' => true,
        'usuario' => [
            'id' => $_SESSION['usuario_id'] ?? null,
            'nome' => $_SESSION['usuario_nome'] ?? null,
            'email' => $_SESSION['usuario_email'] ?? null,
            'roles' => $_SESSION['usuario_roles'] ?? []
        ]
    ]);
} else {
    echo json_encode([
        'logado' => false
    ]);
}
