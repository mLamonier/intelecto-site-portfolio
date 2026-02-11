<?php

require_once __DIR__ . '/../services/PedidoAprovadoNotifier.php';

class PedidoController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    private function isAdmin(): bool
    {
        if (!empty($_SESSION['admin_logado'])) {
            return true;
        }
        $roles = $_SESSION['usuario_roles'] ?? [];
        return is_array($roles) && in_array('ADMIN', $roles, true);
    }

    private function getLoggedUserId(): ?int
    {
        $id = $_SESSION['usuario_id'] ?? null;
        return $id ? (int)$id : null;
    }

    private function requireAdmin(): void
    {
        http_response_code(403);
        echo json_encode(['error' => 'Acesso restrito: admin requerido']);
        exit;
    }
    
    
    public function store()
    {
        $input = json_decode(file_get_contents("php://input"), true);

        $sessionUserId = $this->getLoggedUserId();
        $id_usuario    = $sessionUserId ?? ($input['id_usuario'] ?? null);
        $tipo          = $input['tipo'] ?? 'GRADE'; 
        $id_grade      = $input['id_grade'] ?? null;
        $modalidade    = $input['modalidade'] ?? 'PRESENCIAL';
        $valor_total   = $input['valor_total'] ?? 0;
        $horas_total   = $input['horas_total'] ?? 0;
        $meses_duracao = $input['meses_duracao'] ?? null;
        $forma_pagamento = $input['forma_pagamento'] ?? null;
        $valor_mensal    = $input['valor_mensal'] ?? null;
        $valor_avista    = $input['valor_avista'] ?? null;
        $valor_matricula = $input['valor_matricula'] ?? null;
        $cursos          = $input['cursos'] ?? [];

        if (!$id_usuario || (!$id_grade && empty($cursos))) {
            http_response_code(400);
            echo json_encode(["error" => "Dados obrigatórios ausentes"]);
            return;
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO pedidos
                    (id_usuario, tipo, id_grade, modalidade,
                     horas_total, valor_total, meses_duracao,
                     forma_pagamento, valor_mensal, valor_avista, valor_matricula,
                     status)
                VALUES
                    (:id_usuario, :tipo, :id_grade, :modalidade,
                     :horas_total, :valor_total, :meses_duracao,
                     :forma_pagamento, :valor_mensal, :valor_avista, :valor_matricula,
                     'PENDENTE')
            ");

            $stmt->execute([
                ':id_usuario'      => $id_usuario,
                ':tipo'            => $tipo,
                ':id_grade'        => $id_grade,
                ':modalidade'      => $modalidade,
                ':horas_total'     => $horas_total,
                ':valor_total'     => $valor_total,
                ':meses_duracao'   => $meses_duracao,
                ':forma_pagamento' => $forma_pagamento,
                ':valor_mensal'    => $valor_mensal,
                ':valor_avista'    => $valor_avista,
                ':valor_matricula' => $valor_matricula,
            ]);

            $id_pedido = (int) $this->db->lastInsertId();

            
            if ($id_grade) {
                $stmtItens = $this->db->prepare("
                    INSERT INTO pedido_item (id_pedido, id_curso, horas, valor_hora, valor_total)
                    SELECT
                        :id_pedido,
                        c.id_curso,
                        COALESCE(gc.horas_personalizadas, c.horas) AS horas,
                        0 AS valor_hora,
                        0 AS valor_total
                    FROM grade_curso gc
                    JOIN curso c ON c.id_curso = gc.id_curso
                    WHERE gc.id_grade = :id_grade
                ");

                $stmtItens->execute([
                    ':id_pedido' => $id_pedido,
                    ':id_grade'  => $id_grade,
                ]);
            } elseif (!empty($cursos)) {
                
                $stmtItem = $this->db->prepare("
                    INSERT INTO pedido_item (id_pedido, id_curso, horas, valor_hora, valor_total)
                    VALUES (:id_pedido, :id_curso, :horas, :valor_hora, :valor_total)
                ");

                foreach ($cursos as $curso) {
                    $stmtItem->execute([
                        ':id_pedido'   => $id_pedido,
                        ':id_curso'    => $curso['id_curso'],
                        ':horas'       => $curso['horas'] ?? 0,
                        ':valor_hora'  => $curso['valor_hora'] ?? 0,
                        ':valor_total' => $curso['valor_total'] ?? 0,
                    ]);
                }
            }

            $this->db->commit();

            http_response_code(201);
            echo json_encode([
                "id_pedido"   => $id_pedido,
                "status"      => "PENDENTE",
                "valor_total" => $valor_total,
            ]);
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            echo json_encode([
                "error"   => "Erro ao criar pedido",
                "message" => $e->getMessage()
            ]);
        }
    }

    
    public function index()
    {
        $page    = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;
        $status  = $_GET['status'] ?? null;
        $idUsuario = isset($_GET['id_usuario']) ? (int) $_GET['id_usuario'] : null;
        $cliente = $_GET['cliente'] ?? null;
        $dataInicioRaw = $_GET['data_inicio'] ?? null;
        $dataFimRaw = $_GET['data_fim'] ?? null;
        $isValidDate = static function ($value): bool {
            if (!is_string($value) || $value === '') {
                return false;
            }
            $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
            return $date && $date->format('Y-m-d') === $value;
        };
        $dataInicio = $isValidDate($dataInicioRaw) ? $dataInicioRaw : null;
        $dataFim = $isValidDate($dataFimRaw) ? $dataFimRaw : null;

        if (!$this->isAdmin()) {
            $sessionUserId = $this->getLoggedUserId();
            if (!$sessionUserId) {
                http_response_code(401);
                echo json_encode(["error" => "Login requerido"]);
                return;
            }
            if (!$idUsuario || $idUsuario !== $sessionUserId) {
                http_response_code(403);
                echo json_encode(["error" => "Acesso negado"]);
                return;
            }
            $cliente = null;
        }

        $offset = ($page - 1) * $perPage;

        $where  = " WHERE 1=1 ";
        $params = [];

        if ($status) {
            $where .= " AND p.status = :status ";
            $params[":status"] = $status;
        }

        if ($idUsuario) {
            $where .= " AND p.id_usuario = :id_usuario ";
            $params[":id_usuario"] = $idUsuario;
        }

        if ($cliente) {
            $where .= " AND (u.nome LIKE :cliente_search OR u.email LIKE :cliente_search) ";
            $params[":cliente_search"] = "%{$cliente}%";
        }

        if ($dataInicio) {
            $where .= " AND DATE(p.criado_em) >= :data_inicio ";
            $params[":data_inicio"] = $dataInicio;
        }

        if ($dataFim) {
            $where .= " AND DATE(p.criado_em) <= :data_fim ";
            $params[":data_fim"] = $dataFim;
        }

        
        $stmtTotal = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM pedidos p
            LEFT JOIN usuario u ON u.id_usuario = p.id_usuario
            LEFT JOIN grade g ON g.id_grade = p.id_grade
            $where
        ");

        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
            $sql = "
                SELECT
                    p.id_pedido,
                    p.id_usuario,
                    u.nome AS usuario_nome,
                    u.email AS usuario_email,
                    u.telefone AS usuario_telefone,
                    p.id_grade,
                    g.nome AS grade_nome,
                    g.slug AS grade_slug,
                    p.tipo,
                    p.modalidade,
                    p.horas_total,
                    p.meses_duracao,
                    p.valor_total,
                    p.forma_pagamento,
                    p.valor_mensal,
                    p.valor_avista,
                    p.valor_matricula,
                    p.status,
                    p.criado_em,
                    (SELECT COUNT(*) FROM pedido_item pi WHERE pi.id_pedido = p.id_pedido) AS total_itens
                FROM pedidos p
                LEFT JOIN usuario u ON u.id_usuario = p.id_usuario
                LEFT JOIN grade g ON g.id_grade = p.id_grade
                $where
                ORDER BY p.criado_em DESC
                LIMIT :limit OFFSET :offset
            ";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "data"        => $items,
            "page"        => $page,
            "per_page"    => $perPage,
            "total"       => $total,
            "total_pages" => (int) ceil($total / $perPage),
        ]);
    }

    
    public function update(int $id)
    {
        if (!$this->isAdmin()) {
            $this->requireAdmin();
        }

        $input = json_decode(file_get_contents("php://input"), true);

        $status = $input['status'] ?? null;

        if (!$status) {
            http_response_code(400);
            echo json_encode(["error" => "Status é obrigatório"]);
            return;
        }

        try {
            $stmtAtual = $this->db->prepare("
                SELECT status
                FROM pedidos
                WHERE id_pedido = :id
                LIMIT 1
            ");
            $stmtAtual->execute([':id' => $id]);
            $pedidoAtual = $stmtAtual->fetch(PDO::FETCH_ASSOC);

            if (!$pedidoAtual) {
                http_response_code(404);
                echo json_encode(["error" => "Pedido nÃ£o encontrado"]);
                return;
            }

            $statusAnterior = strtoupper((string)($pedidoAtual['status'] ?? ''));
            $novoStatus = strtoupper((string)$status);

            $stmt = $this->db->prepare("
                UPDATE pedidos
                SET status = :status
                WHERE id_pedido = :id
            ");

            $stmt->execute([
                ':status' => $status,
                ':id'     => $id,
            ]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(["error" => "Pedido não encontrado"]);
                return;
            }

            if ($novoStatus === 'PAGO' && $statusAnterior !== 'PAGO') {
                PedidoAprovadoNotifier::notificar($this->db, $id);
            }

            echo json_encode(["message" => "Pedido atualizado com sucesso"]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error"   => "Erro ao atualizar pedido",
                "message" => $e->getMessage()
            ]);
        }
    }

    
    public function criarPedidoGrade()
    {
        $input = json_decode(file_get_contents("php://input"), true);

        $id_usuario    = 1; 
        $id_grade      = $input['id_grade'] ?? null;
        $modalidade    = $input['modalidade'] ?? 'PRESENCIAL';
        $valor_total   = $input['valor_total'] ?? 0;
        $horas_total   = $input['horas_total'] ?? 0;
        $meses_duracao = $input['meses_duracao'] ?? null;
        $forma_pagamento = $input['forma_pagamento'] ?? null; 
        $valor_mensal    = $input['valor_mensal']    ?? null;
        $valor_avista    = $input['valor_avista']    ?? null;

        if (!$id_usuario || !$id_grade) {
            http_response_code(400);
            echo json_encode(["error" => "Dados obrigatórios ausentes"]);
            return;
        }

        try {
            $this->db->beginTransaction();

$stmt = $this->db->prepare("
    INSERT INTO pedidos
        (id_usuario, tipo, modalidade,
         horas_total, valor_total, meses_duracao,
         forma_pagamento, valor_mensal, valor_avista,
         status)
    VALUES
        (:id_usuario, 'PERSONALIZADA', :modalidade,
         :horas_total, :valor_total, :meses_duracao,
         :forma_pagamento, :valor_mensal, :valor_avista,
         'PENDENTE')
");

$stmt->execute([
    ':id_usuario'      => $id_usuario,
    ':modalidade'      => $modalidade,
    ':horas_total'     => $horas_total,
    ':valor_total'     => $valor_total,
    ':meses_duracao'   => $meses_duracao,
    ':forma_pagamento' => $forma_pagamento,
    ':valor_mensal'    => $valor_mensal,
    ':valor_avista'    => $valor_avista,
]);

            $id_pedido = (int) $this->db->lastInsertId();

            
            $stmtItens = $this->db->prepare("
                INSERT INTO pedido_item (id_pedido, id_curso, horas, valor_hora, valor_total)
                SELECT
                    :id_pedido,
                    c.id_curso,
                    COALESCE(gc.horas_personalizadas, c.horas) AS horas,
                    0 AS valor_hora,
                    0 AS valor_total
                FROM grade_curso gc
                JOIN curso c ON c.id_curso = gc.id_curso
                WHERE gc.id_grade = :id_grade
            ");

            $stmtItens->execute([
                ':id_pedido' => $id_pedido,
                ':id_grade'  => $id_grade,
            ]);

            $this->db->commit();

            echo json_encode([
                "id_pedido"   => $id_pedido,
                "status"      => "PENDENTE",
                "valor_total" => $valor_total,
            ]);
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            echo json_encode([
                "error"   => "Erro ao criar pedido de grade",
                "message" => $e->getMessage()
            ]);
        }
    }

    
public function criarPedidoPersonalizado()
{
    $input = json_decode(file_get_contents("php://input"), true);

    $id_usuario       = 1; 
    $modalidade       = $input['modalidade'] ?? 'PRESENCIAL';
    $cursos           = $input['cursos'] ?? [];
    $horas_total      = $input['horas_total'] ?? 0;
    $valor_total      = $input['valor_total'] ?? 0;

    $forma_pagamento  = $input['forma_pagamento'] ?? null; 
    $valor_mensal     = $input['valor_mensal'] ?? null;
    $valor_avista     = $input['valor_avista'] ?? null;

    $meses_duracao = $input['meses_duracao'] ?? null;

    if (!$id_usuario || empty($cursos)) {
        http_response_code(400);
        echo json_encode(["error" => "Dados obrigatórios ausentes"]);
        return;
    }

    try {
        $this->db->beginTransaction();

        $stmt = $this->db->prepare("
            INSERT INTO pedidos
                (id_usuario, tipo, modalidade,
                 horas_total, valor_total,
                 forma_pagamento, valor_mensal, valor_avista,
                 status)
            VALUES
                (:id_usuario, 'PERSONALIZADA', :modalidade,
                 :horas_total, :valor_total,
                 :forma_pagamento, :valor_mensal, :valor_avista,
                 'PENDENTE')
        ");

        $stmt->execute([
            ':id_usuario'      => $id_usuario,
            ':modalidade'      => $modalidade,
            ':horas_total'     => $horas_total,
            ':valor_total'     => $valor_total,
            ':forma_pagamento' => $forma_pagamento,
            ':valor_mensal'    => $valor_mensal,
            ':valor_avista'    => $valor_avista,
        ]);

        $id_pedido = (int) $this->db->lastInsertId();

        $stmtItem = $this->db->prepare("
            INSERT INTO pedido_item (id_pedido, id_curso, horas, valor_hora, valor_total)
            VALUES (:id_pedido, :id_curso, :horas, :valor_hora, :valor_total)
        ");

        foreach ($cursos as $curso) {
            $stmtItem->execute([
                ':id_pedido'   => $id_pedido,
                ':id_curso'    => $curso['id_curso'],
                ':horas'       => $curso['horas'],
                ':valor_hora'  => $curso['valor_hora'],
                ':valor_total' => $curso['valor_total'],
            ]);
        }

        $this->db->commit();

        echo json_encode([
            "id_pedido"   => $id_pedido,
            "status"      => "PENDENTE",
            "valor_total" => $valor_total,
        ]);
    } catch (Exception $e) {
        $this->db->rollBack();
        http_response_code(500);
        echo json_encode([
            "error"   => "Erro ao criar pedido personalizado",
            "message" => $e->getMessage()
        ]);
    }
}

    
    public function listarPorUsuario()
    {
        $idUsuario = $_GET['id_usuario'] ?? null;
        $page      = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage   = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 5;
        $status    = $_GET['status'] ?? null; 

        if (!$idUsuario) {
            http_response_code(400);
            echo json_encode(["error" => "id_usuario é obrigatório"]);
            return;
        }

        $offset = ($page - 1) * $perPage;

        $where  = " WHERE id_usuario = :id ";
        $params = [":id" => $idUsuario];

        if ($status) {
            $where .= " AND status = :status ";
            $params[":status"] = $status;
        }

        
        $stmtTotal = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM pedidos
            $where
        ");

        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

        
        $sql = "
            SELECT
                id_pedido,
                id_grade,
                tipo,
                modalidade,
                horas_total,
                meses_duracao,
                valor_total,
                forma_pagamento,
                valor_mensal,
                valor_avista,
                status,
                criado_em
            FROM pedidos
            $where
            ORDER BY criado_em DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "data"        => $items,
            "page"        => $page,
            "per_page"    => $perPage,
            "total"       => $total,
            "total_pages" => (int) ceil($total / $perPage),
        ]);
    }

    
    public function show(int $id)
    {
        
        $stmt = $this->db->prepare("
            SELECT
                p.*,
                u.nome AS usuario_nome,
                u.email AS usuario_email,
                u.telefone AS usuario_telefone,
                u.cpf AS usuario_cpf,
                g.nome AS grade_nome,
                g.slug AS grade_slug
            FROM pedidos p
            LEFT JOIN usuario u ON u.id_usuario = p.id_usuario
            LEFT JOIN grade g ON p.id_grade = g.id_grade
            WHERE p.id_pedido = :id
        ");

        $stmt->execute([':id' => $id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pedido) {
            http_response_code(404);
            echo json_encode(["error" => "Pedido não encontrado"]);
            return;
        }

        if (!$this->isAdmin()) {
            $sessionUserId = $this->getLoggedUserId();
            if (!$sessionUserId || (int)$pedido['id_usuario'] !== $sessionUserId) {
                http_response_code(403);
                echo json_encode(["error" => "Acesso negado"]);
                return;
            }
        }

        
        $stmtCursos = $this->db->prepare("
            SELECT
                pi.id_curso,
                c.nome,
                pi.horas AS carga_horaria,
                pi.valor_hora,
                pi.valor_total
            FROM pedido_item pi
            JOIN curso c ON c.id_curso = pi.id_curso
            WHERE pi.id_pedido = :id_pedido
            ORDER BY pi.id_pedido_item ASC
        ");

        $stmtCursos->execute([':id_pedido' => $id]);
        $pedido['cursos'] = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($pedido);
    }

    
    public function destroy(int $id)
    {
        if (!$this->isAdmin()) {
            $this->requireAdmin();
        }

        try {
            
            $stmt = $this->db->prepare("SELECT status FROM pedidos WHERE id_pedido = :id");
            $stmt->execute([':id' => $id]);
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pedido) {
                http_response_code(404);
                echo json_encode(["error" => "Pedido n?o encontrado"]);
                return;
            }

            
            if ($pedido['status'] !== 'PENDENTE') {
                http_response_code(403);
                echo json_encode(["error" => "Apenas pedidos com status PENDENTE podem ser deletados"]);
                return;
            }

            $this->db->beginTransaction();

            
            $stmtPagamentos = $this->db->prepare("DELETE FROM pagamento WHERE id_pedido = :id");
            $stmtPagamentos->execute([':id' => $id]);

            
            $stmtItems = $this->db->prepare("DELETE FROM pedido_item WHERE id_pedido = :id");
            $stmtItems->execute([':id' => $id]);

            
            $stmtDelete = $this->db->prepare("DELETE FROM pedidos WHERE id_pedido = :id");
            $stmtDelete->execute([':id' => $id]);

            $this->db->commit();

            http_response_code(200);
            echo json_encode(["message" => "Pedido deletado com sucesso"]);
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(500);

            
            $errorMessage = "Ocorreu um erro ao deletar o pedido";
            if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                $errorMessage = "N?o foi poss?vel deletar este pedido. Verifique se existem dados relacionados.";
            } elseif (strpos($e->getMessage(), 'Integrity constraint') !== false) {
                $errorMessage = "N?o ? poss?vel deletar este pedido neste momento. Tente novamente mais tarde.";
            }

            echo json_encode(["error" => $errorMessage]);
        }
    }
}
