<?php

class GradeController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    
    public function index()
    {
        $isHomeRequest = isset($_GET['home']) && (int)$_GET['home'] === 1;
        if ($isHomeRequest) {
            $this->indexHome();
            return;
        }

        $categoriaRaw = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
        $hasCategoria = $categoriaRaw !== '';
        $isNumeric = ctype_digit($categoriaRaw);
        $nome = isset($_GET['nome']) ? trim($_GET['nome']) : '';
        $ativoRaw = $_GET['ativo'] ?? '';

        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 20;
        $offset = ($page - 1) * $perPage;
        $whereParts = [];
        $params = [];

        if ($hasCategoria) {
            $whereParts[] = "(
                (:catId IS NOT NULL AND g.id_categoria = :catId)
                OR (:catSlug IS NOT NULL AND LOWER(cat.slug) = :catSlug)
            )";
            $params[':catId'] = $isNumeric ? (int)$categoriaRaw : null;
            $params[':catSlug'] = !$isNumeric ? strtolower($categoriaRaw) : null;
        }

        if ($nome !== '') {
            $whereParts[] = 'g.nome LIKE :nome';
            $params[':nome'] = '%' . $nome . '%';
        }

        if ($ativoRaw !== '') {
            $whereParts[] = 'g.ativo = :ativo';
            $params[':ativo'] = (int) $ativoRaw;
        }

        $where = $whereParts ? ' WHERE ' . implode(' AND ', $whereParts) : '';

        $sqlCount = "
            SELECT COUNT(*)
            FROM grade g
            LEFT JOIN categoria cat ON cat.id_categoria = g.id_categoria
            $where
        ";

        $stmtCount = $this->db->prepare($sqlCount);
        foreach ($params as $key => $value) {
            if ($value === null) {
                $stmtCount->bindValue($key, null, PDO::PARAM_NULL);
            } elseif (is_int($value)) {
                $stmtCount->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmtCount->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        $stmtCount->execute();
        $total = (int)$stmtCount->fetchColumn();

        $sql = "
            SELECT g.*, cat.nome AS categoria_nome, cat.slug AS categoria_slug
            FROM grade g
            LEFT JOIN categoria cat ON cat.id_categoria = g.id_categoria
            $where
            ORDER BY g.nome
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            if ($value === null) {
                $stmt->bindValue($key, null, PDO::PARAM_NULL);
            } elseif (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        
        foreach ($grades as &$grade) {
            $grade = $this->aplicarValoresPadraoAGrade($grade);
        }

        $totalPages = ceil($total / $perPage);

        echo json_encode([
            'success' => true,
            'data' => $grades,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages
        ]);
    }

    private function indexHome(): void
    {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8;
        $limit = max(1, min(12, $limit));

        $sql = "
            SELECT
                g.id_grade,
                g.nome,
                g.slug,
                g.descricao_curta,
                g.imagem_card,
                COALESCE(g.preco_avista, g.mensalidade_valor) AS preco,
                hgc.ordem
            FROM homepage_grades_carousel hgc
            INNER JOIN grade g ON g.id_grade = hgc.id_grade
            WHERE hgc.ativo = 1 AND g.ativo = 1
            ORDER BY hgc.ordem ASC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($grades as &$grade) {
            $grade['id_grade'] = (int)($grade['id_grade'] ?? 0);
            $grade['ordem'] = (int)($grade['ordem'] ?? 0);
            $grade['nome'] = (string)($grade['nome'] ?? '');
            $grade['slug'] = (string)($grade['slug'] ?? '');
            $grade['descricao_curta'] = (string)($grade['descricao_curta'] ?? '');
            $grade['imagem_card'] = (string)($grade['imagem_card'] ?? '');
            $grade['preco'] = $grade['preco'] !== null ? (float)$grade['preco'] : null;
        }

        $payload = [
            'success' => true,
            'home' => true,
            'limit' => $limit,
            'data' => $grades,
        ];

        $json = json_encode($payload);
        if ($json === false) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao serializar grades da home']);
            return;
        }

        $etag = '"' . hash('sha256', $json) . '"';
        header("Cache-Control: public, max-age=120, stale-while-revalidate=300");
        header("ETag: $etag");

        $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if ($ifNoneMatch !== '') {
            $clientEtags = array_map('trim', explode(',', $ifNoneMatch));
            if (in_array($etag, $clientEtags, true) || in_array('*', $clientEtags, true)) {
                http_response_code(304);
                return;
            }
        }

        echo $json;
    }

    
    public function buscarPorId(int $id): void
    {
        $sql = "SELECT * FROM grade WHERE id_grade = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $grade = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$grade) {
            http_response_code(404);
            echo json_encode(['erro' => 'Grade nÃ£o encontrada']);
            return;
        }

        
        $grade = $this->aplicarValoresPadraoAGrade($grade);

        echo json_encode($grade);
    }

    
    public function show(string $slug): void
    {
        $sql = "SELECT * FROM grade WHERE slug = :slug AND ativo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':slug', $slug);
        $stmt->execute();

        $grade = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$grade) {
            http_response_code(404);
            echo json_encode(['erro' => 'Grade nÃ£o encontrada']);
            return;
        }

        $sqlCursos = "
            SELECT
                gc.ordem,
                c.id_curso,
                c.nome,
                c.slug,
                COALESCE(gc.horas_personalizadas, c.horas) AS horas,
                c.descricao_curta,
                c.pdf_conteudo,
                c.link_aula_demo
            FROM grade_curso gc
            JOIN curso c ON c.id_curso = gc.id_curso
            WHERE gc.id_grade = :id_grade
            ORDER BY gc.ordem ASC
        ";
        $stmt2 = $this->db->prepare($sqlCursos);
        $stmt2->bindValue(':id_grade', (int)$grade['id_grade'], PDO::PARAM_INT);
        $stmt2->execute();

        $cursos = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        $grade['cursos'] = $cursos;

        $cargaTotal = 0;
        foreach ($cursos as $curso) {
            $cargaTotal += (int)($curso['horas'] ?? 0);
        }
        $grade['carga_horaria_total'] = $cargaTotal;

        
        if (isset($grade['preco_avista'])) {
            $grade['preco_avista'] = $grade['preco_avista'] !== null ? (float)$grade['preco_avista'] : null;
        }
        if (isset($grade['mensalidade_valor'])) {
            $grade['mensalidade_valor'] = $grade['mensalidade_valor'] !== null ? (float)$grade['mensalidade_valor'] : null;
        }
        if (isset($grade['matricula_valor'])) {
            $grade['matricula_valor'] = $grade['matricula_valor'] !== null ? (float)$grade['matricula_valor'] : null;
        }
        if (isset($grade['parcelas_maximas'])) {
            $grade['parcelas_maximas'] = $grade['parcelas_maximas'] !== null ? (int)$grade['parcelas_maximas'] : null;
        }

        
        if (isset($grade['valor_mensal_presencial'])) {
            $grade['valor_mensal_presencial'] = $grade['valor_mensal_presencial'] !== null ? (float)$grade['valor_mensal_presencial'] : null;
        }
        if (isset($grade['valor_mensal_ead'])) {
            $grade['valor_mensal_ead'] = $grade['valor_mensal_ead'] !== null ? (float)$grade['valor_mensal_ead'] : null;
        }
        if (isset($grade['valor_avista_presencial'])) {
            $grade['valor_avista_presencial'] = $grade['valor_avista_presencial'] !== null ? (float)$grade['valor_avista_presencial'] : null;
        }
        if (isset($grade['valor_avista_ead'])) {
            $grade['valor_avista_ead'] = $grade['valor_avista_ead'] !== null ? (float)$grade['valor_avista_ead'] : null;
        }
        if (isset($grade['valor_matricula'])) {
            $grade['valor_matricula'] = $grade['valor_matricula'] !== null ? (float)$grade['valor_matricula'] : null;
        }
        if (isset($grade['percentual_parcelamento'])) {
            $grade['percentual_parcelamento'] = $grade['percentual_parcelamento'] !== null ? (float)$grade['percentual_parcelamento'] : null;
        }

        
        $grade = $this->aplicarValoresPadraoAGrade($grade);

        echo json_encode($grade);
    }

    
    public function listarCursos(int $idGrade): void
    {
        $sql = "
            SELECT
                gc.ordem,
                gc.horas_personalizadas,
                c.id_curso,
                c.nome,
                c.slug,
                c.horas,
                COALESCE(gc.horas_personalizadas, c.horas) AS horas_final,
                c.descricao_curta
            FROM grade_curso gc
            JOIN curso c ON c.id_curso = gc.id_curso
            WHERE gc.id_grade = :id_grade
            ORDER BY gc.ordem ASC
        ";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_grade', $idGrade, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($result ?: []);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao listar cursos', 'mensagem' => $e->getMessage()]);
        }
    }

    
    public function create(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        if (empty($data['nome'])) {
            http_response_code(400);
            echo json_encode(['erro' => 'Nome Ã© obrigatÃ³rio']);
            return;
        }

        
        $getConfig = function (string $key, $default = null) {
            $stmt = $this->db->prepare("SELECT valor FROM configuracao WHERE chave = :chave LIMIT 1");
            $stmt->execute([':chave' => $key]);
            $val = $stmt->fetchColumn();
            return ($val === false) ? $default : $val;
        };

        $nome = trim($data['nome']);
        $slug = trim((string)($data['slug'] ?? ''));
        if ($slug === '') $slug = $this->gerarSlug($nome);

        $mesesDuracao = (int)($data['meses_duracao'] ?? ($data['meses'] ?? 0));
        $descricaoCurta = $data['descricao_curta'] ?? '';
        $descricaoLonga = $data['descricao_longa_md'] ?? null;
        $idCategoriaInformada = array_key_exists('id_categoria', $data) || array_key_exists('categoria', $data);
        $idCategoria = null;
        if (array_key_exists('id_categoria', $data)) {
            $idCategoria = ($data['id_categoria'] === null || $data['id_categoria'] === '') ? null : (int)$data['id_categoria'];
        } elseif (array_key_exists('categoria', $data) && is_numeric($data['categoria'])) {
            $idCategoria = (int)$data['categoria'];
        }
        if (!$idCategoriaInformada) {
            $stmtCategoriaAtual = $this->db->prepare("SELECT id_categoria FROM grade WHERE id_grade = :id LIMIT 1");
            $stmtCategoriaAtual->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtCategoriaAtual->execute();
            $idCategoriaAtual = $stmtCategoriaAtual->fetchColumn();
            $idCategoria = ($idCategoriaAtual === false || $idCategoriaAtual === null) ? null : (int)$idCategoriaAtual;
        }

        $ativo = isset($data['ativo']) ? (int)$data['ativo'] : 1;

        
        $vendaMensalFlag = isset($data['venda_mensal']) ? (int)$data['venda_mensal'] : null;
        $vendaAvistaFlag = isset($data['venda_avista']) ? (int)$data['venda_avista'] : null;

        
        $defaultValorMensalPresencial = $getConfig('VALOR_MENSAL_PRESENCIAL_PADRAO', null);
        $defaultValorMensalEad = $getConfig('VALOR_MENSAL_EAD_PADRAO', null);
        $defaultValorMatricula = $getConfig('VALOR_MATRICULA_PADRAO', null);
        $defaultPercentualParcelamento = $getConfig('PERCENTUAL_PARCELAMENTO_PADRAO', null);

        
        $vendeMensal = $vendaMensalFlag !== null ? $vendaMensalFlag : 1; 
        $valorMensalPresencial = $data['valor_mensal_presencial'] ?? null;
        $valorMensalEad = $data['valor_mensal_ead'] ?? null;
        $valorAvistaPresencial = $data['valor_avista_presencial'] ?? null;
        $valorAvistaEad = $data['valor_avista_ead'] ?? null;
        $valorMatricula = $data['valor_matricula'] ?? null;

        $imagemCard = $data['imagem_card'] ?? null;
        $imagemDetalhe = $data['imagem_detalhe'] ?? null;

        
        $tipoVenda = $data['tipo_venda'] ?? null;
        $precoAvista = $data['preco_avista'] ?? null;
        $parcelasMaximas = $data['parcelas_maximas'] ?? null;
        $percentualParcelamento = $data['percentual_parcelamento'] ?? ($data['taxa_parcelamento'] ?? null);
        $mensalidadeValor = $data['mensalidade_valor'] ?? null;
        $matriculaValor = $data['matricula_valor'] ?? null;

        
        if ($valorMensalPresencial === null || $valorMensalPresencial === '') {
            $valorMensalPresencial = $defaultValorMensalPresencial;
        }
        if ($valorMensalEad === null || $valorMensalEad === '') {
            $valorMensalEad = $defaultValorMensalEad;
        }
        if ($valorMatricula === null || $valorMatricula === '') {
            $valorMatricula = $defaultValorMatricula;
        }
        if ($percentualParcelamento === null || $percentualParcelamento === '') {
            $percentualParcelamento = $defaultPercentualParcelamento;
        }

        
        if ($tipoVenda === null) {
            $avistaAtivo = $vendaAvistaFlag !== null ? $vendaAvistaFlag : 1; 
            if ($avistaAtivo && $vendeMensal) {
                $tipoVenda = 'AVISTA_PARCELADO'; 
            } elseif ($avistaAtivo && !$vendeMensal) {
                $tipoVenda = 'AVISTA_PARCELADO';
            } elseif (!$avistaAtivo && $vendeMensal) {
                $tipoVenda = 'MENSAL';
            } else {
                $tipoVenda = 'AVISTA_PARCELADO';
            }
        }

        $sql = "
            INSERT INTO grade (
                nome, slug, id_categoria, meses_duracao, descricao_curta, descricao_longa_md,
                vende_mensal,
                valor_mensal_presencial, valor_mensal_ead,
                valor_avista_presencial, valor_avista_ead,
                valor_matricula,
                imagem_card, imagem_detalhe,
                tipo_venda, preco_avista, parcelas_maximas, percentual_parcelamento, mensalidade_valor, matricula_valor,
                usa_valores_padrao, usa_percentual_padrao, ativo
            ) VALUES (
                :nome, :slug, :id_categoria, :meses_duracao, :descricao_curta, :descricao_longa_md,
                :vende_mensal,
                :valor_mensal_presencial, :valor_mensal_ead,
                :valor_avista_presencial, :valor_avista_ead,
                :valor_matricula,
                :imagem_card, :imagem_detalhe,
                :tipo_venda, :preco_avista, :parcelas_maximas, :percentual_parcelamento, :mensalidade_valor, :matricula_valor,
                :usa_valores_padrao, :usa_percentual_padrao, :ativo
            )
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':nome', $nome);
            $stmt->bindValue(':slug', $slug);
            $stmt->bindValue(':id_categoria', $idCategoria, is_null($idCategoria) ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':meses_duracao', $mesesDuracao, PDO::PARAM_INT);
            $stmt->bindValue(':descricao_curta', $descricaoCurta);
            $stmt->bindValue(':descricao_longa_md', $descricaoLonga);

            $stmt->bindValue(':vende_mensal', $vendeMensal, PDO::PARAM_INT);
            $stmt->bindValue(':valor_mensal_presencial', $valorMensalPresencial);
            $stmt->bindValue(':valor_mensal_ead', $valorMensalEad);
            $stmt->bindValue(':valor_avista_presencial', $valorAvistaPresencial);
            $stmt->bindValue(':valor_avista_ead', $valorAvistaEad);
            $stmt->bindValue(':valor_matricula', $valorMatricula);

            $stmt->bindValue(':imagem_card', $imagemCard);
            $stmt->bindValue(':imagem_detalhe', $imagemDetalhe);

            $stmt->bindValue(':tipo_venda', $tipoVenda);
            $stmt->bindValue(':preco_avista', $precoAvista);
            $stmt->bindValue(':parcelas_maximas', $parcelasMaximas, is_null($parcelasMaximas) ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':percentual_parcelamento', $percentualParcelamento);
            $stmt->bindValue(':mensalidade_valor', $mensalidadeValor);
            $stmt->bindValue(':matricula_valor', $matriculaValor);

            $stmt->bindValue(':usa_valores_padrao', isset($data['usa_valores_padrao']) ? (int)$data['usa_valores_padrao'] : 1, PDO::PARAM_INT);

            $stmt->bindValue(':usa_percentual_padrao', isset($data['usa_percentual_padrao']) ? (int)$data['usa_percentual_padrao'] : 1, PDO::PARAM_INT);

            $stmt->bindValue(':ativo', $ativo, PDO::PARAM_INT);

            $stmt->execute();
            echo json_encode([
                'sucesso' => true,
                'id_grade' => (int)$this->db->lastInsertId(),
                'mensagem' => 'Grade criada com sucesso'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao criar grade', 'mensagem' => $e->getMessage()]);
        }
    }

    
    public function update(int $id): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        if (empty($data['nome'])) {
            http_response_code(400);
            echo json_encode(['erro' => 'Nome Ã© obrigatÃ³rio']);
            return;
        }

        $nome = trim($data['nome']);
        $slug = trim((string)($data['slug'] ?? ''));
        if ($slug === '') $slug = $this->gerarSlug($nome);

        $mesesDuracao = (int)($data['meses_duracao'] ?? ($data['meses'] ?? 0));
        $descricaoCurta = $data['descricao_curta'] ?? '';
        $descricaoLonga = $data['descricao_longa_md'] ?? null;
        $idCategoria = null;
        if (array_key_exists('id_categoria', $data)) {
            $idCategoria = ($data['id_categoria'] === null || $data['id_categoria'] === '') ? null : (int)$data['id_categoria'];
        } elseif (array_key_exists('categoria', $data) && is_numeric($data['categoria'])) {
            $idCategoria = (int)$data['categoria'];
        }

        $ativo = isset($data['ativo']) ? (int)$data['ativo'] : 1;

        $vendaMensalFlag = isset($data['venda_mensal']) ? (int)$data['venda_mensal'] : null;
        $vendaAvistaFlag = isset($data['venda_avista']) ? (int)$data['venda_avista'] : null;
        $vendeMensal = $vendaMensalFlag !== null ? $vendaMensalFlag : (isset($data['vende_mensal']) ? (int)$data['vende_mensal'] : 1);
        $valorMensalPresencial = $data['valor_mensal_presencial'] ?? null;
        $valorMensalEad = $data['valor_mensal_ead'] ?? null;
        $valorAvistaPresencial = $data['valor_avista_presencial'] ?? null;
        $valorAvistaEad = $data['valor_avista_ead'] ?? null;
        $valorMatricula = $data['valor_matricula'] ?? null;

        $imagemCard = $data['imagem_card'] ?? null;
        $imagemDetalhe = $data['imagem_detalhe'] ?? null;

        $tipoVenda = $data['tipo_venda'] ?? null;
        if ($tipoVenda === null) {
            $avistaAtivo = $vendaAvistaFlag !== null ? $vendaAvistaFlag : 1;
            if ($avistaAtivo && $vendeMensal) {
                $tipoVenda = 'AVISTA_PARCELADO';
            } elseif ($avistaAtivo && !$vendeMensal) {
                $tipoVenda = 'AVISTA_PARCELADO';
            } else {
                $tipoVenda = 'MENSAL';
            }
        }
        $precoAvista = $data['preco_avista'] ?? null;
        $parcelasMaximas = $data['parcelas_maximas'] ?? null;
        $percentualParcelamento = $data['percentual_parcelamento'] ?? ($data['taxa_parcelamento'] ?? null);
        $mensalidadeValor = $data['mensalidade_valor'] ?? null;
        $matriculaValor = $data['matricula_valor'] ?? null;

        $sql = "
            UPDATE grade SET
                nome = :nome,
                slug = :slug,
                id_categoria = :id_categoria,
                meses_duracao = :meses_duracao,
                descricao_curta = :descricao_curta,
                descricao_longa_md = :descricao_longa_md,

                vende_mensal = :vende_mensal,
                valor_mensal_presencial = :valor_mensal_presencial,
                valor_mensal_ead = :valor_mensal_ead,
                valor_avista_presencial = :valor_avista_presencial,
                valor_avista_ead = :valor_avista_ead,
                valor_matricula = :valor_matricula,

                imagem_card = :imagem_card,
                imagem_detalhe = :imagem_detalhe,

                tipo_venda = :tipo_venda,
                preco_avista = :preco_avista,
                parcelas_maximas = :parcelas_maximas,
                percentual_parcelamento = :percentual_parcelamento,
                mensalidade_valor = :mensalidade_valor,
                matricula_valor = :matricula_valor,

                usa_valores_padrao = :usa_valores_padrao,
                usa_percentual_padrao = :usa_percentual_padrao,
                ativo = :ativo
            WHERE id_grade = :id
        ";

        try {
            $stmt = $this->db->prepare($sql);

            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':nome', $nome);
            $stmt->bindValue(':slug', $slug);
            $stmt->bindValue(':id_categoria', $idCategoria, is_null($idCategoria) ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':meses_duracao', $mesesDuracao, PDO::PARAM_INT);
            $stmt->bindValue(':descricao_curta', $descricaoCurta);
            $stmt->bindValue(':descricao_longa_md', $descricaoLonga);

            $stmt->bindValue(':vende_mensal', $vendeMensal, PDO::PARAM_INT);
            $stmt->bindValue(':valor_mensal_presencial', $valorMensalPresencial);
            $stmt->bindValue(':valor_mensal_ead', $valorMensalEad);
            $stmt->bindValue(':valor_avista_presencial', $valorAvistaPresencial);
            $stmt->bindValue(':valor_avista_ead', $valorAvistaEad);
            $stmt->bindValue(':valor_matricula', $valorMatricula);

            $stmt->bindValue(':imagem_card', $imagemCard);
            $stmt->bindValue(':imagem_detalhe', $imagemDetalhe);

            $stmt->bindValue(':tipo_venda', $tipoVenda);
            $stmt->bindValue(':preco_avista', $precoAvista);
            $stmt->bindValue(':parcelas_maximas', $parcelasMaximas, is_null($parcelasMaximas) ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':percentual_parcelamento', $percentualParcelamento);
            $stmt->bindValue(':mensalidade_valor', $mensalidadeValor);
            $stmt->bindValue(':matricula_valor', $matriculaValor);

            $stmt->bindValue(':usa_valores_padrao', isset($data['usa_valores_padrao']) ? (int)$data['usa_valores_padrao'] : 1, PDO::PARAM_INT);

            $stmt->bindValue(':usa_percentual_padrao', isset($data['usa_percentual_padrao']) ? (int)$data['usa_percentual_padrao'] : 1, PDO::PARAM_INT);

            $stmt->bindValue(':ativo', $ativo, PDO::PARAM_INT);

            $stmt->execute();
            echo json_encode(['sucesso' => true, 'mensagem' => 'Grade atualizada']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao atualizar', 'mensagem' => $e->getMessage()]);
        }
    }
    public function destroy(int $id): void
    {
        try {
            $this->db->beginTransaction();

            $stmtCheck = $this->db->prepare('SELECT COUNT(*) as total FROM pedidos WHERE id_grade = :id');
            $stmtCheck->bindValue(':id', $id, PDO::PARAM_INT);

            $checkExecuted = false;
            $totalPedidos = 0;

            try {
                $stmtCheck->execute();
                $checkExecuted = true;
                $resultado = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                $totalPedidos = (int)($resultado['total'] ?? 0);
            } catch (PDOException $e) {
                $checkExecuted = false;
            }

            if ($checkExecuted && $totalPedidos > 0) {
                $this->db->rollBack();
                http_response_code(400);
                echo json_encode([
                    'erro' => 'NÃ£o pode ser excluÃ­do por pertencer a ' . $totalPedidos . ' pedido(s)',
                    'mensagem' => 'Esta grade estÃ¡ vinculada a ' . $totalPedidos . ' pedido(s) e nÃ£o pode ser excluÃ­da.'
                ]);
                return;
            }

            // Evita erro de FK quando a grade estiver no carrossel da homepage.
            $stmt = $this->db->prepare('DELETE FROM homepage_grades_carousel WHERE id_grade = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->db->prepare('DELETE FROM grade_curso WHERE id_grade = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->db->prepare('DELETE FROM grade WHERE id_grade = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->db->commit();

            http_response_code(200);
            echo json_encode(['sucesso' => true, 'mensagem' => 'Grade excluÃ­da']);
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao excluir', 'mensagem' => $e->getMessage()]);
        }
    }

    
    public function adicionarCurso(int $idGrade): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        $idCurso = (int)($data['id_curso'] ?? 0);
        if ($idCurso <= 0) {
            http_response_code(400);
            echo json_encode(['erro' => 'ID do curso Ã© obrigatÃ³rio']);
            return;
        }

        
        $horasPers = $data['horas_personalizadas'] ?? ($data['carga_horaria_custom'] ?? null);
        $horasPers = ($horasPers === '' || $horasPers === null) ? null : (int)$horasPers;

        try {
            $stmt = $this->db->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 AS nova_ordem FROM grade_curso WHERE id_grade = :id_grade');
            $stmt->bindValue(':id_grade', $idGrade, PDO::PARAM_INT);
            $stmt->execute();
            $ordem = (int)($stmt->fetch(PDO::FETCH_ASSOC)['nova_ordem'] ?? 1);

            $sql = "INSERT INTO grade_curso (id_grade, id_curso, ordem, horas_personalizadas)
                    VALUES (:id_grade, :id_curso, :ordem, :horas_personalizadas)";
            $stmt2 = $this->db->prepare($sql);
            $stmt2->bindValue(':id_grade', $idGrade, PDO::PARAM_INT);
            $stmt2->bindValue(':id_curso', $idCurso, PDO::PARAM_INT);
            $stmt2->bindValue(':ordem', $ordem, PDO::PARAM_INT);
            $stmt2->bindValue(':horas_personalizadas', $horasPers, is_null($horasPers) ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt2->execute();

            http_response_code(201);
            echo json_encode(['sucesso' => true, 'mensagem' => 'Curso adicionado']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao adicionar', 'mensagem' => $e->getMessage()]);
        }
    }

    
    public function atualizarCurso(int $idGrade, int $idCurso): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        $horasPers = $data['horas_personalizadas'] ?? ($data['carga_horaria_custom'] ?? null);
        $horasPers = ($horasPers === '' || $horasPers === null) ? null : (int)$horasPers;

        try {
            $sql = "UPDATE grade_curso
                    SET horas_personalizadas = :horas_personalizadas
                    WHERE id_grade = :id_grade AND id_curso = :id_curso";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_grade', $idGrade, PDO::PARAM_INT);
            $stmt->bindValue(':id_curso', $idCurso, PDO::PARAM_INT);
            $stmt->bindValue(':horas_personalizadas', $horasPers, is_null($horasPers) ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->execute();

            http_response_code(200);
            echo json_encode(['sucesso' => true, 'mensagem' => 'Curso atualizado']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao atualizar', 'mensagem' => $e->getMessage()]);
        }
    }

    
    public function reordenarCursos(int $idGrade): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        if (!isset($data['cursos']) || !is_array($data['cursos'])) {
            http_response_code(400);
            echo json_encode(['erro' => 'Array de cursos Ã© obrigatÃ³rio']);
            return;
        }

        try {
            $this->db->beginTransaction();

            foreach ($data['cursos'] as $index => $idCurso) {
                $idCurso = (int)$idCurso;
                $ordem = $index + 1;

                $sql = "UPDATE grade_curso
                        SET ordem = :ordem
                        WHERE id_grade = :id_grade AND id_curso = :id_curso";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':ordem', $ordem, PDO::PARAM_INT);
                $stmt->bindValue(':id_grade', $idGrade, PDO::PARAM_INT);
                $stmt->bindValue(':id_curso', $idCurso, PDO::PARAM_INT);
                $stmt->execute();
            }

            $this->db->commit();
            http_response_code(200);
            echo json_encode(['sucesso' => true, 'mensagem' => 'Cursos reordenados']);
        } catch (PDOException $e) {
            $this->db->rollBack();
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao reordenar', 'mensagem' => $e->getMessage()]);
        }
    }

    
    public function removerCurso(int $idGrade, int $idCurso): void
    {
        try {
            $sql = "DELETE FROM grade_curso WHERE id_grade = :id_grade AND id_curso = :id_curso";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_grade', $idGrade, PDO::PARAM_INT);
            $stmt->bindValue(':id_curso', $idCurso, PDO::PARAM_INT);
            $stmt->execute();

            http_response_code(200);
            echo json_encode(['sucesso' => true, 'mensagem' => 'Curso removido']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao remover', 'mensagem' => $e->getMessage()]);
        }
    }

    private function gerarSlug(string $texto): string
    {
        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
        $slugBase = trim($texto, '-');

        
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM grade WHERE slug = :slug");
        $stmt->execute([':slug' => $slugBase]);
        $count = (int)$stmt->fetchColumn();

        
        if ($count > 0) {
            $i = 1;
            do {
                $novoSlug = $slugBase . '-' . $i;
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM grade WHERE slug = :slug");
                $stmt->execute([':slug' => $novoSlug]);
                $count = (int)$stmt->fetchColumn();
                if ($count === 0) {
                    return $novoSlug;
                }
                $i++;
            } while ($count > 0 && $i < 1000); 
        }

        return $slugBase;
    }

    private function aplicarValoresPadraoAGrade(array $grade): array
    {
        
        $getConfig = function (string $key, $default = null) {
            $stmt = $this->db->prepare("SELECT valor FROM configuracao WHERE chave = :chave LIMIT 1");
            $stmt->execute([':chave' => $key]);
            $val = $stmt->fetchColumn();
            return ($val === false) ? $default : $val;
        };

        
        if (!empty($grade['usa_valores_padrao'])) {
            $grade['valor_mensal_presencial'] = $getConfig('VALOR_MENSAL_PRESENCIAL_PADRAO', $grade['valor_mensal_presencial']);
            $grade['valor_mensal_ead'] = $getConfig('VALOR_MENSAL_EAD_PADRAO', $grade['valor_mensal_ead']);
            $grade['valor_matricula'] = $getConfig('VALOR_MATRICULA_PADRAO', $grade['valor_matricula']);
        }

        
        if (!empty($grade['usa_percentual_padrao'])) {
            $grade['percentual_parcelamento'] = $getConfig('PERCENTUAL_PARCELAMENTO_PADRAO', $grade['percentual_parcelamento']);
        }

        return $grade;
    }
}

