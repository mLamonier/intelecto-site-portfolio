<?php
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Configurações padrão de valores';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Intelecto</title>
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
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
            position: relative;
        }

        .header h1 {
            color: white;
            font-size: 22px;
        }

        .back-btn {
            position: relative;
            padding: 10px 16px;
            border-radius: 8px;
            background: #4a5568;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: #2d3748;
        }

        .btn {
            padding: 10px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .back-btn {
            background: #292929;
            color: #fff;
        }

        .back-btn:hover {
            background: #3b4557;
        }

        .btn-primary {
            background: #25bd31;
            color: #fff;
        }

        .btn-primary:hover {
            background: #189723;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 14px;
        }

        label {
            display: block;
            font-size: 12px;
            color: #4a5568;
            margin-bottom: 6px;
        }

        input[type="number"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        input[type="number"]:focus {
            outline: none;
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .check {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .check input {
            width: auto;
        }

        .actions {
            margin-top: 16px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .muted {
            color: #718096;
            font-size: 12px;
            margin-top: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ <?= $page_title ?></h1>
            <a class="back-btn" href="../index.php">← Voltar ao Dashboard</a>
        </div>

        <div class="card">
            <div id="alert"></div>
            <div class="grid">
                <div>
                    <label>Valor mensal presencial padrão</label>
                    <input type="number" step="0.01" min="0" id="VALOR_MENSAL_PRESENCIAL_PADRAO" placeholder="0.00" />
                    <div class="muted">Usado quando uma grade nova não informar valor presencial.</div>
                </div>
                <div>
                    <label>Valor mensal EAD padrão</label>
                    <input type="number" step="0.01" min="0" id="VALOR_MENSAL_EAD_PADRAO" placeholder="0.00" />
                    <div class="muted">Usado quando uma grade nova não informar valor EAD.</div>
                </div>
                <div>
                    <label>Valor de matrícula padrão</label>
                    <input type="number" step="0.01" min="0" id="VALOR_MATRICULA_PADRAO" placeholder="0.00" />
                    <div class="muted">Aplicado quando a grade não definir matrícula.</div>
                </div>
                <div>
                    <label>Desconto % plano parcelado padrão</label>
                    <input type="number" step="0.01" min="0" max="100" id="DESCONTO_PARCELADO_PADRAO" placeholder="ex: 5" />
                    <div class="muted">Desconto padrão aplicado no plano parcelado (%).</div>
                </div>
                <div>
                    <label>Desconto % plano à vista padrão</label>
                    <input type="number" step="0.01" min="0" max="100" id="DESCONTO_AVISTA_PADRAO" placeholder="ex: 10" />
                    <div class="muted">Desconto padrão aplicado no plano à vista (%).</div>
                </div>
            </div>

            <div class="actions">
                <button class="btn btn-primary" id="btnSalvar">💾 Salvar padrões</button>
                <button class="btn btn-secondary" id="btnRecarregar">↻ Recarregar</button>
            </div>
        </div>
    </div>

    <script>
        const API_URL = '<?php echo $apiBase; ?>';
        const campos = [
            'VALOR_MENSAL_PRESENCIAL_PADRAO',
            'VALOR_MENSAL_EAD_PADRAO',
            'VALOR_MATRICULA_PADRAO',
            'DESCONTO_PARCELADO_PADRAO',
            'DESCONTO_AVISTA_PADRAO'
        ];

        function setAlert(msg, type = 'success') {
            const el = document.getElementById('alert');
            el.innerHTML = `<div class="alert ${type === 'success' ? 'alert-success' : 'alert-error'}">${msg}</div>`;
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        function clearAlert() {
            document.getElementById('alert').innerHTML = '';
        }

        async function carregar() {
            clearAlert();
            const res = await fetch(`${API_URL}/config`);
            const data = await res.json();
            if (!Array.isArray(data)) {
                setAlert('Não foi possível carregar as configurações.', 'error');
                return;
            }
            const map = {};
            data.forEach(item => {
                if (item.chave) map[item.chave] = item.valor;
            });
            campos.forEach(key => {
                const el = document.getElementById(key);
                const val = map[key];
                if (!el) return;
                el.value = val ?? '';
            });
        }

        async function salvar() {
            clearAlert();
            for (const key of campos) {
                const el = document.getElementById(key);
                if (!el) continue;
                const valor = el.value;
                const resp = await fetch(`${API_URL}/config`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        chave: key,
                        valor
                    })
                });
                if (!resp.ok) {
                    setAlert(`Erro ao salvar ${key}`, 'error');
                    return;
                }
            }
            setAlert('Padrões salvos com sucesso.');
        }

        carregar();

        document.getElementById('btnSalvar').addEventListener('click', (e) => {
            e.preventDefault();
            salvar();
        });
        document.getElementById('btnRecarregar').addEventListener('click', (e) => {
            e.preventDefault();
            carregar();
        });
    </script>
</body>

</html>
