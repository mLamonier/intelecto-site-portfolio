<?php

require_once __DIR__ . '/../../includes/mailer.php';

class UsuarioController
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    

    public function index()
    {
        try {
            $email = $_GET['email'] ?? null;
            $excludeAdminRaw = $_GET['exclude_admin'] ?? null;
            $excludeAdmin = in_array(strtolower((string)$excludeAdminRaw), ['1', 'true', 'yes'], true);
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

            if ($email) {
                $where = 'WHERE u.email = :email';
                $params = [':email' => $email];
                if ($excludeAdmin) {
                    $where .= " AND NOT EXISTS (
                        SELECT 1
                        FROM usuario_role ur
                        WHERE ur.id_usuario = u.id_usuario
                          AND UPPER(ur.role) = 'ADMIN'
                    )";
                }
                if ($dataInicio) {
                    $where .= ' AND DATE(u.criado_em) >= :data_inicio';
                    $params[':data_inicio'] = $dataInicio;
                }
                if ($dataFim) {
                    $where .= ' AND DATE(u.criado_em) <= :data_fim';
                    $params[':data_fim'] = $dataFim;
                }

                $stmt = $this->conn->prepare("
                    SELECT id_usuario, nome, email, telefone, cpf, ativo, criado_em
                    FROM usuario u
                    $where
                ");
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();

                $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

                http_response_code(200);
                echo json_encode($usuarios);
                return;
            }

            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
            $offset = ($page - 1) * $perPage;

            $nome = isset($_GET['nome']) ? trim($_GET['nome']) : '';
            $telefoneRaw = $_GET['telefone'] ?? '';
            $telefone = preg_replace('/\D/', '', $telefoneRaw);
            $ativoRaw = $_GET['ativo'] ?? '';

            $where = 'WHERE 1=1';
            $params = [];
            if ($excludeAdmin) {
                $where .= " AND NOT EXISTS (
                    SELECT 1
                    FROM usuario_role ur
                    WHERE ur.id_usuario = u.id_usuario
                      AND UPPER(ur.role) = 'ADMIN'
                )";
            }
            if ($dataInicio) {
                $where .= ' AND DATE(u.criado_em) >= :data_inicio';
                $params[':data_inicio'] = $dataInicio;
            }
            if ($dataFim) {
                $where .= ' AND DATE(u.criado_em) <= :data_fim';
                $params[':data_fim'] = $dataFim;
            }
            if ($nome !== '') {
                $where .= ' AND u.nome LIKE :nome';
                $params[':nome'] = '%' . $nome . '%';
            }
            if ($telefone !== '') {
                $telefoneQuery = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(u.telefone, ' ', ''), '(', ''), ')', ''), '-', ''), '+', ''), '.', '')";
                $where .= " AND $telefoneQuery LIKE :telefone";
                $params[':telefone'] = '%' . $telefone . '%';
            }
            if ($ativoRaw !== '') {
                $where .= ' AND u.ativo = :ativo';
                $params[':ativo'] = (int) $ativoRaw;
            }

            $stmtTotal = $this->conn->prepare("SELECT COUNT(*) AS total FROM usuario u $where");
            $stmtTotal->execute($params);
            $total = (int) $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

            $query = "SELECT id_usuario, nome, email, telefone, cpf, ativo, criado_em
                    FROM usuario u
                    $where
                    ORDER BY criado_em DESC
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            http_response_code(200);
            echo json_encode([
                'data' => $usuarios,
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage)
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["erro" => "Erro ao buscar usuários", "detalhes" => $e->getMessage()]);
        }
    }

    

    public function show($id)
    {
        try {
            $stmt = $this->conn->prepare('SELECT id_usuario, nome, email, telefone, cpf, ativo, criado_em FROM usuario WHERE id_usuario = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                http_response_code(404);
                echo json_encode(['erro' => 'Usuário não encontrado']);
                return;
            }

            http_response_code(200);
            echo json_encode($usuario);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao buscar usuário', 'mensagem' => $e->getMessage()]);
        }
    }

    

    public function store()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];

            
            if (empty($input['nome']) || empty($input['email'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'Nome e email são obrigatórios']);
                return;
            }

            $cpf = preg_replace('/\D/', '', $input['cpf'] ?? '');
            $cpfQuery = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', ''), '(', ''), ')', '')";

            
            $stmt = $this->conn->prepare('SELECT id_usuario FROM usuario WHERE email = :email');
            $stmt->bindValue(':email', $input['email']);
            $stmt->execute();

            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['erro' => 'Email já cadastrado']);
                return;
            }

            
            if ($cpf) {
                $stmt = $this->conn->prepare("SELECT id_usuario FROM usuario WHERE $cpfQuery = :cpf");
                $stmt->bindValue(':cpf', $cpf);
                $stmt->execute();

                if ($stmt->fetch()) {
                    http_response_code(400);
                    echo json_encode(['erro' => 'CPF já cadastrado']);
                    return;
                }
            }

            
            $senhaHash = !empty($input['senha'])
                ? password_hash($input['senha'], PASSWORD_DEFAULT)
                : password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

            $stmt = $this->conn->prepare('
                INSERT INTO usuario (nome, email, senha_hash, telefone, cpf, ativo, criado_em)
                VALUES (:nome, :email, :senha_hash, :telefone, :cpf, :ativo, NOW())
            ');

            $stmt->bindValue(':nome', $input['nome']);
            $stmt->bindValue(':email', $input['email']);
            $stmt->bindValue(':senha_hash', $senhaHash);
            $stmt->bindValue(':telefone', $input['telefone'] ?? null);
            $stmt->bindValue(':cpf', $cpf ?: null);
            $stmt->bindValue(':ativo', isset($input['ativo']) ? (int)$input['ativo'] : 1, PDO::PARAM_INT);

            $stmt->execute();

            $novoId = $this->conn->lastInsertId();

            http_response_code(201);
            echo json_encode([
                'sucesso' => true,
                'id_usuario' => $novoId,
                'id' => $novoId, 
                'mensagem' => 'Usuário criado com sucesso'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao criar usuário', 'mensagem' => $e->getMessage()]);
        }
    }

    

    public function update($id)
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];

            $cpf = preg_replace('/\D/', '', $input['cpf'] ?? '');
            $cpfQuery = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', ''), '(', ''), ')', '')";

            
            $stmt = $this->conn->prepare('SELECT id_usuario FROM usuario WHERE id_usuario = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['erro' => 'Usuário não encontrado']);
                return;
            }

            
            if (empty($input['nome']) || empty($input['email'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'Nome e email são obrigatórios']);
                return;
            }

            
            $stmt = $this->conn->prepare('SELECT id_usuario FROM usuario WHERE email = :email AND id_usuario != :id');
            $stmt->bindValue(':email', $input['email']);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['erro' => 'Email já cadastrado por outro usuário']);
                return;
            }

            
            if ($cpf) {
                $stmt = $this->conn->prepare("SELECT id_usuario FROM usuario WHERE $cpfQuery = :cpf AND id_usuario != :id");
                $stmt->bindValue(':cpf', $cpf);
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->fetch()) {
                    http_response_code(400);
                    echo json_encode(['erro' => 'CPF já cadastrado por outro usuário']);
                    return;
                }
            }

            
            if (!empty($input['senha'])) {
                
                $senhaHash = password_hash($input['senha'], PASSWORD_DEFAULT);

                $stmt = $this->conn->prepare('
                    UPDATE usuario
                    SET nome = :nome,
                        email = :email,
                        senha_hash = :senha_hash,
                        telefone = :telefone,
                        cpf = :cpf,
                        ativo = :ativo
                    WHERE id_usuario = :id
                ');
                $stmt->bindValue(':senha_hash', $senhaHash);
            } else {
                
                $stmt = $this->conn->prepare('
                    UPDATE usuario
                    SET nome = :nome,
                        email = :email,
                        telefone = :telefone,
                        cpf = :cpf,
                        ativo = :ativo
                    WHERE id_usuario = :id
                ');
            }

            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':nome', $input['nome']);
            $stmt->bindValue(':email', $input['email']);
            $stmt->bindValue(':telefone', $input['telefone'] ?? null);
            $stmt->bindValue(':cpf', $cpf ?: null);
            $stmt->bindValue(':ativo', isset($input['ativo']) ? (int)$input['ativo'] : 1, PDO::PARAM_INT);

            $stmt->execute();

            http_response_code(200);
            echo json_encode(['sucesso' => true, 'mensagem' => 'Usuário atualizado com sucesso']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao atualizar usuário', 'mensagem' => $e->getMessage()]);
        }
    }

    

    public function destroy($id)
    {
        try {
            
            $this->conn->beginTransaction();

            
            $stmt = $this->conn->prepare('SELECT id_usuario, nome FROM usuario WHERE id_usuario = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                http_response_code(404);
                echo json_encode(['erro' => 'Usuário não encontrado']);
                return;
            }

            
            $stmt = $this->conn->prepare('SELECT COUNT(*) as total FROM pedidos WHERE id_usuario = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resultado['total'] > 0) {
                $this->conn->rollBack();
                http_response_code(400);
                echo json_encode([
                    'erro' => 'Não é possível excluir este usuário',
                    'mensagem' => "O usuário \"{$usuario['nome']}\" possui {$resultado['total']} pedido(s) vinculado(s). Para excluir, é necessário primeiro remover ou transferir os pedidos.",
                    'total_pedidos' => (int)($resultado['total'] ?? 0)
                ]);
                return;
            }

            
            $stmt = $this->conn->prepare('DELETE FROM usuario_role WHERE id_usuario = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->conn->prepare('DELETE FROM password_reset WHERE id_usuario = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            
            $stmt = $this->conn->prepare('DELETE FROM usuario WHERE id_usuario = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->conn->commit();

            http_response_code(200);
            echo json_encode(['sucesso' => true, 'mensagem' => 'Usuário excluído com sucesso']);
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            
            if ($e->getCode() == '23000' || strpos($e->getMessage(), 'foreign key constraint') !== false) {
                http_response_code(400);
                echo json_encode([
                    'erro' => 'Não é possível excluir este usuário',
                    'mensagem' => 'O usuário possui registros vinculados (pedidos, pagamentos, etc.) e não pode ser excluído.'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['erro' => 'Erro ao excluir usuário', 'mensagem' => $e->getMessage()]);
            }
        }
    }

    

    public function verificarExistencia()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];

            $email = trim($input['email'] ?? '');
            $cpf = preg_replace('/\D/', '', $input['cpf'] ?? '');
            $cpfQuery = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', ''), '(', ''), ')', '')";

            if (!$email && !$cpf) {
                http_response_code(400);
                echo json_encode(['erro' => 'Email ou CPF são obrigatórios para verificação']);
                return;
            }

            
            if ($email) {
                $stmt = $this->conn->prepare('SELECT id_usuario FROM usuario WHERE email = :email LIMIT 1');
                $stmt->bindValue(':email', $email);
                $stmt->execute();
                if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                    http_response_code(409);
                    echo json_encode(['existe' => true, 'campo' => 'email']);
                    return;
                }
            }

            
            if ($cpf) {
                try {
                    $stmt = $this->conn->prepare("SELECT id_usuario FROM usuario WHERE $cpfQuery = :cpf LIMIT 1");
                    $stmt->bindValue(':cpf', $cpf);
                    $stmt->execute();
                    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                        http_response_code(409);
                        echo json_encode(['existe' => true, 'campo' => 'cpf']);
                        return;
                    }
                } catch (Exception $e) {
                    
                }
            }

            
            http_response_code(200);
            echo json_encode(['existe' => false]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao verificar existência']);
        }
    }

    

    public function primeiroAcesso()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];

            $nome = trim($input['nome'] ?? '');
            $email = trim($input['email'] ?? '');
            $telefone = trim($input['telefone'] ?? '') ?: null;
            $cpf = preg_replace('/\D/', '', $input['cpf'] ?? '');
            $cpfQuery = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', ''), '(', ''), ')', '')";

            if ($nome === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['erro' => 'Nome e e-mail válidos são obrigatórios']);
                return;
            }

            
            $stmt = $this->conn->prepare('SELECT id_usuario, nome, telefone FROM usuario WHERE email = :email LIMIT 1');
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            $usuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);

            
            $cpfExistente = false;
            if ($cpf) {
                $stmt = $this->conn->prepare("SELECT id_usuario FROM usuario WHERE $cpfQuery = :cpf LIMIT 1");
                $stmt->bindValue(':cpf', $cpf);
                $stmt->execute();
                $cpfExistente = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
            }

            
            if ($usuarioExistente || $cpfExistente) {
                http_response_code(409);
                echo json_encode([
                    'existe' => true,
                    'campo' => $usuarioExistente ? 'email' : 'cpf',
                    'erro' => 'Email ou CPF já cadastrados'
                ]);
                return;
            }

            
            $novoUsuario = true;
            $senhaHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

            $stmtInsert = $this->conn->prepare('
                INSERT INTO usuario (nome, email, senha_hash, telefone, cpf, ativo, criado_em)
                VALUES (:nome, :email, :senha_hash, :telefone, :cpf, 1, NOW())
            ');
            $stmtInsert->bindValue(':nome', $nome);
            $stmtInsert->bindValue(':email', $email);
            $stmtInsert->bindValue(':senha_hash', $senhaHash);
            $stmtInsert->bindValue(':telefone', $telefone);
            $stmtInsert->bindValue(':cpf', $cpf ?: null);
            $stmtInsert->execute();

            $idUsuario = (int)$this->conn->lastInsertId();

            
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $stmtToken = $this->conn->prepare('
                INSERT INTO password_reset (id_usuario, token, expira_em)
                VALUES (:id_usuario, :token, :expira_em)
            ');
            $stmtToken->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
            $stmtToken->bindValue(':token', $token);
            $stmtToken->bindValue(':expira_em', $expira);
            $stmtToken->execute();

            
            Mailer::enviarPrimeiroAcesso($email, $nome, $token);

            http_response_code($novoUsuario ? 201 : 200);
            echo json_encode([
                'sucesso' => true,
                'id_usuario' => $idUsuario,
                'novo' => $novoUsuario,
                'mensagem' => $novoUsuario ? 'Usuário criado e convite enviado' : 'Usuário já existe'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'erro' => 'Erro ao processar primeiro acesso',
                'mensagem' => $e->getMessage()
            ]);
        }
    }
}
