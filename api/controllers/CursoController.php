<?php
class CursoController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    
    public function index()
    {
        
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        
        $conditions = [];
        $params = [];

        if (isset($_GET['nome']) && $_GET['nome'] !== '') {
            $conditions[] = 'nome LIKE :nome';
            $params[':nome'] = '%' . trim($_GET['nome']) . '%';
        }

        if (isset($_GET['ativo']) && $_GET['ativo'] !== '') {
            $conditions[] = 'ativo = :ativo';
            $params[':ativo'] = (int) $_GET['ativo'];
        }

        if (isset($_GET['pode_montar_grade']) && $_GET['pode_montar_grade'] !== '') {
            $conditions[] = 'pode_montar_grade = :pode_montar_grade';
            $params[':pode_montar_grade'] = (int) $_GET['pode_montar_grade'];
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

        
        $stmtTotal = $this->db->prepare('SELECT COUNT(*) AS total FROM curso' . $where);
        foreach ($params as $key => $value) {
            $stmtTotal->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmtTotal->execute();
        $total = (int) $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

        
        $sql = "SELECT * FROM curso" . $where . " ORDER BY nome LIMIT :limit OFFSET :offset";
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

    
    public function buscarPorId($id)
    {
        $sql = "SELECT * FROM curso WHERE id_curso = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $curso = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($curso) {
            echo json_encode($curso);
        } else {
            http_response_code(404);
            echo json_encode(['erro' => 'Curso não encontrado']);
        }
    }

    
    public function criar()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nome']) || empty($data['horas']) || empty($data['descricao_curta'])) {
            http_response_code(400);
            echo json_encode(['erro' => 'Nome, horas e descrição curta são obrigatórios']);
            return;
        }

        if (empty($data['slug'])) {
            $data['slug'] = $this->gerarSlug($data['nome']);
        }

        $sql = "INSERT INTO curso (
            nome, slug, categoria, horas, descricao_curta, descricao_longa_md,
            pdf_conteudo, link_aula_demo, pode_montar_grade, ativo
        ) VALUES (
            :nome, :slug, :categoria, :horas, :descricao_curta, :descricao_longa_md,
            :pdf_conteudo, :link_aula_demo, :pode_montar_grade, :ativo
        )";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':nome', $data['nome']);
            $stmt->bindValue(':slug', $data['slug']);
            $stmt->bindValue(':categoria', $data['categoria'] ?? null);
            $stmt->bindValue(':horas', $data['horas'], PDO::PARAM_INT);
            $stmt->bindValue(':descricao_curta', $data['descricao_curta']);
            $stmt->bindValue(':descricao_longa_md', $data['descricao_longa_md'] ?? null);
            $stmt->bindValue(':pdf_conteudo', $data['pdf_conteudo'] ?? null);
            $stmt->bindValue(':link_aula_demo', $data['link_aula_demo'] ?? null);
            $stmt->bindValue(':pode_montar_grade', $data['pode_montar_grade'] ?? 1, PDO::PARAM_INT);
            $stmt->bindValue(':ativo', $data['ativo'] ?? 1, PDO::PARAM_INT);
            $stmt->execute();

            http_response_code(201);
            echo json_encode([
                'sucesso' => true,
                'id_curso' => $this->db->lastInsertId(),
                'mensagem' => 'Curso criado com sucesso'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao criar curso', 'mensagem' => $e->getMessage()]);
        }
    }

    
    public function atualizar($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $sql = "UPDATE curso SET
            nome = :nome,
            slug = :slug,
            categoria = :categoria,
            horas = :horas,
            descricao_curta = :descricao_curta,
            descricao_longa_md = :descricao_longa_md,
            pdf_conteudo = :pdf_conteudo,
            link_aula_demo = :link_aula_demo,
            pode_montar_grade = :pode_montar_grade,
            ativo = :ativo
        WHERE id_curso = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':nome', $data['nome']);
            $stmt->bindValue(':slug', $data['slug']);
            $stmt->bindValue(':categoria', $data['categoria'] ?? null);
            $stmt->bindValue(':horas', $data['horas'], PDO::PARAM_INT);
            $stmt->bindValue(':descricao_curta', $data['descricao_curta']);
            $stmt->bindValue(':descricao_longa_md', $data['descricao_longa_md'] ?? null);
            $stmt->bindValue(':pdf_conteudo', $data['pdf_conteudo'] ?? null);
            $stmt->bindValue(':link_aula_demo', $data['link_aula_demo'] ?? null);
            $stmt->bindValue(':pode_montar_grade', $data['pode_montar_grade'] ?? 1, PDO::PARAM_INT);
            $stmt->bindValue(':ativo', $data['ativo'] ?? 1, PDO::PARAM_INT);
            $stmt->execute();

            http_response_code(200);
            echo json_encode(['sucesso' => true, 'mensagem' => 'Curso atualizado']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao atualizar', 'mensagem' => $e->getMessage()]);
        }
    }

    
    public function excluir($id)
    {
        try {
            
            $stmt = $this->db->prepare('SELECT nome FROM curso WHERE id_curso = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $curso = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$curso) {
                http_response_code(404);
                echo json_encode(['erro' => 'Curso não encontrado']);
                return;
            }

            
            $stmt = $this->db->prepare('SELECT COUNT(*) as total FROM grade_curso WHERE id_curso = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $resultadoGrade = $stmt->fetch(PDO::FETCH_ASSOC);

            $totalVinculos = $resultadoGrade['total'];

            if ($totalVinculos > 0) {
                http_response_code(400);
                echo json_encode([
                    'erro' => 'Não é possível excluir este curso',
                    'mensagem' => "O curso \"{$curso['nome']}\" está vinculado a {$resultadoGrade['total']} grade(s). Remova os vínculos antes de excluir."
                ]);
                return;
            }

            $stmt = $this->db->prepare('DELETE FROM curso WHERE id_curso = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            http_response_code(200);
            echo json_encode(['sucesso' => true, 'mensagem' => 'Curso excluído']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao excluir', 'mensagem' => $e->getMessage()]);
        }
    }

    
    private function gerarSlug($texto)
    {
        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
        $texto = trim($texto, '-');
        return $texto;
    }

    
    public function cursosPorsonalizados()
    {
        $sql = "SELECT 
                    id_curso, 
                    nome, 
                    slug, 
                    horas, 
                    descricao_curta,
                    pode_montar_grade
                FROM curso 
                WHERE pode_montar_grade = 1 
                AND ativo = 1 
                ORDER BY nome";

        try {
            $stmt = $this->db->query($sql);
            $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($cursos);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao buscar cursos personalizáveis', 'mensagem' => $e->getMessage()]);
        }
    }
}
