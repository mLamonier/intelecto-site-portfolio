<?php

class HomepageController
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    
    public function getBanners()
    {
        $query = "SELECT * FROM homepage_banner WHERE ativo = 1 ORDER BY ordem ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllBanners()
    {
        $query = "SELECT * FROM homepage_banner ORDER BY ordem ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function createBanner($data)
    {
        $imagem = $data['imagem'];
        $imagem_mobile = isset($data['imagem_mobile']) ? $data['imagem_mobile'] : null;
        $titulo = isset($data['titulo']) ? $data['titulo'] : null;
        $link = isset($data['link']) ? $data['link'] : null;
        $ordem = isset($data['ordem']) ? (int)$data['ordem'] : 0;
        $ativo = isset($data['ativo']) ? (int)$data['ativo'] : 1;

        $query = "INSERT INTO homepage_banner (imagem, imagem_mobile, titulo, link, ordem, ativo) VALUES (:imagem, :imagem_mobile, :titulo, :link, :ordem, :ativo)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':imagem' => $imagem,
            ':imagem_mobile' => $imagem_mobile,
            ':titulo' => $titulo,
            ':link' => $link,
            ':ordem' => $ordem,
            ':ativo' => $ativo
        ]);
    }

    public function updateBanner($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        if (isset($data['imagem'])) {
            $fields[] = "imagem = :imagem";
            $params[':imagem'] = $data['imagem'];
        }
        if (isset($data['imagem_mobile'])) {
            $fields[] = "imagem_mobile = :imagem_mobile";
            $params[':imagem_mobile'] = $data['imagem_mobile'];
        }
        if (isset($data['titulo'])) {
            $fields[] = "titulo = :titulo";
            $params[':titulo'] = $data['titulo'];
        }
        if (isset($data['link'])) {
            $fields[] = "link = :link";
            $params[':link'] = $data['link'];
        }
        if (isset($data['ordem'])) {
            $fields[] = "ordem = :ordem";
            $params[':ordem'] = (int)$data['ordem'];
        }
        if (isset($data['ativo'])) {
            $fields[] = "ativo = :ativo";
            $params[':ativo'] = (int)$data['ativo'];
        }

        if (empty($fields)) return false;

        $query = "UPDATE homepage_banner SET " . implode(", ", $fields) . " WHERE id_banner = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }

    public function deleteBanner($id)
    {
        $query = "DELETE FROM homepage_banner WHERE id_banner = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':id' => $id]);
    }

    
    public function getGradesCarousel()
    {
        $query = "SELECT hgc.id, hgc.id_grade, hgc.ordem, hgc.ativo, g.nome, g.descricao_curta, g.preco_avista as preco, g.imagem_card
                  FROM homepage_grades_carousel hgc
                  JOIN grade g ON hgc.id_grade = g.id_grade
                  WHERE hgc.ativo = 1
                  ORDER BY hgc.ordem ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function addGradeToCarousel($id_grade, $ordem = 0, $ativo = 1)
    {
        $query = "INSERT INTO homepage_grades_carousel (id_grade, ordem, ativo) VALUES (:id_grade, :ordem, :ativo)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':id_grade' => $id_grade, ':ordem' => $ordem, ':ativo' => $ativo]);
    }

    public function removeGradeFromCarousel($id)
    {
        $query = "DELETE FROM homepage_grades_carousel WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':id' => $id]);
    }

    public function updateGradeCarouselOrder($id, $ordem)
    {
        $query = "UPDATE homepage_grades_carousel SET ordem = :ordem WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':ordem' => $ordem, ':id' => $id]);
    }

    public function updateGradeCarousel($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        if (isset($data['ordem'])) {
            $fields[] = "ordem = :ordem";
            $params[':ordem'] = (int)$data['ordem'];
        }
        if (isset($data['ativo'])) {
            $fields[] = "ativo = :ativo";
            $params[':ativo'] = (int)$data['ativo'];
        }

        if (empty($fields)) return false;

        $query = "UPDATE homepage_grades_carousel SET " . implode(", ", $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }

    
    public function getStats()
    {
        $query = "SELECT * FROM homepage_stats ORDER BY ordem ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function updateStat($tipo, $data)
    {
        $fields = [];
        $params = [':tipo' => $tipo];

        if (isset($data['valor'])) {
            $fields[] = "valor = :valor";
            $params[':valor'] = (int)$data['valor'];
        }
        if (isset($data['label'])) {
            $fields[] = "label = :label";
            $params[':label'] = $data['label'];
        }
        if (isset($data['imagem'])) {
            $fields[] = "imagem = :imagem";
            $params[':imagem'] = $data['imagem'];
        }

        if (empty($fields)) return false;

        $query = "UPDATE homepage_stats SET " . implode(", ", $fields) . " WHERE tipo = :tipo";
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }

    
    public function getCategoriesHomepage()
    {
        $query = "SELECT hc.id_categoria_homepage, hc.id_categoria, hc.imagem, hc.ordem, hc.ativo,
                         c.nome, c.descricao
                  FROM homepage_categories hc
                  JOIN categoria c ON hc.id_categoria = c.id_categoria
                  WHERE hc.ativo = 1
                  ORDER BY hc.ordem ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function createCategory($id_categoria, $imagem = null, $ordem = 0, $ativo = 1)
    {
        $query = "INSERT INTO homepage_categories (id_categoria, imagem, ordem, ativo) 
                  VALUES (:id_categoria, :imagem, :ordem, :ativo)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':id_categoria' => $id_categoria, ':imagem' => $imagem, ':ordem' => $ordem, ':ativo' => $ativo]);
    }

    public function updateCategory($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];
        $hasNome = array_key_exists('nome', $data);
        $nome = $hasNome ? trim((string)$data['nome']) : null;

        if (isset($data['id_categoria'])) {
            $fields[] = "id_categoria = :id_categoria";
            $params[':id_categoria'] = (int)$data['id_categoria'];
        }
        if (isset($data['imagem'])) {
            $fields[] = "imagem = :imagem";
            $params[':imagem'] = $data['imagem'];
        }
        if (isset($data['ordem'])) {
            $fields[] = "ordem = :ordem";
            $params[':ordem'] = (int)$data['ordem'];
        }
        if (isset($data['ativo'])) {
            $fields[] = "ativo = :ativo";
            $params[':ativo'] = (int)$data['ativo'];
        }

        if (empty($fields) && !$hasNome) {
            return false;
        }

        if ($hasNome && $nome === '') {
            return false;
        }

        try {
            $this->db->beginTransaction();

            if ($hasNome) {
                $queryNome = "UPDATE categoria c
                              JOIN homepage_categories hc ON hc.id_categoria = c.id_categoria
                              SET c.nome = :nome
                              WHERE hc.id_categoria_homepage = :id";
                $stmtNome = $this->db->prepare($queryNome);
                $stmtNome->execute([
                    ':nome' => $nome,
                    ':id' => $id
                ]);
            }

            if (!empty($fields)) {
                $query = "UPDATE homepage_categories SET " . implode(", ", $fields) . " WHERE id_categoria_homepage = :id";
                $stmt = $this->db->prepare($query);
                $stmt->execute($params);
            }

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function deleteCategory($id)
    {
        $query = "DELETE FROM homepage_categories WHERE id_categoria_homepage = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':id' => $id]);
    }

    
    public function getTestimonials()
    {
        $query = "SELECT * FROM homepage_testimonials WHERE ativo = 1 ORDER BY ordem ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function createTestimonial($data)
    {
        if (!is_array($data)) return false;

        $foto = trim((string)($data['midia'] ?? ($data['foto'] ?? '')));
        $nome_aluno = trim((string)($data['nome_aluno'] ?? ''));
        $curso = trim((string)($data['curso'] ?? ''));
        $mensagem = trim((string)($data['mensagem'] ?? ''));
        $ordem = isset($data['ordem']) ? (int)$data['ordem'] : 0;
        $ativo = isset($data['ativo']) ? (int)$data['ativo'] : 1;

        if ($foto === '') return false;

        $query = "INSERT INTO homepage_testimonials (foto, nome_aluno, curso, mensagem, ordem, ativo) 
                  VALUES (:foto, :nome_aluno, :curso, :mensagem, :ordem, :ativo)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':foto' => $foto,
            ':nome_aluno' => $nome_aluno,
            ':curso' => $curso,
            ':mensagem' => $mensagem,
            ':ordem' => $ordem,
            ':ativo' => $ativo
        ]);
    }

    public function updateTestimonial($id, $data)
    {
        if (!is_array($data)) return false;

        $fields = [];
        $params = [':id' => $id];

        if (array_key_exists('foto', $data) || array_key_exists('midia', $data)) {
            $fields[] = "foto = :foto";
            $params[':foto'] = trim((string)($data['midia'] ?? $data['foto']));
        }
        if (array_key_exists('nome_aluno', $data)) {
            $fields[] = "nome_aluno = :nome_aluno";
            $params[':nome_aluno'] = $data['nome_aluno'];
        }
        if (array_key_exists('curso', $data)) {
            $fields[] = "curso = :curso";
            $params[':curso'] = $data['curso'];
        }
        if (array_key_exists('mensagem', $data)) {
            $fields[] = "mensagem = :mensagem";
            $params[':mensagem'] = $data['mensagem'];
        }
        if (isset($data['ordem'])) {
            $fields[] = "ordem = :ordem";
            $params[':ordem'] = (int)$data['ordem'];
        }
        if (isset($data['ativo'])) {
            $fields[] = "ativo = :ativo";
            $params[':ativo'] = (int)$data['ativo'];
        }

        if (empty($fields)) return false;

        $query = "UPDATE homepage_testimonials SET " . implode(", ", $fields) . " WHERE id_testimonial = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }

    public function deleteTestimonial($id)
    {
        $query = "DELETE FROM homepage_testimonials WHERE id_testimonial = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':id' => $id]);
    }

    
    public function getFAQ()
    {
        $query = "SELECT * FROM homepage_faq WHERE ativo = 1 ORDER BY ordem ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function createFAQ($pergunta, $resposta, $ordem = 0, $ativo = 1)
    {
        $query = "INSERT INTO homepage_faq (pergunta, resposta, ordem, ativo) VALUES (:pergunta, :resposta, :ordem, :ativo)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':pergunta' => $pergunta, ':resposta' => $resposta, ':ordem' => $ordem, ':ativo' => $ativo]);
    }

    public function updateFAQ($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        if (isset($data['pergunta'])) {
            $fields[] = "pergunta = :pergunta";
            $params[':pergunta'] = $data['pergunta'];
        }
        if (isset($data['resposta'])) {
            $fields[] = "resposta = :resposta";
            $params[':resposta'] = $data['resposta'];
        }
        if (isset($data['ordem'])) {
            $fields[] = "ordem = :ordem";
            $params[':ordem'] = (int)$data['ordem'];
        }
        if (isset($data['ativo'])) {
            $fields[] = "ativo = :ativo";
            $params[':ativo'] = (int)$data['ativo'];
        }

        if (empty($fields)) return false;

        $query = "UPDATE homepage_faq SET " . implode(", ", $fields) . " WHERE id_faq = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }

    public function deleteFAQ($id)
    {
        $query = "DELETE FROM homepage_faq WHERE id_faq = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':id' => $id]);
    }
}
