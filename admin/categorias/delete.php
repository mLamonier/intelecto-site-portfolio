<?php
require_once '../includes/auth_admin.php';
require_once __DIR__ . '/../includes/config.php';

if (!isset($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$id = (int)$_GET['id'];

$apiUrl = rtrim(site_base_url(), '/') . "/api/categorias/{$id}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    header('Location: list.php?msg=excluido');
} else {
    $erro = json_decode($response, true);
    $mensagem = $erro['mensagem'] ?? $erro['erro'] ?? 'Erro ao excluir';
    header('Location: list.php?erro=' . urlencode($mensagem));
}
exit;
