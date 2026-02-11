<?php
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/config.php';

$apiRoot = $apiBase;

$apiRoot = preg_replace('#/index\.php(\?route=)?$#', '', $apiRoot);
$apiRoot = rtrim($apiRoot, '/');

function api_url(string $path): string
{
    global $apiBase;
    $path = ltrim($path, '/');

    
    if (strpos($apiBase, '?route=') !== false) {
        return $apiBase . $path;
    }

    
    return rtrim($apiBase, '/') . '/' . $path;
}

function api_get_json(string $path): array
{
    $url = api_url($path);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    $decoded = json_decode($response ?? '', true);

    if ($httpCode < 200 || $httpCode >= 300) {
        return [
            'ok' => false,
            'http' => $httpCode,
            'url' => $url,
            'raw' => $response,
            'curl_error' => $curlErr,
            'json' => $decoded
        ];
    }

    return ['ok' => true, 'data' => $decoded, 'url' => $url, 'http' => $httpCode];
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: list.php?erro=Grade não especificada');
    exit;
}

$gradeResp = api_get_json("grades/$id");
if (!$gradeResp['ok'] || !is_array($gradeResp['data']) || isset($gradeResp['data']['erro'])) {
    
    http_response_code(500);
    echo "<h2>Erro ao carregar grade</h2>";
    echo "<p><b>URL:</b> " . htmlspecialchars($gradeResp['url'] ?? '') . "</p>";
    echo "<p><b>HTTP:</b> " . htmlspecialchars((string)($gradeResp['http'] ?? '')) . "</p>";
    echo "<pre style='white-space: pre-wrap'>" . htmlspecialchars($gradeResp['raw'] ?? '') . "</pre>";
    exit;
}

$grade = $gradeResp['data'];
$gradeNome = $grade['nome'] ?? ('Grade #' . $id);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gerenciar Cursos da Grade - Intelecto</title>
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .header {
            background: #fff;
            padding: 22px 26px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .header h1 {
            font-size: 20px;
            color: #333;
        }

        .btn {
            padding: 10px 16px;
            border-radius: 8px;
            border: 0;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-block;
        }

        .btn-secondary {
            background: #607D8B;
            color: #fff;
        }

        .btn-primary {
            background: #4CAF50;
            color: #fff;
        }

        .btn-danger {
            background: #f44336;
            color: #fff;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
            padding: 20px;
            margin-bottom: 18px;
        }

        .row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: end;
        }

        label {
            display: block;
            font-size: 12px;
            color: #555;
            margin-bottom: 6px;
        }

        select,
        input {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #ddd;
            outline: none;
        }

        .col {
            flex: 1;
            min-width: 240px;
        }

        .col-small {
            width: 200px;
            min-width: 200px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border-bottom: 1px solid #eee;
            padding: 10px;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #fafafa;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .3px;
            color: #555;
        }

        .muted {
            color: #777;
            font-size: 13px;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-mini {
            padding: 8px 10px;
            font-size: 13px;
            border-radius: 8px;
        }

        .btn-edit {
            background: #2196F3;
            color: #fff;
        }

        .btn-warning {
            background: #FF9800;
            color: #fff;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            margin-top: 10px;
        }

        .alert-ok {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .alert-err {
            background: #ffebee;
            color: #c62828;
        }

        .right {
            text-align: right;
        }

        
        #tbody tr {
            cursor: grab;
            transition: background-color 0.2s ease;
        }

        #tbody tr:active {
            cursor: grabbing;
        }

        #tbody tr.sortable-ghost {
            opacity: 0.5;
            background-color: #f0f0f0;
        }

        #tbody tr.sortable-drag {
            opacity: 1;
            background-color: #e3f2fd;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .drag-hint {
            display: inline-block;
            width: 24px;
            height: 24px;
            cursor: grab;
            margin-right: 8px;
            opacity: 0.5;
            transition: opacity 0.2s, color 0.2s;
            user-select: none;
        }

        .drag-hint:active {
            cursor: grabbing;
        }

        tr:hover .drag-hint {
            opacity: 1;
            color: #667eea;
        }

        #tbody tr.sortable-fallback {
            opacity: 0.5;
        }

        .muted-sm {
            font-size: 11px;
            color: #999;
            margin-top: 4px;
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="header">
            <h1>📚 Gerenciar cursos da grade: <?= htmlspecialchars($gradeNome) ?></h1>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a class="btn btn-secondary" href="list.php">← Voltar</a>
                <a class="btn btn-primary" href="form.php?id=<?= (int)$id ?>">✏️ Editar grade</a>
            </div>
        </div>

        <div class="card">
            <div class="row">
                <div class="col">
                    <label>Adicionar curso</label>
                    <select id="cursoSelect"></select>
                </div>
                <div class="col-small">
                    <label>Horas personalizadas (opcional)</label>
                    <input id="horasCustom" type="number" min="0" step="1" placeholder="Ex: 20" />
                </div>
                <div class="col-small">
                    <button class="btn btn-primary" style="width:100%;" onclick="adicionarCurso()">➕ Adicionar</button>
                </div>
            </div>
            <div id="msg"></div>
        </div>

        <div class="card">
            <div class="row" style="justify-content: space-between; align-items: center;">
                <div>
                    <div class="muted">Cursos na grade (ordem, horas e remoção).</div>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <div class="right muted" id="resumo"></div>
                    <button class="btn btn-primary btn-mini" onclick="recalcularOrdem()" title="Recalcula a ordem sequencial (1, 2, 3...)">
                        💾 Salvar Ordem
                    </button>
                </div>
            </div>

            <div style="overflow:auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width:30px;"></th>
                            <th style="width:50px;">Ordem</th>
                            <th>Curso</th>
                            <th style="width:140px;">Horas</th>
                            <th style="width:230px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tbody">
                        <tr>
                            <td colspan="4" class="muted">Carregando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        const API_BASE = <?= json_encode($apiBase) ?>;
        const ID_GRADE = <?= (int)$id ?>;

        const API_ROOT = <?= json_encode($apiRoot) ?>;

        function apiUrl(path) {
            path = String(path || '').replace(/^\/+/, '');
            if (String(API_BASE).includes('?route=')) {
                return API_BASE + path;
            }
            return API_ROOT + '/' + path;
        }

        function showMsg(text, ok = true) {
            const el = document.getElementById('msg');
            if (!text) {
                el.innerHTML = '';
                return;
            }
            el.innerHTML = `<div class="alert ${ok ? 'alert-ok' : 'alert-err'}">${text}</div>`;
        }

        function getIdCurso(c) {
            return c.id_curso ?? c.idcurso ?? c.idCurso ?? null;
        }

        function getNomeCurso(c) {
            return c.nome ?? '(Sem nome)';
        }

        function getOrdem(c) {
            return Number(c.ordem ?? 0);
        }

        function getHoras(c) {
            // tenta vários nomes possíveis para ficar compatível
            return Number(
                c.horas_final ??
                c.horasfinal ??
                c.horas ??
                c.carga_horaria ??
                0
            );
        }

        function normalizeArrayResponse(data) {
            if (Array.isArray(data)) return data;
            if (data && Array.isArray(data.data)) return data.data;
            return null;
        }

        // Função para inicializar SortableJS
        let sortableInstance = null;
        
        function initSortable() {
            const tbody = document.getElementById('tbody');
            
            if (!tbody) {
                return;
            }
            
            // Verifica se há linhas draggable
            const rows = tbody.querySelectorAll('tr[data-curso-id]');
            if (rows.length === 0) {
                return;
            }
            
            
            // Destrói a instância anterior se existir
            if (sortableInstance) {
                sortableInstance.destroy();
                sortableInstance = null;
            }
            
            sortableInstance = Sortable.create(tbody, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                handle: '.drag-hint',
                forceFallback: false,
                delay: 0,
                delayOnTouchOnly: true,
                touchStartThreshold: 0,
                preventOnFilter: false,
                fallbackClass: 'sortable-fallback',
                onStart: function(evt) {
                },
                onEnd: async function(evt) {
                    // Quando drag termina, reordena no servidor
                    const rows = Array.from(tbody.querySelectorAll('tr[data-curso-id]'));
                    const ids = rows.map(row => Number(row.getAttribute('data-curso-id')));
                    
                    
                    if (ids.length === 0) {
                        return;
                    }
                    
                    const payload = {
                        cursos: ids
                    };
                    
                    try {
                        const res = await fetch(apiUrl(`grades/${ID_GRADE}/cursos/reordenar`), {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });
                        
                        const data = await res.json().catch(() => ({}));
                        
                        if (!res.ok) {
                            showMsg(`Erro ao reordenar: ${data.erro || data.mensagem || 'Erro desconhecido'}`, false);
                            // Recarrega para reverter a mudança visual
                            await carregarCursosDaGrade();
                            return;
                        }
                        
                        showMsg('Ordem atualizada com sucesso.');
                    } catch (error) {
                        showMsg('Erro ao reordenar: ' + error.message, false);
                        await carregarCursosDaGrade();
                    }
                }
            });
        }

        async function recalcularOrdem() {
            const tbody = document.getElementById('tbody');
            const rows = tbody.querySelectorAll('tr[data-curso-id]');
            
            if (rows.length === 0) {
                showMsg('Nenhum curso para recalcular.', false);
                return;
            }

            // Pega a ordem atual dos cursos (como estão na tela)
            const ids = Array.from(rows).map(row => Number(row.getAttribute('data-curso-id')));
            
            const payload = {
                cursos: ids
            };
            
            try {
                const res = await fetch(apiUrl(`grades/${ID_GRADE}/cursos/reordenar`), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await res.json().catch(() => ({}));
                
                if (!res.ok) {
                    showMsg(`Erro ao recalcular ordem: ${data.erro || data.mensagem || 'Erro desconhecido'}`, false);
                    return;
                }
                
                showMsg('Ordem recalculada e salva com sucesso!');
                await carregarCursosDaGrade();
            } catch (error) {
                showMsg('Erro ao recalcular ordem: ' + error.message, false);
            }
        }

        async function carregarCursosDisponiveis() {
            const sel = document.getElementById('cursoSelect');
            sel.innerHTML = `<option>Carregando...</option>`;

            const res = await fetch(apiUrl('cursos'));
            const data = await res.json().catch(() => null);
            const lista = normalizeArrayResponse(data);

            if (!res.ok || !lista) {
                sel.innerHTML = `<option value="">Erro ao carregar cursos</option>`;
                return;
            }

            sel.innerHTML = lista.map(c => {
                const id = getIdCurso(c);
                const nome = getNomeCurso(c);
                const horas = Number(c.horas ?? 0);
                return `<option value="${id}">${nome} (${horas}h)</option>`;
            }).join('');
        }

        async function carregarCursosDaGrade() {
            const tbody = document.getElementById('tbody');
            tbody.innerHTML = `<tr><td colspan="4" class="muted">Carregando...</td></tr>`;
            showMsg('');

            const res = await fetch(apiUrl(`grades/${ID_GRADE}/cursos`));
            const data = await res.json().catch(() => null);
            const lista = normalizeArrayResponse(data);

            if (!res.ok || !lista) {
                tbody.innerHTML = `<tr><td colspan="4" class="muted">Erro ao carregar: ${JSON.stringify(data)}</td></tr>`;
                return;
            }

            // ordena por ordem
            lista.sort((a, b) => getOrdem(a) - getOrdem(b));

            const totalHoras = lista.reduce((sum, c) => sum + getHoras(c), 0);
            document.getElementById('resumo').textContent = `${lista.length} cursos - ${totalHoras} horas totais`;

            if (lista.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="muted">Nenhum curso nesta grade.</td></tr>`;
                return;
            }

            tbody.innerHTML = lista.map((c, idx) => {
                const idCurso = getIdCurso(c);
                const nome = getNomeCurso(c);
                const ordem = getOrdem(c) || (idx + 1);
                const horas = getHoras(c);

                return `
                <tr data-curso-id="${idCurso}" style="cursor: grab;">
                    <td style="padding: 10px 5px; text-align: center;">
                        <span class="drag-hint" title="Arraste para reordenar" style="cursor: grab;">::</span>
                    </td>
                    <td>${ordem}</td>
                    <td>${nome}</td>
                    <td>
                        <input type="number" min="0" step="1" value="${horas}" style="width:110px;"
                               onchange="atualizarHoras(${idCurso}, this.value)">
                    </td>
                    <td>
                        <div class="actions">
                            <button class="btn btn-mini btn-danger" onclick="removerCurso(${idCurso}, '${nome.replace(/'/g, "\'")}')">Remover</button>
                        </div>
                    </td>
                </tr>
            `;
            }).join('');

            // Aguarda proximo frame para garantir que DOM foi renderizado
            requestAnimationFrame(() => {
                initSortable();
            });
        }

        async function adicionarCurso() {
            const idCurso = Number(document.getElementById('cursoSelect').value || 0);
            const horasCustomRaw = document.getElementById('horasCustom').value;
            const horasCustom = horasCustomRaw !== '' ? Number(horasCustomRaw) : null;

            if (!idCurso) {
                showMsg('Selecione um curso.', false);
                return;
            }

            // Verifica se o curso já existe na grade
            const res = await fetch(apiUrl(`grades/${ID_GRADE}/cursos`));
            const cursosExistentes = await res.json();

            if (Array.isArray(cursosExistentes)) {
                const cursoJaExiste = cursosExistentes.some(c => getIdCurso(c) === idCurso);
                if (cursoJaExiste) {
                    showMsg('Este curso já foi adicionado a esta grade.', false);
                    return;
                }
            }

            const payload = {
                id_curso: idCurso,
                // envia os 2 nomes para compatibilidade com qualquer versão do controller
                horas_personalizadas: horasCustom,
                carga_horaria_custom: horasCustom
            };

            const resAdd = await fetch(apiUrl(`grades/${ID_GRADE}/cursos`), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await resAdd.json().catch(() => ({}));

            if (!resAdd.ok) {
                showMsg(`Erro ao adicionar: ${data.erro || data.mensagem || JSON.stringify(data)}`, false);
                return;
            }

            document.getElementById('horasCustom').value = '';
            showMsg('Curso adicionado com sucesso.');
            await carregarCursosDaGrade();
        }

        async function removerCurso(idCurso, nome) {
            if (!confirm(`Remover o curso "${nome}" da grade?`)) return;

            const res = await fetch(apiUrl(`grades/${ID_GRADE}/cursos/${idCurso}`), {
                method: 'DELETE'
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                showMsg(`Erro ao remover: ${data.erro || data.mensagem || JSON.stringify(data)}`, false);
                return;
            }

            showMsg('Curso removido.');
            await carregarCursosDaGrade();
        }

        async function atualizarHoras(idCurso, horas) {
            const h = Number(horas || 0);

            const payload = {
                horas_personalizadas: h,
                carga_horaria_custom: h
            };

            const res = await fetch(apiUrl(`grades/${ID_GRADE}/cursos/${idCurso}`), {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                showMsg(`Erro ao atualizar horas: ${data.erro || data.mensagem || JSON.stringify(data)}`, false);
                return;
            }

            showMsg('Horas atualizadas.');
            await carregarCursosDaGrade();
        }

        // init
        carregarCursosDisponiveis();
        carregarCursosDaGrade();
    </script>
</body>

</html>
