<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/site.php';

function isAdminSession(): bool
{
    if (!empty($_SESSION['admin_logado'])) {
        return true;
    }
    $roles = $_SESSION['usuario_roles'] ?? [];
    return is_array($roles) && in_array('ADMIN', $roles, true);
}

$isAdmin = isAdminSession();
if (!$isAdmin) {
    header('Location: ' . site_path('login.php'));
    exit;
}
