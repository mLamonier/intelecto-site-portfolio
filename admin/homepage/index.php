<?php
require_once __DIR__ . '/../includes/auth_admin.php';

$root = dirname(__DIR__, 2);
$assetsRoot = $root . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets';
$imageAssets = [];
if (is_dir($assetsRoot)) {
    $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($assetsRoot, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($rii as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm', 'ogg'], true)) {
            continue;
        }
        $relative = str_replace($assetsRoot . DIRECTORY_SEPARATOR, '', $file->getPathname());
        $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
        if (strpos($relative, 'uploads/') === 0) {
            continue;
        }
        $imageAssets[] = 'assets/' . $relative;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador da Homepage</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url("<?php echo htmlspecialchars(site_asset_path('assets/background branco.png')); ?>");
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .back-btn {
            position: absolute;
            top: 30px;
            left: 30px;
            padding: 12px 24px;
            background: #292929;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: #3b4557;
        }

        .nav-tabs {
            display: flex;
            border-bottom: 2px solid #e5e7eb;
            background: #f9fafb;
            overflow-x: auto;
        }

        .nav-tabs button {
            flex: 1;
            padding: 15px 20px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            color: #6b7280;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .nav-tabs button:hover {
            color: #dc2626;
            background: rgba(220, 38, 38, 0.05);
        }

        .nav-tabs button.active {
            color: #dc2626;
            border-bottom-color: #dc2626;
        }

        .content {
            padding: 30px;
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        .section h2 {
            color: #1f2937;
            margin-bottom: 20px;
            font-size: 1.5rem;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select,
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 0.95rem;
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group input[type="file"] {
            padding: 5px;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #dc2626;
            color: white;
        }

        .btn-primary:hover {
            background: #991b1b;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        .items-list {
            margin-top: 20px;
            border-top: 2px solid #e5e7eb;
            padding-top: 20px;
        }

        .item {
            background: #f9fafb;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #dc2626;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .item-info {
            flex: 1;
        }

        .item-thumb {
            margin-right: 15px;
            flex-shrink: 0;
        }

        .item-title {
            font-weight: 600;
            color: #1f2937;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .item-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 5px;
        }

        .item-meta-item {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .item-desc {
            color: #6b7280;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .item-actions {
            display: flex;
            gap: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-badge.ativo {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.inativo {
            background: #fee2e2;
            color: #991b1b;
        }

        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
            display: block;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
            display: block;
        }


        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .modal-header h3 {
            margin: 0;
            order: 1;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 15px 20px;
            background: #f9fafb;
            border-radius: 0 0 8px 8px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            order: 2;
        }

        .close:hover,
        .close:focus {
            opacity: 0.7;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .nav-tabs button {
                font-size: 0.85rem;
                padding: 12px 10px;
            }

            .item {
                flex-direction: column;
                align-items: flex-start;
            }

            .item-actions {
                margin-top: 10px;
                width: 100%;
            }

            .item-actions .btn {
                flex: 1;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <a href="../index.php" class="back-btn">← Voltar ao Dashboard</a>
            <h1>🏠 Gerenciador da Homepage</h1>
            <p>Personalize o conteúdo exibido na página inicial do site</p>
        </div>

        <div class="nav-tabs">
            <button class="tab-btn active" onclick="switchTab('banners')">📸 Banner</button>
            <button class="tab-btn" onclick="switchTab('grades')">📚 Cursos Mais Vendidos</button>
            <button class="tab-btn" onclick="switchTab('stats')">📊 Estatísticas</button>
            <button class="tab-btn" onclick="switchTab('categories')">🏷️ Categorias</button>
            <button class="tab-btn" onclick="switchTab('testimonials')">💬 Depoimentos</button>
            <button class="tab-btn" onclick="switchTab('faq')">❓ FAQ</button>
            <button class="tab-btn" onclick="switchTab('grade-valores')">💰 Grade Personalizada</button>
        </div>

        <div class="content">
            <div id="alert-container"></div>


            <div id="banners" class="section active">
                <h2>📸 Gerenciar Banner</h2>

                <div class="form-group">
                    <label>Título do Banner</label>
                    <input type="text" id="bannerTitle" placeholder="Ex: Promoção de Verão">
                </div>

                <div class="form-group">
                    <label>Upload de Imagem (Desktop)</label>
                    <input type="text" id="bannerImage" list="homepageImageAssets" placeholder="assets/banners/exemplo.jpg">
                    <small style="color: #6b7280; display: block; margin-top: 5px;">Recomendado: 1920x600px ou superior</small>
                </div>

                <div class="form-group">
                    <label>Upload de Imagem Mobile (opcional)</label>
                    <input type="text" id="bannerImageMobile" list="homepageImageAssets" placeholder="assets/banners/exemplo-mobile.jpg">
                    <small style="color: #6b7280; display: block; margin-top: 5px;">Esta imagem será exibida em telas menores que 768px. Recomendado: 768x400px</small>
                </div>

                <div class="form-group">
                    <label>Link (opcional) - deixe vazio se não quiser que seja clicável</label>
                    <input type="text" id="bannerLink" placeholder="Ex: https://exemplo.com/promocao">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Ordem</label>
                        <input type="number" id="bannerOrder" value="0">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="bannerActive" checked>
                            Ativo
                        </label>
                    </div>
                </div>

                <button class="btn btn-primary" onclick="addBanner()">+ Adicionar Banner</button>

                <div class="items-list" id="banners-list"></div>
            </div>


            <div id="grades" class="section">
                <h2>📚 Cursos Mais Vendidos</h2>

                <div class="form-group">
                    <label>Selecionar Grades</label>
                    <select id="gradesSelect">
                        <option value="">Escolha uma grade...</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Ordem</label>
                        <input type="number" id="gradeOrder" value="0">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="gradeActive" checked>
                            Ativo
                        </label>
                    </div>
                </div>

                <button class="btn btn-primary" onclick="addGradeToCarousel()">+ Adicionar Grade</button>

                <div class="items-list" id="grades-list"></div>
            </div>


            <div id="stats" class="section">
                <h2>📊 Estatísticas (Alunos, Cursos, etc)</h2>
                <div id="stats-list"></div>
            </div>


            <div id="categories" class="section">
                <h2>🏷️ Gerenciar Categorias</h2>
                <p style="color: #6b7280; margin-bottom: 20px;">Essas categorias alimentam o filtro da página <code>/cursos?categoria=id</code> para listar grades.</p>

                <div class="form-group">
                    <label>Selecionar Categoria</label>
                    <select id="categoriesSelect">
                        <option value="">Escolha uma categoria...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Upload de Imagem</label>
                    <input type="text" id="categoryImage" list="homepageImageAssets" placeholder="assets/categorias/exemplo.jpg">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Ordem</label>
                        <input type="number" id="categoryOrder" value="0">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="categoryActive" checked>
                            Ativo
                        </label>
                    </div>
                </div>

                <button class="btn btn-primary" onclick="addCategory()">+ Adicionar Categoria</button>

                <div class="items-list" id="categories-list"></div>
            </div>


            <div id="testimonials" class="section">
                <h2>💬 Gerenciar Depoimentos</h2>

                <div class="form-group">
                    <label>Mídia do Depoimento (imagem ou vídeo)</label>
                    <input type="text" id="testimonialPhoto" list="homepageImageAssets" placeholder="assets/testimonials/exemplo.jpg ou .mp4">
                    <small style="color: #6b7280; display: block; margin-top: 5px;">Para vídeo, envie apenas o arquivo (.mp4/.webm/.ogg). Nome, curso e mensagem ficam opcionais.</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nome do Aluno</label>
                        <input type="text" id="testimonialName">
                    </div>
                    <div class="form-group">
                        <label>Curso</label>
                        <input type="text" id="testimonialCourse">
                    </div>
                </div>

                <div class="form-group">
                    <label>Mensagem de Depoimento</label>
                    <textarea id="testimonialMessage"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Ordem</label>
                        <input type="number" id="testimonialOrder" value="0">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="testimonialActive" checked>
                            Ativo
                        </label>
                    </div>
                </div>

                <button class="btn btn-primary" onclick="addTestimonial()">+ Adicionar Depoimento</button>

                <div class="items-list" id="testimonials-list"></div>
            </div>


            <div id="faq" class="section">
                <h2>❓ Gerenciar Perguntas Frequentes</h2>

                <div class="form-group">
                    <label>Pergunta</label>
                    <input type="text" id="faqQuestion">
                </div>

                <div class="form-group">
                    <label>Resposta</label>
                    <textarea id="faqAnswer"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Ordem</label>
                        <input type="number" id="faqOrder" value="0">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="faqActive" checked>
                            Ativo
                        </label>
                    </div>
                </div>

                <button class="btn btn-primary" onclick="addFAQ()">+ Adicionar FAQ</button>

                <div class="items-list" id="faq-list"></div>
            </div>


            <div id="grade-valores" class="section">
                <h2>💰 Valores da Grade Personalizada</h2>
                <p style="color: #6b7280; margin-bottom: 20px;">Configure os valores de mensalidade para montagem de grade personalizada</p>

                <div class="form-row">
                    <div class="form-group">
                        <label>Mensalidade - Presencial (R$)</label>
                        <input type="number" id="valorMensalPresencial" step="0.01" min="0" placeholder="Ex: 200.00">
                        <small style="color: #6b7280; display: block; margin-top: 5px;">Valor da mensalidade no modo presencial</small>
                    </div>
                    <div class="form-group">
                        <label>Mensalidade - EAD (R$)</label>
                        <input type="number" id="valorMensalEad" step="0.01" min="0" placeholder="Ex: 180.00">
                        <small style="color: #6b7280; display: block; margin-top: 5px;">Valor da mensalidade no modo EAD</small>
                    </div>
                </div>

                <button class="btn btn-primary" onclick="saveValoresGrade()">💾 Salvar Valores</button>

                <div style="margin-top: 30px; padding: 15px; background: #f0f9ff; border-left: 4px solid #0284c7; border-radius: 4px;">
                    <h4 style="color: #0c4a6e; margin-bottom: 8px;">ℹ️ Como funciona:</h4>
                    <p style="color: #075985; font-size: 0.9rem; line-height: 1.6;">
                        Os valores configurados aqui são usados na página "Monte sua Grade" para calcular automaticamente o preço total da grade personalizada do aluno.
                        <br><br>
                        <strong>Cálculo:</strong>
                        <br>1. Carga horária total ÷ 16h/mês = Duração em meses
                        <br>2. Valor da mensalidade × Duração em meses = Valor Total
                        <br><br>
                        <em>Pressuposição: 2 aulas/semana de 2h cada (4h/semana = 16h/mês)</em>
                    </p>
                </div>
            </div>
        </div>
    </div>


    <div id="modalEditBanner" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal('modalEditBanner')">&times;</span>
                <h3>Editar Banner</h3>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editBannerId">
                <div class="form-group">
                    <label>Título</label>
                    <input type="text" id="editBannerTitle">
                </div>
                <div class="form-group">
                    <label id="labelBannerDesktop">Alterar Imagem Desktop (opcional)</label>
                    <input type="text" id="editBannerImage" list="homepageImageAssets" placeholder="assets/banners/exemplo.jpg">
                    <small style="color: #6b7280; display: block; margin-top: 5px;">Deixe vazio para manter a imagem atual</small>
                </div>
                <div class="form-group">
                    <label id="labelBannerMobile">Alterar Imagem Mobile (opcional)</label>
                    <input type="text" id="editBannerImageMobile" list="homepageImageAssets" placeholder="assets/banners/exemplo-mobile.jpg">
                    <small style="color: #6b7280; display: block; margin-top: 5px;">Deixe vazio para manter a imagem atual. Esta imagem será exibida em telas < 768px</small>
                </div>
                <div class="form-group">
                    <label>Link (deixe vazio se não quiser que seja clicável)</label>
                    <input type="text" id="editBannerLink">
                </div>
                <div class="form-group">
                    <label>Ordem</label>
                    <input type="number" id="editBannerOrder">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="editBannerActive">
                        Ativo
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('modalEditBanner')">Cancelar</button>
                <button class="btn btn-primary" onclick="saveBanner()">Salvar</button>
            </div>
        </div>
    </div>


    <div id="modalEditGrade" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal('modalEditGrade')">&times;</span>
                <h3>Editar Grade</h3>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editGradeId">
                <div class="form-group">
                    <label>Ordem</label>
                    <input type="number" id="editGradeOrder">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="editGradeActive">
                        Ativo
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('modalEditGrade')">Cancelar</button>
                <button class="btn btn-primary" onclick="saveGrade()">Salvar</button>
            </div>
        </div>
    </div>


    <div id="modalEditStat" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal('modalEditStat')">&times;</span>
                <h3>Editar Estatística</h3>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editStatTipo">
                <div class="form-group">
                    <label>Label (texto exibido)</label>
                    <input type="text" id="editStatLabel">
                </div>
                <div class="form-group">
                    <label>Valor</label>
                    <input type="number" id="editStatValor">
                </div>
                <div class="form-group">
                    <label>Imagem/Ícone (opcional)</label>
                    <input type="text" id="editStatImage" list="homepageImageAssets" placeholder="assets/icons/exemplo.png">
                    <div id="editStatImagePreview" style="margin-top: 10px;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('modalEditStat')">Cancelar</button>
                <button class="btn btn-primary" onclick="saveStat()">Salvar</button>
            </div>
        </div>
    </div>


    <div id="modalEditCategory" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal('modalEditCategory')">&times;</span>
                <h3>Editar Categoria</h3>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editCategoryId">
                <div class="form-group">
                    <label>Nome da Categoria</label>
                    <input type="text" id="editCategoryName" list="categoryNameList" placeholder="Nome da categoria">
                    <datalist id="categoryNameList"></datalist>
                </div>
                <div class="form-group">
                    <label id="labelCategoryImage">Alterar Imagem (opcional)</label>
                    <input type="text" id="editCategoryImage" list="homepageImageAssets" placeholder="assets/categorias/exemplo.jpg">
                    <small style="color: #6b7280; display: block; margin-top: 5px;">Deixe vazio para manter a imagem atual</small>
                </div>
                <div class="form-group">
                    <label>Ordem</label>
                    <input type="number" id="editCategoryOrder">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="editCategoryActive">
                        Ativo
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('modalEditCategory')">Cancelar</button>
                <button class="btn btn-primary" onclick="saveCategory()">Salvar</button>
            </div>
        </div>
    </div>


    <div id="modalEditTestimonial" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal('modalEditTestimonial')">&times;</span>
                <h3>Editar Depoimento</h3>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editTestimonialId">
                <div class="form-group">
                    <label>Mídia (imagem ou vídeo)</label>
                    <input type="text" id="editTestimonialMedia" list="homepageImageAssets" placeholder="assets/testimonials/exemplo.jpg ou .mp4">
                </div>
                <div class="form-group">
                    <label>Nome do Aluno</label>
                    <input type="text" id="editTestimonialName">
                </div>
                <div class="form-group">
                    <label>Curso</label>
                    <input type="text" id="editTestimonialCourse">
                </div>
                <div class="form-group">
                    <label>Mensagem</label>
                    <textarea id="editTestimonialMessage"></textarea>
                </div>
                <div class="form-group">
                    <label>Ordem</label>
                    <input type="number" id="editTestimonialOrder">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="editTestimonialActive">
                        Ativo
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('modalEditTestimonial')">Cancelar</button>
                <button class="btn btn-primary" onclick="saveTestimonial()">Salvar</button>
            </div>
        </div>
    </div>


    <div id="modalEditFAQ" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal('modalEditFAQ')">&times;</span>
                <h3>Editar FAQ</h3>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editFAQId">
                <div class="form-group">
                    <label>Pergunta</label>
                    <input type="text" id="editFAQQuestion">
                </div>
                <div class="form-group">
                    <label>Resposta</label>
                    <textarea id="editFAQAnswer"></textarea>
                </div>
                <div class="form-group">
                    <label>Ordem</label>
                    <input type="number" id="editFAQOrder">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="editFAQActive">
                        Ativo
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('modalEditFAQ')">Cancelar</button>
                <button class="btn btn-primary" onclick="saveFAQ()">Salvar</button>
            </div>
        </div>
    </div>

    <script>
        async function parseJsonResponse(response) {
            const text = await response.text();
            const clean = text.replace(/^\uFEFF/, "");
            return JSON.parse(clean);
        }

        function normalizeArrayResponse(data) {
            if (Array.isArray(data)) return data;
            if (data && Array.isArray(data.data)) return data.data;
            return null;
        }

        const API_URL = <?php echo json_encode(rtrim(site_base_url(), '/') . '/api'); ?>;

        const SITE_BASE = <?php echo json_encode(site_base_path()); ?>;
        const ASSET_BASE = <?php echo json_encode(site_asset_path('assets')); ?>;

        function assetUrl(path) {
            if (!path) return '';
            if (path.startsWith('http://') || path.startsWith('https://')) return path;

            let clean = path.replace(/^\/+/, '');

            if (clean.startsWith('frontend/public/assets/')) {
                clean = clean.replace(/^frontend\/public\//, '');
            }

            if (clean.startsWith('assets/')) {
                clean = clean.replace(/^assets\//, '');
                return `${ASSET_BASE}/${clean}`;
            }

            if (clean.startsWith('uploads/')) {
                return `${ASSET_BASE}/${clean}`;
            }

            return `${SITE_BASE}/${clean}`;
        }

        function isVideoMedia(path) {
            return /\.(mp4|webm|ogg)(\?.*)?$/i.test((path || '').trim());
        }

        function escapeForInlineJs(value) {
            return String(value ?? '')
                .replace(/\\/g, '\\\\')
                .replace(/'/g, "\\'")
                .replace(/\r?\n/g, '\\n');
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function switchTab(tabName) {

            const sections = document.querySelectorAll('.section');
            sections.forEach(section => section.classList.remove('active'));


            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));


            document.getElementById(tabName).classList.add('active');


            event.target.classList.add('active');


            if (tabName === 'banners') loadBanners();
            else if (tabName === 'grades') loadGrades();
            else if (tabName === 'stats') loadStats();
            else if (tabName === 'categories') loadCategories();
            else if (tabName === 'testimonials') loadTestimonials();
            else if (tabName === 'faq') loadFAQ();
            else if (tabName === 'grade-valores') loadValoresGrade();
        }

        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alert-container');
            alertContainer.innerHTML = `<div class="alert ${type}">${message}</div>`;
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 4000);
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }


        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }


        let currentBannerImage = '';
        let currentBannerImageMobile = '';
        async function loadBanners() {
            try {
                const response = await fetch(`${API_URL}/homepage/banners/all`);
                const banners = await parseJsonResponse(response);

                let html = '';
                banners.forEach(banner => {
                    const statusBadge = banner.ativo ? '<span class="status-badge ativo">Ativo</span>' : '<span class="status-badge inativo">Inativo</span>';
                    const linkInfo = banner.link ? `<span class="item-meta-item">Clicável</span>` : '';
                    const titulo = banner.titulo || `Banner #${banner.id_banner}`;
                    const imagemThumb = banner.imagem ?
                        `<img src="${assetUrl(banner.imagem)}" style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px; margin-right: 15px;">` :
                        '';
                    html += `
                        <div class="item">
                            <div class="item-info" style="display: flex; align-items: center;">
                                ${imagemThumb}
                                <div>
                                    <div class="item-title">${titulo}</div>
                                    <div class="item-meta">
                                        <span class="item-meta-item">Ordem: ${banner.ordem}</span>
                                        ${statusBadge}
                                        ${linkInfo}
                                    </div>
                                </div>
                            </div>
                            <div class="item-actions">
                                <button class="btn btn-secondary btn-small" onclick='editBanner(${banner.id_banner}, "${banner.titulo || ''}", "${banner.link || ''}", ${banner.ordem}, ${banner.ativo}, "${banner.imagem || ''}", "${banner.imagem_mobile || ''}")'>Editar</button>
                                <button class="btn btn-danger btn-small" onclick="deleteBanner(${banner.id_banner})">Deletar</button>
                            </div>
                        </div>
                    `;
                });
                document.getElementById('banners-list').innerHTML = html || '<p>Nenhum banner cadastrado</p>';
            } catch (error) {
                showAlert('Erro ao carregar banners', 'error');
            }
        }

        async function addBanner() {
            const title = document.getElementById('bannerTitle').value;
            const link = document.getElementById('bannerLink').value;
            const ordem = parseInt(document.getElementById('bannerOrder').value || '0', 10);
            const ativo = document.getElementById('bannerActive').checked ? 1 : 0;
            const imagePath = document.getElementById('bannerImage').value.trim();
            const imageMobilePath = document.getElementById('bannerImageMobile').value.trim();

            if (!imagePath) {
                showAlert('Selecione a imagem do banner', 'error');
                return;
            }

            try {
                const response = await fetch(`${API_URL}/homepage/banners`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        imagem: imagePath,
                        imagem_mobile: imageMobilePath || null,
                        titulo: title || null,
                        link: link || null,
                        ordem: ordem,
                        ativo: ativo
                    })
                });
                const result = await parseJsonResponse(response);

                if (result.success) {
                    showAlert('Banner adicionado!');
                    document.getElementById('bannerImage').value = '';
                    document.getElementById('bannerImageMobile').value = '';
                    document.getElementById('bannerTitle').value = '';
                    document.getElementById('bannerLink').value = '';
                    document.getElementById('bannerOrder').value = 0;
                    document.getElementById('bannerActive').checked = true;
                    loadBanners();
                } else {
                    showAlert('Erro ao adicionar banner', 'error');
                }
            } catch (error) {
                showAlert('Erro ao adicionar banner', 'error');
            }
        }

        function editBanner(id, titulo, link, ordem, ativo, imagem, imagemMobile) {
            document.getElementById('editBannerId').value = id;
            document.getElementById('editBannerTitle').value = titulo || '';
            document.getElementById('editBannerLink').value = link || '';
            document.getElementById('editBannerOrder').value = ordem ?? 0;
            document.getElementById('editBannerActive').checked = ativo == 1;

            currentBannerImage = imagem || '';
            currentBannerImageMobile = imagemMobile || '';

            const desktopLabel = document.getElementById('labelBannerDesktop');
            const mobileLabel = document.getElementById('labelBannerMobile');
            if (desktopLabel) {
                desktopLabel.textContent = currentBannerImage ?
                    `Alterar Imagem Desktop (atual: ${currentBannerImage})` :
                    'Alterar Imagem Desktop (opcional)';
            }
            if (mobileLabel) {
                mobileLabel.textContent = currentBannerImageMobile ?
                    `Alterar Imagem Mobile (atual: ${currentBannerImageMobile})` :
                    'Alterar Imagem Mobile (opcional)';
            }

            document.getElementById('editBannerImage').value = currentBannerImage;
            document.getElementById('editBannerImageMobile').value = currentBannerImageMobile;
            openModal('modalEditBanner');
        }

        async function saveBanner() {
            const id = document.getElementById('editBannerId').value;
            const titulo = document.getElementById('editBannerTitle').value;
            const link = document.getElementById('editBannerLink').value;
            const ordem = parseInt(document.getElementById('editBannerOrder').value || '0', 10);
            const ativo = document.getElementById('editBannerActive').checked ? 1 : 0;
            const imagePath = document.getElementById('editBannerImage').value.trim();
            const imageMobilePath = document.getElementById('editBannerImageMobile').value.trim();

            try {
                const payload = {
                    titulo: titulo || null,
                    link: link || null,
                    ordem: ordem,
                    ativo: ativo
                };

                if (imagePath !== '') {
                    payload.imagem = imagePath;
                }
                if (imageMobilePath !== '') {
                    payload.imagem_mobile = imageMobilePath;
                }

                const response = await fetch(`${API_URL}/homepage/banners/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const result = await parseJsonResponse(response);

                if (result.success) {
                    showAlert('Banner atualizado!');
                    closeModal('modalEditBanner');
                    loadBanners();
                } else {
                    showAlert('Erro ao atualizar banner', 'error');
                }
            } catch (error) {
                showAlert('Erro ao atualizar banner', 'error');
            }
        }

        async function deleteBanner(id) {
            if (!confirm('Deletar este banner?')) {
                return;
            }

            try {
                const response = await fetch(`${API_URL}/homepage/banners/${id}`, {
                    method: 'DELETE'
                });
                const result = await parseJsonResponse(response);

                if (result.success) {
                    showAlert('Banner deletado!');
                    loadBanners();
                } else {
                    showAlert('Erro ao deletar banner', 'error');
                }
            } catch (error) {
                showAlert('Erro ao deletar banner', 'error');
            }
        }

        async function loadGrades() {
            try {
                const response = await fetch(`${API_URL}/grades`);
                const gradesRaw = await parseJsonResponse(response);
                const grades = normalizeArrayResponse(gradesRaw) || [];

                const select = document.getElementById('gradesSelect');
                let html = '<option value="">Escolha uma grade...</option>';
                grades.forEach(grade => {
                    html += `<option value="${grade.id_grade}">${grade.nome}</option>`;
                });
                select.innerHTML = html;

                const carouselResponse = await fetch(`${API_URL}/homepage/grades-carousel`);
                const carouselRaw = await parseJsonResponse(carouselResponse);
                const carousel = normalizeArrayResponse(carouselRaw) || [];

                let listHtml = '';
                carousel.forEach(item => {
                    const statusBadge = item.ativo ? '<span class="status-badge ativo">Ativo</span>' : '<span class="status-badge inativo">Inativo</span>';
                    const imagemUrl = item.imagem_card ? assetUrl(item.imagem_card) : '/placeholder.jpg';
                    const thumbnail = `<img src="${imagemUrl}" alt="${item.nome}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px;" onerror="this.src='/placeholder.jpg'" />`;
                    listHtml += `
                        <div class="item">
                            <div class="item-thumb">
                                ${thumbnail}
                            </div>
                            <div class="item-info">
                                <div class="item-title">${item.nome}</div>
                                <div class="item-meta">
                                    <span class="item-meta-item">Ordem: ${item.ordem}</span>
                                    ${statusBadge}
                                </div>
                            </div>
                            <div class="item-actions">
                                <button class="btn btn-secondary btn-small" onclick="editGrade(${item.id})">Editar</button>
                                <button class="btn btn-danger btn-small" onclick="deleteGrade(${item.id})">Remover</button>
                            </div>
                        </div>
                    `;
                });
                document.getElementById('grades-list').innerHTML = listHtml || '<p>Nenhuma grade no carousel</p>';
            } catch (error) {
                showAlert('Erro ao carregar grades', 'error');
            }
        }

        async function addGradeToCarousel() {
            const gradesSelect = document.getElementById('gradesSelect');
            const id_grade = gradesSelect.value;
            const ordem = document.getElementById('gradeOrder').value;
            const ativo = document.getElementById('gradeActive').checked ? 1 : 0;

            if (!id_grade) {
                showAlert('Selecione uma grade', 'error');
                return;
            }

            try {
                const response = await fetch(`${API_URL}/homepage/grades-carousel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_grade: parseInt(id_grade),
                        ordem: parseInt(ordem),
                        ativo: ativo
                    })
                });
                const result = await parseJsonResponse(response);

                if (result.success) {
                    showAlert('Grade adicionada ao carousel!');
                    gradesSelect.value = '';
                    document.getElementById('gradeOrder').value = 0;
                    document.getElementById('gradeActive').checked = true;
                    loadGrades();
                }
            } catch (error) {
                showAlert('Erro ao adicionar grade', 'error');
            }
        }

        function editGrade(id, ordem, ativo) {
            document.getElementById('editGradeId').value = id;
            document.getElementById('editGradeOrder').value = ordem;
            document.getElementById('editGradeActive').checked = ativo == 1;
            openModal('modalEditGrade');
        }

        async function saveGrade() {
            const id = document.getElementById('editGradeId').value;
            const ordem = document.getElementById('editGradeOrder').value;
            const ativo = document.getElementById('editGradeActive').checked ? 1 : 0;

            try {
                const response = await fetch(`${API_URL}/homepage/grades-carousel/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        ordem: parseInt(ordem),
                        ativo: ativo
                    })
                });
                const result = await parseJsonResponse(response);

                if (result.success) {
                    showAlert('Grade atualizada!');
                    closeModal('modalEditGrade');
                    loadGrades();
                }
            } catch (error) {
                showAlert('Erro ao atualizar grade', 'error');
            }
        }

        async function removeGradeFromCarousel(id) {
            if (confirm('Remover esta grade do carousel?')) {
                try {
                    const response = await fetch(`${API_URL}/homepage/grades-carousel/${id}`, {
                        method: 'DELETE'
                    });
                    const result = await parseJsonResponse(response);

                    if (result.success) {
                        showAlert('Grade removida!');
                        loadGrades();
                    }
                } catch (error) {
                    showAlert('Erro ao remover grade', 'error');
                }
            }
        }


        async function loadStats() {
            try {
                const response = await fetch(`${API_URL}/homepage/stats`);
                const stats = await parseJsonResponse(response);

                let html = '';
                stats.forEach(stat => {
                    const imagemThumb = stat.imagem ?
                        `<img src="${assetUrl(stat.imagem)}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; margin-right: 10px;">` :
                        '';
                    html += `
                        <div class="item">
                            <div class="item-info" style="display: flex; align-items: center;">
                                ${imagemThumb}
                                <div>
                                    <div class="item-title">${stat.label}</div>
                                    <div class="item-meta">
                                        <span class="item-meta-item">Valor: ${stat.valor}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="item-actions">
                                <button class="btn btn-secondary btn-small" onclick='editStat("${stat.tipo}", "${stat.label}", ${stat.valor}, "${stat.imagem || ""}")'>Editar</button>
                            </div>
                        </div>
                    `;
                });
                document.getElementById('stats-list').innerHTML = html;
            } catch (error) {}
        }

        function editStat(tipo, label, valor, imagem) {
            document.getElementById('editStatTipo').value = tipo;
            document.getElementById('editStatLabel').value = label;
            document.getElementById('editStatValor').value = valor;
            const imageInput = document.getElementById('editStatImage');
            const preview = document.getElementById('editStatImagePreview');
            imageInput.value = imagem || '';
            if (preview) {
                preview.innerHTML = imagem ? `<img src="${assetUrl(imagem)}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">` : '';
            }
            openModal('modalEditStat');
        }

        async function saveStat() {
            const tipo = document.getElementById('editStatTipo').value;
            const label = document.getElementById('editStatLabel').value;
            const valor = document.getElementById('editStatValor').value;
            const imagePath = document.getElementById('editStatImage').value.trim();

            try {
                const payload = {
                    label: label,
                    valor: parseInt(valor)
                };

                if (imagePath !== '') {
                    payload.imagem = imagePath;
                } else {
                    payload.imagem = null;
                }

                const response = await fetch(`${API_URL}/homepage/stats/${tipo}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const result = await parseJsonResponse(response);

                if (result.success) {
                    showAlert('Estat??stica atualizada!');
                    closeModal('modalEditStat');
                    loadStats();
                }
            } catch (error) {
                showAlert('Erro ao atualizar estat??stica', 'error');
            }
        }


        let currentCategoryImage = '';
        let categoriesCatalog = [];

        function normalizeCategoryName(value) {
            return String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim();
        }

        async function loadCategories() {
            try {
                const response = await fetch(`${API_URL}/categorias`);
                const categories = await parseJsonResponse(response);
                categoriesCatalog = Array.isArray(categories) ? categories : [];

                const select = document.getElementById('categoriesSelect');
                const nameList = document.getElementById('categoryNameList');
                let html = '<option value="">Escolha uma categoria...</option>';
                let nameListHtml = '';
                categoriesCatalog.forEach(cat => {
                    html += `<option value="${cat.id_categoria}" data-name="${cat.nome}" data-desc="${cat.descricao}">${cat.nome}</option>`;
                    nameListHtml += `<option value="${cat.nome}"></option>`;
                });
                select.innerHTML = html;
                if (nameList) {
                    nameList.innerHTML = nameListHtml;
                }

                const homepageResponse = await fetch(`${API_URL}/homepage/categories`);
                const homepageCategories = await homepageResponse.json();

                let listHtml = '';
                homepageCategories.forEach(cat => {
                    const statusBadge = cat.ativo ? '<span class="status-badge ativo">✅ Ativo</span>' : '<span class="status-badge inativo">❌ Inativo</span>';
                    const imagemThumb = cat.imagem ?
                        `<img src="${assetUrl(cat.imagem)}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; margin-right: 15px;">` :
                        '';
                    listHtml += `
                        <div class="item">
                            <div class="item-info" style="display: flex; align-items: center;">
                                ${imagemThumb}
                                <div>
                                    <div class="item-title">${cat.nome}</div>
                                    <div class="item-meta">
                                        <span class="item-meta-item">Ordem: ${cat.ordem}</span>
                                        ${statusBadge}
                                    </div>
                                    <div class="item-desc">${cat.descricao || 'Sem descrição'}</div>
                                </div>
                            </div>
                            <div class="item-actions">
                                <button class="btn btn-secondary btn-small" onclick='editCategory(${cat.id_categoria_homepage}, ${cat.ordem}, ${cat.ativo}, ${JSON.stringify(cat.nome || '')}, ${JSON.stringify(cat.imagem || "")})'>Editar</button>
                                <button class="btn btn-danger btn-small" onclick="deleteCategory(${cat.id_categoria_homepage})">Deletar</button>
                            </div>
                        </div>
                    `;
                });
                document.getElementById('categories-list').innerHTML = listHtml || '<p>Nenhuma categoria na homepage</p>';
            } catch (error) {}
        }

        async function addCategory() {
            const categoriesSelect = document.getElementById('categoriesSelect');
            const id_categoria = categoriesSelect.value;
            const imagePath = document.getElementById('categoryImage').value.trim();
            const ordem = document.getElementById('categoryOrder').value;
            const ativo = document.getElementById('categoryActive').checked ? 1 : 0;

            if (!id_categoria) {
                showAlert('Selecione uma categoria', 'error');
                return;
            }

            try {
                const response = await fetch(`${API_URL}/homepage/categories`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_categoria: parseInt(id_categoria),
                        imagem: imagePath || null,
                        ordem: parseInt(ordem),
                        ativo: ativo
                    })
                });
                const result = await parseJsonResponse(response);

                if (result.success) {
                    showAlert('Categoria adicionada!');
                    document.getElementById('categoryImage').value = '';
                    document.getElementById('categoryOrder').value = 0;
                    document.getElementById('categoryActive').checked = true;
                    categoriesSelect.value = '';
                    loadCategories();
                }
            } catch (error) {
                showAlert('Erro ao adicionar categoria', 'error');
            }
        }

        function editCategory(id, ordem, ativo, nome, imagem) {
            document.getElementById('editCategoryId').value = id;
            document.getElementById('editCategoryOrder').value = ordem;
            document.getElementById('editCategoryActive').checked = ativo == 1;
            document.getElementById('editCategoryName').value = nome || '';

            currentCategoryImage = imagem || '';

            const imageLabel = document.getElementById('labelCategoryImage');
            if (imageLabel) {
                imageLabel.textContent = currentCategoryImage ?
                    `Alterar Imagem (atual: ${currentCategoryImage})` :
                    'Alterar Imagem (opcional)';
            }
            document.getElementById('editCategoryImage').value = currentCategoryImage;
            openModal('modalEditCategory');
        }

        async function saveCategory() {
            const id = document.getElementById('editCategoryId').value;
            const nome = document.getElementById('editCategoryName').value.trim();
            const ordem = document.getElementById('editCategoryOrder').value;
            const ativo = document.getElementById('editCategoryActive').checked ? 1 : 0;
            const imagePath = document.getElementById('editCategoryImage').value.trim();

            try {
                const payload = {
                    ordem: parseInt(ordem),
                    ativo: ativo
                };

                if (nome !== '') {
                    const normalizedNome = normalizeCategoryName(nome);
                    const selectedCategory = categoriesCatalog.find(cat =>
                        normalizeCategoryName(cat.nome) === normalizedNome
                    );

                    if (selectedCategory) {
                        payload.id_categoria = parseInt(selectedCategory.id_categoria);
                    } else {
                        payload.nome = nome;
                    }
                }
                if (imagePath !== '') {
                    payload.imagem = imagePath;
                }

                const response = await fetch(`${API_URL}/homepage/categories/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const result = await parseJsonResponse(response);

                if (!response.ok || !result.success) {
                    throw new Error(result.erro || result.mensagem || 'Erro ao atualizar categoria');
                }

                showAlert('Categoria atualizada!');
                closeModal('modalEditCategory');
                loadCategories();
            } catch (error) {
                showAlert(error.message || 'Erro ao atualizar categoria', 'error');
            }
        }

        async function deleteCategory(id) {
            if (confirm('Deletar esta categoria?')) {
                try {
                    const response = await fetch(`${API_URL}/homepage/categories/${id}`, {
                        method: 'DELETE'
                    });
                    const result = await parseJsonResponse(response);

                    if (result.success) {
                        showAlert('Categoria deletada!');
                        loadCategories();
                    }
                } catch (error) {
                    showAlert('Erro ao deletar categoria', 'error');
                }
            }
        }
        async function loadTestimonials() {
            try {
                const response = await fetch(`${API_URL}/homepage/testimonials`);
                const testimonials = await parseJsonResponse(response);

                let html = '';
                testimonials.forEach(test => {
                    const statusBadge = test.ativo ? '<span class="status-badge ativo">Ativo</span>' : '<span class="status-badge inativo">? Inativo</span>';
                    const mediaPath = (test.foto || test.midia || '').trim();
                    const videoDepoimento = isVideoMedia(mediaPath);
                    const mediaPreview = mediaPath ?
                        (videoDepoimento ?
                            `<video src="${assetUrl(mediaPath)}" style="width: 72px; height: 72px; object-fit: cover; border-radius: 12px; margin-right: 15px; background: #111;" muted playsinline></video>` :
                            `<img src="${assetUrl(mediaPath)}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 50%; margin-right: 15px;">`) :
                        '';
                    const titulo = (test.nome_aluno || '').trim() || (videoDepoimento ? 'Depoimento em vídeo' : 'Aluno sem nome');
                    const curso = (test.curso || '').trim();
                    const tipoBadge = videoDepoimento ?
                        '<span class="item-meta-item" style="background:#111827;color:#fff;padding:2px 8px;border-radius:999px;">Vídeo</span>' :
                        '<span class="item-meta-item" style="background:#e5e7eb;color:#1f2937;padding:2px 8px;border-radius:999px;">Imagem</span>';

                    html += `
                        <div class="item">
                            <div class="item-info" style="display: flex; align-items: center;">
                                ${mediaPreview}
                                <div>
                                    <div class="item-title">${escapeHtml(titulo)}</div>
                                    <div class="item-meta">
                                        ${curso ? `<span class="item-meta-item">Curso: ${escapeHtml(curso)}</span>` : ''}
                                        ${tipoBadge}
                                        <span class="item-meta-item">Ordem: ${test.ordem}</span>
                                        ${statusBadge}
                                    </div>
                                </div>
                            </div>
                            <div class="item-actions">
                                <button class="btn btn-secondary btn-small" onclick="editTestimonial(${test.id_testimonial}, '${escapeForInlineJs(mediaPath)}', '${escapeForInlineJs(test.nome_aluno)}', '${escapeForInlineJs(test.curso)}', '${escapeForInlineJs(test.mensagem)}', ${test.ordem}, ${test.ativo})">Editar</button>
                                <button class="btn btn-danger btn-small" onclick="deleteTestimonial(${test.id_testimonial})">Deletar</button>
                            </div>
                        </div>
                    `;
                });
                document.getElementById('testimonials-list').innerHTML = html || '<p>Nenhum depoimento cadastrado</p>';
            } catch (error) {}
        }

        async function addTestimonial() {
            const mediaPath = document.getElementById('testimonialPhoto').value.trim();
            const nome = document.getElementById('testimonialName').value.trim();
            const curso = document.getElementById('testimonialCourse').value.trim();
            const mensagem = document.getElementById('testimonialMessage').value.trim();
            const ordem = document.getElementById('testimonialOrder').value;
            const ativo = document.getElementById('testimonialActive').checked ? 1 : 0;
            const videoDepoimento = isVideoMedia(mediaPath);

            if (!mediaPath) {
                showAlert('Selecione uma imagem ou vídeo', 'error');
                return;
            }

            if (!videoDepoimento && (!nome || !curso || !mensagem)) {
                showAlert('Para depoimento com imagem, preencha nome, curso e mensagem', 'error');
                return;
            }

            try {
                const response = await fetch(`${API_URL}/homepage/testimonials`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        midia: mediaPath,
                        foto: mediaPath,
                        nome_aluno: nome,
                        curso: curso,
                        mensagem: mensagem,
                        ordem: parseInt(ordem),
                        ativo: ativo
                    })
                });
                const result = await parseJsonResponse(response);

                if (result.success) {
                    showAlert('Depoimento adicionado!');
                    document.getElementById('testimonialPhoto').value = '';
                    document.getElementById('testimonialName').value = '';
                    document.getElementById('testimonialCourse').value = '';
                    document.getElementById('testimonialMessage').value = '';
                    document.getElementById('testimonialOrder').value = 0;
                    document.getElementById('testimonialActive').checked = true;
                    loadTestimonials();
                }
            } catch (error) {
                showAlert('Erro ao adicionar depoimento', 'error');
            }
        }

        function editTestimonial(id, midia, nome, curso, mensagem, ordem, ativo) {
            document.getElementById('editTestimonialId').value = id;
            document.getElementById('editTestimonialMedia').value = midia || '';
            document.getElementById('editTestimonialName').value = nome;
            document.getElementById('editTestimonialCourse').value = curso;
            document.getElementById('editTestimonialMessage').value = mensagem;
            document.getElementById('editTestimonialOrder').value = ordem;
            document.getElementById('editTestimonialActive').checked = ativo == 1;
            openModal('modalEditTestimonial');
        }

        async function saveTestimonial() {
            const id = document.getElementById('editTestimonialId').value;
            const midia = document.getElementById('editTestimonialMedia').value.trim();
            const nome = document.getElementById('editTestimonialName').value.trim();
            const curso = document.getElementById('editTestimonialCourse').value.trim();
            const mensagem = document.getElementById('editTestimonialMessage').value.trim();
            const ordem = document.getElementById('editTestimonialOrder').value;
            const ativo = document.getElementById('editTestimonialActive').checked ? 1 : 0;
            const videoDepoimento = isVideoMedia(midia);

            if (!midia) {
                showAlert('Selecione uma imagem ou vídeo', 'error');
                return;
            }

            if (!videoDepoimento && (!nome || !curso || !mensagem)) {
                showAlert('Para depoimento com imagem, preencha nome, curso e mensagem', 'error');
                return;
            }

            try {
                const response = await fetch(`${API_URL}/homepage/testimonials/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        midia: midia,
                        foto: midia,
                        nome_aluno: nome,
                        curso: curso,
                        mensagem: mensagem,
                        ordem: parseInt(ordem),
                        ativo: ativo
                    })
                });
                const result = await parseJsonResponse(response);

                if (result.success) {
                    showAlert('Depoimento atualizado!');
                    closeModal('modalEditTestimonial');
                    loadTestimonials();
                }
            } catch (error) {
                showAlert('Erro ao atualizar depoimento', 'error');
            }
        }


        async function deleteTestimonial(id) {
            if (confirm('Deletar este depoimento?')) {
                try {
                    const response = await fetch(`${API_URL}/homepage/testimonials/${id}`, {
                        method: 'DELETE'
                    });
                    const result = await parseJsonResponse(response);

                    if (result.success) {
                        showAlert('Depoimento deletado!');
                        loadTestimonials();
                    }
                } catch (error) {
                    showAlert('Erro ao deletar depoimento', 'error');
                }
            }
        }


        async function loadFAQ() {
            try {
                const response = await fetch(`${API_URL}/homepage/faq`);
                const faqItems = await parseJsonResponse(response);

                let html = '';
                faqItems.forEach(item => {
                    const statusBadge = item.ativo ? '<span class="status-badge ativo">✅ Ativo</span>' : '<span class="status-badge inativo">❌ Inativo</span>';
                    html += `
                        <div class="item">
                            <div class="item-info">
                                <div class="item-title">${item.pergunta}</div>
                                <div class="item-meta">
                                    <span class="item-meta-item">Ordem: ${item.ordem}</span>
                                    ${statusBadge}
                                </div>
                                <div class="item-desc">${item.resposta}</div>
                            </div>
                            <div class="item-actions">
                                <button class="btn btn-secondary btn-small" onclick="editFAQ(${item.id_faq}, '${item.pergunta.replace(/'/g, "\\'")}', '${item.resposta.replace(/'/g, "\\'")}', ${item.ordem}, ${item.ativo})">Editar</button>
                                <button class="btn btn-danger btn-small" onclick="deleteFAQ(${item.id_faq})">Deletar</button>
                            </div>
                        </div>
                    `;
                });
                document.getElementById('faq-list').innerHTML = html || '<p>Nenhuma FAQ cadastrada</p>';
            } catch (error) {}
        }

        async function addFAQ() {
            const pergunta = document.getElementById('faqQuestion').value;
            const resposta = document.getElementById('faqAnswer').value;
            const ordem = document.getElementById('faqOrder').value;
            const ativo = document.getElementById('faqActive').checked ? 1 : 0;

            if (!pergunta || !resposta) {
                showAlert('Preencha pergunta e resposta', 'error');
                return;
            }

            try {
                const response = await fetch(`${API_URL}/homepage/faq`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        pergunta: pergunta,
                        resposta: resposta,
                        ordem: parseInt(ordem),
                        ativo: ativo
                    })
                });
                const result = await parseJsonResponse(response);

                if (result.success) {
                    showAlert('FAQ adicionada!');
                    document.getElementById('faqQuestion').value = '';
                    document.getElementById('faqAnswer').value = '';
                    document.getElementById('faqOrder').value = 0;
                    document.getElementById('faqActive').checked = true;
                    loadFAQ();
                }
            } catch (error) {
                showAlert('Erro ao adicionar FAQ', 'error');
            }
        }

        function editFAQ(id, pergunta, resposta, ordem, ativo) {
            document.getElementById('editFAQId').value = id;
            document.getElementById('editFAQQuestion').value = pergunta;
            document.getElementById('editFAQAnswer').value = resposta;
            document.getElementById('editFAQOrder').value = ordem;
            document.getElementById('editFAQActive').checked = ativo == 1;
            openModal('modalEditFAQ');
        }

        async function saveFAQ() {
            const id = document.getElementById('editFAQId').value;
            const pergunta = document.getElementById('editFAQQuestion').value;
            const resposta = document.getElementById('editFAQAnswer').value;
            const ordem = document.getElementById('editFAQOrder').value;
            const ativo = document.getElementById('editFAQActive').checked ? 1 : 0;

            try {
                const response = await fetch(`${API_URL}/homepage/faq/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        pergunta: pergunta,
                        resposta: resposta,
                        ordem: parseInt(ordem),
                        ativo: ativo
                    })
                });
                const result = await parseJsonResponse(response);

                if (result.success) {
                    showAlert('FAQ atualizada!');
                    closeModal('modalEditFAQ');
                    loadFAQ();
                }
            } catch (error) {
                showAlert('Erro ao atualizar FAQ', 'error');
            }
        }

        async function deleteFAQ(id) {
            if (confirm('Deletar esta FAQ?')) {
                try {
                    const response = await fetch(`${API_URL}/homepage/faq/${id}`, {
                        method: 'DELETE'
                    });
                    const result = await parseJsonResponse(response);

                    if (result.success) {
                        showAlert('FAQ deletada!');
                        loadFAQ();
                    }
                } catch (error) {
                    showAlert('Erro ao deletar FAQ', 'error');
                }
            }
        }

        // ==================== VALORES GRADE PERSONALIZADA ====================
        async function loadValoresGrade() {
            try {
                const response = await fetch(`${API_URL}/configuracoes`);
                const configs = await parseJsonResponse(response);

                configs.forEach(config => {
                    if (config.chave === 'VALOR_MENSAL_PRESENCIAL_PADRAO') {
                        document.getElementById('valorMensalPresencial').value = config.valor;
                    }
                    if (config.chave === 'VALOR_MENSAL_EAD_PADRAO') {
                        document.getElementById('valorMensalEad').value = config.valor;
                    }
                });
            } catch (error) {}
        }

        async function saveValoresGrade() {
            const presencial = document.getElementById('valorMensalPresencial').value;
            const ead = document.getElementById('valorMensalEad').value;

            if (!presencial || !ead) {
                showAlert('Preencha todos os campos!', 'error');
                return;
            }

            try {
                // Salvar valor presencial
                await fetch(`${API_URL}/configuracoes`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        chave: 'VALOR_MENSAL_PRESENCIAL_PADRAO',
                        valor: presencial
                    })
                });

                // Salvar valor EAD
                await fetch(`${API_URL}/configuracoes`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        chave: 'VALOR_MENSAL_EAD_PADRAO',
                        valor: ead
                    })
                });

                showAlert('Valores salvos com sucesso!');
            } catch (error) {
                showAlert('Erro ao salvar valores', 'error');
            }
        }

        // Carregar banners ao inicializar
        window.addEventListener('load', () => {
            loadBanners();
        });
    </script>

    <datalist id="homepageImageAssets">
        <?php foreach ($imageAssets as $path): ?>
            <option value="<?= htmlspecialchars($path) ?>"></option>
        <?php endforeach; ?>
    </datalist>

</body>

</html>
