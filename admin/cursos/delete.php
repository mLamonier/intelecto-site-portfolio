<?php

require_once __DIR__ . '/includes/auth_admin.php';
require_once __DIR__ . '/includes/config.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: list.php?erro=ID não informado');
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiBase . 'cursos/' . $id);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    header('Location: list.php?sucesso=Curso excluído com sucesso');
} else {
    $erro = json_decode($response, true);
    $mensagem = $erro['erro'] ?? 'Erro ao excluir curso';
    header('Location: list.php?erro=' . urlencode($mensagem));
}
exit;
