<?php

require_once __DIR__ . '/includes/auth_admin.php';

if (!isset($_SERVER['REQUEST_METHOD']) || strtoupper($_SERVER['REQUEST_METHOD']) !== 'GET') {
    header('Location: ' . site_path('admin/index.php'), true, 303);
    exit;
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
require_once __DIR__ . '/includes/config.php';

function getStats($apiBase, $dataInicio, $dataFim)
{
    $stats = [
        'total_usuarios' => 0,
        'total_pedidos_pendentes' => 0,
        'total_pedidos_pagos' => 0,
        'total_pedidos_cancelados' => 0
    ];

    $sessionCookie = session_name() . '=' . session_id();
    $filtroData = '&data_inicio=' . urlencode($dataInicio) . '&data_fim=' . urlencode($dataFim);

    $parseTotal = function ($response) {
        if (!$response) {
            return 0;
        }
        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return 0;
        }
        if (isset($decoded['erro'])) {
            return 0;
        }
        if (isset($decoded['total'])) {
            return (int) $decoded['total'];
        }
        if (isset($decoded['data']) && is_array($decoded['data'])) {
            return count($decoded['data']);
        }
        return count($decoded);
    };

    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiBase . '/usuarios&exclude_admin=1&per_page=1&page=1' . $filtroData);
    curl_setopt($ch, CURLOPT_COOKIE, $sessionCookie);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $stats['total_usuarios'] = $parseTotal($response);

    $fetchPedidosPorStatus = function (string $status) use ($apiBase, $sessionCookie, $filtroData) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiBase . '/pedidos&status=' . urlencode($status) . '&per_page=1&page=1' . $filtroData);
        curl_setopt($ch, CURLOPT_COOKIE, $sessionCookie);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            return 0;
        }

        $pedidos = json_decode($response, true);
        if (!is_array($pedidos) || !isset($pedidos['total'])) {
            return 0;
        }

        return (int) $pedidos['total'];
    };

    $stats['total_pedidos_pendentes'] = $fetchPedidosPorStatus('PENDENTE');
    $stats['total_pedidos_pagos'] = $fetchPedidosPorStatus('PAGO');
    $stats['total_pedidos_cancelados'] = $fetchPedidosPorStatus('CANCELADO');

    return $stats;
}

function parseDateFromQuery($value)
{
    if (!is_string($value) || trim($value) === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        return null;
    }

    return $date;
}

$hoje = new DateTimeImmutable('today');
$defaultDataFim = $hoje;
$defaultDataInicio = $hoje->sub(new DateInterval('P29D'));

$dataInicio = parseDateFromQuery($_GET['data_inicio'] ?? '') ?? $defaultDataInicio;
$dataFim = parseDateFromQuery($_GET['data_fim'] ?? '') ?? $defaultDataFim;

if ($dataInicio > $dataFim) {
    [$dataInicio, $dataFim] = [$dataFim, $dataInicio];
}

$dataInicioStr = $dataInicio->format('Y-m-d');
$dataFimStr = $dataFim->format('Y-m-d');

$stats = getStats($apiBase, $dataInicioStr, $dataFimStr);
$nomeAdmin = $_SESSION['admin_nome'] ?? 'Administrador';
$host = $_SERVER['HTTP_HOST'] ?? '';
$siteUrl = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false)
    ? 'http://localhost:5173'
    : site_base() . '/';

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Intelecto</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url("<?= htmlspecialchars(asset_url('assets/background branco.png')) ?>");
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        
        .header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: white;
            font-size: 32px;
        }

        .header p {
            color: rgba(255, 255, 255, 0.9);
            margin-top: 5px;
        }

        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn-header {
            background: #292929;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .btn-header:hover {
            background: #3b4557;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 85, 104, 0.3);
        }

        .btn-logout {
            background: #dc2626;
        }

        .btn-logout:hover {
            background: #991b1b;
        }

        .btn-primary {
            background: #25bd31;
            color: white;
        }

        .btn-primary:hover {
            background: #189723;
        }

        .btn-password {
            background: #292929;
            color: white;
        }

        .btn-password:hover {
            background: #3b4557;
        }

        .date-filter {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            padding: 18px 20px;
            margin-bottom: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: end;
        }

        .date-filter .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 170px;
        }

        .date-filter label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #666;
            font-weight: 600;
        }

        .date-filter input[type="date"] {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            color: #111827;
            background: #fff;
        }

        .date-filter .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .date-filter .btn {
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .date-filter .btn-apply {
            background: #111827;
            color: #fff;
        }

        .date-filter .btn-default {
            background: #f3f4f6;
            color: #111827;
        }

        .date-filter .hint {
            margin-left: auto;
            color: #6b7280;
            font-size: 13px;
        }

        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .stat-card .icon {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .stat-card .label {
            color: #999;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .stat-card .value {
            color: #333;
            font-size: 42px;
            font-weight: bold;
        }

        .stat-card.usuarios {
            border-left: 5px solid #2196F3;
        }

        .stat-card.pedidos {
            border-left: 5px solid #9C27B0;
        }

        .stat-card.pedidos-pagos {
            border-left: 5px solid #22c55e;
        }

        .stat-card.pedidos-cancelados {
            border-left: 5px solid #ef4444;
        }

        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .menu-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .menu-card .icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .menu-card h3 {
            font-size: 22px;
            margin-bottom: 10px;
            color: #333;
        }

        .menu-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .menu-card.usuarios {
            border-top: 5px solid #2196F3;
        }

        .menu-card.cursos {
            border-top: 5px solid #4CAF50;
        }

        .menu-card.grades {
            border-top: 5px solid #FF9800;
        }

        .menu-card.pedidos {
            border-top: 5px solid #9C27B0;
        }

        .menu-card.config {
            border-top: 5px solid #607D8B;
        }

        .menu-card.categorias {
            border-top: 5px solid #1f34f7ff;
        }

        .menu-card.homepage {
            border-top: 5px solid #e41f1f;
        }

        .menu-card.security {
            border-top: 5px solid #6b21a8;
        }

        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .header-actions {
                flex-direction: column;
                width: 100%;
            }

            .btn-header {
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .date-filter {
                align-items: stretch;
            }

            .date-filter .field {
                min-width: 100%;
            }

            .date-filter .actions {
                width: 100%;
            }

            .date-filter .btn {
                flex: 1;
                text-align: center;
            }

            .date-filter .hint {
                margin-left: 0;
                width: 100%;
                text-align: left;
            }

            .menu-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        
        <div class="header">
            <div>
                <h1>🎓 Dashboard Admin</h1>
                <p>Bem-vindo, <strong><?= htmlspecialchars($nomeAdmin) ?></strong></p>
            </div>
            <div class="header-actions">
                <a href="<?= htmlspecialchars($siteUrl) ?>" class="btn-header btn-primary">Voltar ao Site</a>
                <a href="forgot-password.php" class="btn-header btn-password">Recuperar Senha</a>
                <a href="logout.php" class="btn-header btn-logout">🚪 Sair</a>
            </div>
        </div>

        <form method="GET" action="index.php" class="date-filter">
            <div class="field">
                <label for="data_inicio">Data inicial</label>
                <input type="date" id="data_inicio" name="data_inicio" value="<?= htmlspecialchars($dataInicioStr) ?>">
            </div>
            <div class="field">
                <label for="data_fim">Data final</label>
                <input type="date" id="data_fim" name="data_fim" value="<?= htmlspecialchars($dataFimStr) ?>">
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-apply">Aplicar filtro</button>
                <a href="index.php" class="btn btn-default">Limpar filtro</a>
            </div>
            <div class="hint">Período ativo: <?= htmlspecialchars($dataInicioStr) ?> até <?= htmlspecialchars($dataFimStr) ?></div>
        </form>

        <div class="stats-grid">
            <div class="stat-card usuarios">
                <div class="icon">&#128101;</div>
                <div class="label">LEADS</div>
                <div class="value"><?= $stats['total_usuarios'] ?></div>
            </div>

            <div class="stat-card pedidos-pagos">
                <div class="icon">&#9989;</div>
                <div class="label">Pedidos pagos</div>
                <div class="value"><?= $stats['total_pedidos_pagos'] ?></div>
            </div>

            <div class="stat-card pedidos-cancelados">
                <div class="icon">&#10060;</div>
                <div class="label">Pedidos cancelados</div>
                <div class="value"><?= $stats['total_pedidos_cancelados'] ?></div>
            </div>

            <div class="stat-card pedidos">
                <div class="icon">&#128722;</div>
                <div class="label">Pedidos pendentes</div>
                <div class="value"><?= $stats['total_pedidos_pendentes'] ?></div>
            </div>
        </div>

        <div class="menu-grid">
            <a href="usuarios/list.php" class="menu-card usuarios">
                <div class="icon">👥</div>
                <h3>Usuários</h3>
                <p>Cadastre, edite e visualize todos os usuários do sistema</p>
            </a>

            <a href="categorias/list.php" class="menu-card categorias">
                <div class="icon">🏷️</div>
                <h3>Categorias</h3>
                <p>Gerenciar categorias de cursos e grades</p>
            </a>

            <a href="cursos/list.php" class="menu-card cursos">
                <div class="icon">📚</div>
                <h3>Cursos</h3>
                <p>Adicione novos cursos, edite conteúdos e configure aulas demo</p>
            </a>

            <a href="grades/list.php" class="menu-card grades">
                <div class="icon">📦</div>
                <h3>Grades</h3>
                <p>Monte pacotes de cursos com preços e modalidades</p>
            </a>

            <a href="homepage/index.php" class="menu-card homepage">
                <div class="icon">🏠</div>
                <h3>Página Inicial</h3>
                <p>Gerencie banners, cursos em destaque, depoimentos e FAQ do site</p>
            </a>

            <a href="pedidos/list.php" class="menu-card pedidos">
                <div class="icon">🛒</div>
                <h3>Pedidos</h3>
                <p>Acompanhe vendas, pagamentos e status dos pedidos</p>
            </a>

            <a href="config/list.php" class="menu-card config">
                <div class="icon">⚙️</div>
                <h3>Valores Padrão</h3>
                <p>Configure os valores padrão das grades cadastradas</p>
            </a>
        </div>
    </div>
</body>

</html>
