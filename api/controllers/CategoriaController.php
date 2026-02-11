<?php

class CategoriaController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    

    public function index()
    {
        $sql = "SELECT * FROM categoria WHERE ativo = 1 ORDER BY ordem, nome";
        $stmt = $this->db->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    

    public function todas()
    {
        
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
        $offset = ($page - 1) * $perPage;

        $nome = isset($_GET['nome']) ? trim($_GET['nome']) : '';
        $ativoRaw = $_GET['ativo'] ?? '';
        $whereParts = [];
        $params = [];
        if ($nome !== '') {
            $whereParts[] = 'nome LIKE :nome';
            $params[':nome'] = '%' . $nome . '%';
        }
        if ($ativoRaw !== '') {
            $whereParts[] = 'ativo = :ativo';
            $params[':ativo'] = (int) $ativoRaw;
        }
        $where = $whereParts ? ' WHERE ' . implode(' AND ', $whereParts) : '';

        
        $stmtTotal = $this->db->prepare('SELECT COUNT(*) AS total FROM categoria' . $where);
        foreach ($params as $key => $value) {
            $stmtTotal->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmtTotal->execute();
        $total = (int) $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

        
        $sql = "SELECT * FROM categoria" . $where . " ORDER BY ordem, nome LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode([
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int) ceil($total / $perPage)
        ]);
    }

    

    public function show($id)
    {
        $sql = "SELECT * FROM categoria WHERE id_categoria = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($categoria) {
            echo json_encode($categoria);
        } else {
            http_response_code(404);
            echo json_encode(['erro' => 'Categoria não encontrada']);
        }
    }

    

    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        
        if (empty($data['nome'])) {
            http_response_code(400);
            echo json_encode(['erro' => 'Nome é obrigatório']);
            return;
        }

        
        if (empty($data['slug'])) {
            $data['slug'] = $this->gerarSlug($data['nome']);
        }

        $sql = "INSERT INTO categoria (
            nome, slug, descricao, ordem, ativo
        ) VALUES (
            :nome, :slug, :descricao, :ordem, :ativo
        )";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':nome', $data['nome']);
            $stmt->bindValue(':slug', $data['slug']);
            $stmt->bindValue(':descricao', $data['descricao'] ?? null);
            $stmt->bindValue(':ordem', $data['ordem'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':ativo', $data['ativo'] ?? 1, PDO::PARAM_INT);
            $stmt->execute();

            http_response_code(201);
            echo json_encode([
                'sucesso' => true,
                'id_categoria' => $this->db->lastInsertId(),
                'mensagem' => 'Categoria criada com sucesso'
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                http_response_code(400);
                echo json_encode(['erro' => 'Já existe uma categoria com este nome ou slug']);
            } else {
                http_response_code(500);
                echo json_encode(['erro' => 'Erro ao criar categoria', 'mensagem' => $e->getMessage()]);
            }
        }
    }

    

    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);

        
        $stmt = $this->db->prepare('SELECT id_categoria FROM categoria WHERE id_categoria = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['erro' => 'Categoria não encontrada']);
            return;
        }

        $sql = "UPDATE categoria SET
            nome = :nome,
            slug = :slug,
            descricao = :descricao,
            ordem = :ordem,
            ativo = :ativo
        WHERE id_categoria = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':nome', $data['nome']);
            $stmt->bindValue(':slug', $data['slug']);
            $stmt->bindValue(':descricao', $data['descricao'] ?? null);
            $stmt->bindValue(':ordem', $data['ordem'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':ativo', $data['ativo'] ?? 1, PDO::PARAM_INT);
            $stmt->execute();

            http_response_code(200);
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Categoria atualizada com sucesso'
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                http_response_code(400);
                echo json_encode(['erro' => 'Já existe uma categoria com este nome ou slug']);
            } else {
                http_response_code(500);
                echo json_encode(['erro' => 'Erro ao atualizar categoria', 'mensagem' => $e->getMessage()]);
            }
        }
    }

    

    public function destroy($id)
    {
        try {
            
            $stmt = $this->db->prepare('SELECT nome FROM categoria WHERE id_categoria = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$categoria) {
                http_response_code(404);
                echo json_encode(['erro' => 'Categoria não encontrada']);
                return;
            }

            
            $stmt = $this->db->prepare('SELECT COUNT(*) as total FROM curso WHERE id_categoria = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $resultadoCursos = $stmt->fetch(PDO::FETCH_ASSOC);

            
            $stmt = $this->db->prepare('SELECT COUNT(*) as total FROM grade WHERE id_categoria = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $resultadoGrades = $stmt->fetch(PDO::FETCH_ASSOC);

            
            $stmt = $this->db->prepare('SELECT COUNT(*) as total FROM homepage_categories WHERE id_categoria = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $resultadoHomepage = $stmt->fetch(PDO::FETCH_ASSOC);

            $totalVinculos = $resultadoCursos['total'] + $resultadoGrades['total'] + $resultadoHomepage['total'];

            if ($totalVinculos > 0) {
                http_response_code(400);
                echo json_encode([
                    'erro' => 'Não é possível excluir esta categoria',
                    'mensagem' => "A categoria \"{$categoria['nome']}\" está vinculada a {$resultadoCursos['total']} curso(s), {$resultadoGrades['total']} grade(s) e {$resultadoHomepage['total']} item(ns) da homepage. Remova os vínculos antes de excluir."
                ]);
                return;
            }

            
            $stmt = $this->db->prepare('DELETE FROM categoria WHERE id_categoria = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            http_response_code(200);
            echo json_encode(['sucesso' => true, 'mensagem' => 'Categoria excluída com sucesso']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao excluir categoria', 'mensagem' => $e->getMessage()]);
        }
    }

    

    private function gerarSlug($texto)
    {
        
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
        $texto = trim($texto, '-');
        return $texto;
    }
}
