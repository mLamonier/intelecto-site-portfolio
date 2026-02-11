<?php

require_once '../includes/auth_admin.php';
require_once '../includes/config.php';

if (!isset($_GET['id'])) {
    header('Location: list.php?erro=' . urlencode('ID não fornecido'));
    exit;
}

$id = (int)$_GET['id'];
$url = $apiBase . '/usuarios/' . $id;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    header('Location: list.php?erro=' . urlencode('Erro de conexão com a API: ' . $curlError));
    exit;
}

$data = json_decode($response, true);

if ($httpCode === 200) {
    $mensagem = isset($data['mensagem']) ? $data['mensagem'] : 'Usuário excluído com sucesso';
    header('Location: list.php?sucesso=' . urlencode($mensagem));
    exit;
}

if ($httpCode === 400) {
    
    $mensagemErro = isset($data['mensagem']) ? $data['mensagem'] : 'Não é possível excluir este usuário';
    header('Location: list.php?erro=' . urlencode($mensagemErro));
    exit;
}

if ($httpCode === 404) {
    header('Location: list.php?erro=' . urlencode('Usuário não encontrado'));
    exit;
}

$mensagemErro = isset($data['mensagem']) ? $data['mensagem'] : 'Erro ao excluir usuário';
header('Location: list.php?erro=' . urlencode($mensagemErro));
exit;
