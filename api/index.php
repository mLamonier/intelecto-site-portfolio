<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/site.php';

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    header("Access-Control-Allow-Origin: {$origin}");
    header("Access-Control-Allow-Credentials: true");
} else {
header("Access-Control-Allow-Origin: *");
}
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");
header("Vary: Origin, Cookie, Accept-Encoding");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header("Cache-Control: private, max-age=60, must-revalidate");
} else {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/controllers/CursoController.php';
    require_once __DIR__ . '/controllers/CategoriaController.php';
    require_once __DIR__ . '/controllers/GradeController.php';
    require_once __DIR__ . '/controllers/UsuarioController.php';
    require_once __DIR__ . '/controllers/PedidoController.php';
    require_once __DIR__ . '/controllers/PagamentoController.php';
    require_once __DIR__ . '/controllers/ConfigController.php';
    require_once __DIR__ . '/controllers/HomepageController.php';
    require_once __DIR__ . '/controllers/HomepageUploadController.php';
    require_once __DIR__ . '/controllers/GoogleReviewsController.php';
    require_once __DIR__ . '/services/PagBankService.php';

    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => 'Erro ao inicializar API',
        'mensagem' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

$route = $_GET['route'] ?? null;

if ($route) {
    
    $route = explode('?', $route)[0];

    
    $segments = explode('/', trim($route, '/'));
} else {
    
    $requestUri = trim($_SERVER['REQUEST_URI'], '/');
    $requestUri = explode('?', $requestUri)[0];
    $segments = explode('/', $requestUri);

    
    $baseSegment = trim(site_base_path(), '/');
    $segments = array_filter($segments, function ($seg) use ($baseSegment) {
        if ($seg === '' || $seg === 'api' || $seg === 'index.php') {
            return false;
        }
        if ($baseSegment !== '' && $seg === $baseSegment) {
            return false;
        }
        return true;
    });
    $segments = array_values($segments);

function api_is_admin(): bool
{
    if (!empty($_SESSION['admin_logado'])) {
        return true;
    }
    $roles = $_SESSION['usuario_roles'] ?? [];
    return is_array($roles) && in_array('ADMIN', $roles, true);
}

function api_is_logged(): bool
{
    return !empty($_SESSION['usuario_logado']);
}

function api_deny(string $message, int $code = 403): void
{
    http_response_code($code);
    echo json_encode(['erro' => $message]);
    exit;
}

function api_is_public_mutation(array $segments, string $method): bool
{
    if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        return false;
    }

    $root = $segments[0] ?? '';
    $sub = $segments[1] ?? '';

    
    if ($root === 'usuarios' && $method === 'POST') {
        return $sub === '' || $sub === 'verificar' || $sub === 'primeiro-acesso';
    }

    if ($root === 'pedidos' && $method === 'POST') {
        return true;
    }

    if ($root === 'pagamentos' && $method === 'POST') {
        return true;
    }

    return false;
}

function api_guard(array $segments, string $method): void
{
    $root = $segments[0] ?? '';
    $sub = $segments[1] ?? '';

    if (api_is_public_mutation($segments, $method)) {
        return;
    }

    if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        if (!api_is_admin()) {
            api_deny('Acesso restrito: admin requerido', 401);
        }
        return;
    }

    
    if ($root === 'usuarios') {
        if (!api_is_admin()) {
            api_deny('Acesso restrito: admin requerido', 401);
        }
    }

    if ($root === 'pedidos') {
        if (!api_is_admin() && !api_is_logged()) {
            api_deny('Acesso restrito: login requerido', 401);
        }
    }

    if ($root === 'google-reviews' && $sub === 'status') {
        if (!api_is_admin()) {
            api_deny('Acesso restrito: admin requerido', 401);
        }
    }
}

api_guard($segments, $method);

}

if (isset($segments[0]) && $segments[0] === 'google-reviews') {
    $controller = new GoogleReviewsController();

    
    if ($method === 'GET' && !isset($segments[1])) {
        $controller->getReviews();

        
    } elseif ($method === 'GET' && isset($segments[1]) && $segments[1] === 'status') {
        $controller->getStatus();

        
    } elseif ($method === 'POST' && isset($segments[1]) && $segments[1] === 'clear-cache') {
        $controller->clearCache();
    } else {
        http_response_code(404);
        echo json_encode(["erro" => "Rota nÃ£o encontrada"]);
    }
}

elseif (isset($segments[0]) && $segments[0] === 'cursos') {
    $controller = new CursoController($db);

    
    if ($method === 'GET' && !isset($segments[1])) {
        $controller->index();

        
    } elseif ($method === 'GET' && isset($segments[1]) && is_numeric($segments[1])) {
        $controller->buscarPorId($segments[1]);

        
    } elseif ($method === 'POST' && !isset($segments[1])) {
        $controller->criar();

        
    } elseif ($method === 'PUT' && isset($segments[1]) && is_numeric($segments[1])) {
        $controller->atualizar($segments[1]);

        
    } elseif ($method === 'DELETE' && isset($segments[1]) && is_numeric($segments[1])) {
        $controller->excluir($segments[1]);
    } else {
        http_response_code(404);
        echo json_encode(["erro" => "Rota nÃ£o encontrada"]);
    }
}

elseif (isset($segments[0]) && $segments[0] === 'categorias') {
    $controller = new CategoriaController($db);

    if ($method === 'GET' && !isset($segments[1])) {
        $controller->index();
    } elseif ($method === 'GET' && isset($segments[1]) && $segments[1] === 'todas') {
        $controller->todas();
    } elseif ($method === 'GET' && isset($segments[1]) && is_numeric($segments[1])) {
        $controller->show($segments[1]);
    } elseif ($method === 'POST') {
        $controller->store();
    } elseif ($method === 'PUT' && isset($segments[1]) && is_numeric($segments[1])) {
        $controller->update($segments[1]);
    } elseif ($method === 'DELETE' && isset($segments[1]) && is_numeric($segments[1])) {
        $controller->destroy($segments[1]);
    } else {
        http_response_code(404);
        echo json_encode(["erro" => "Rota nÃ£o encontrada"]);
    }
}

elseif (isset($segments[0]) && $segments[0] === 'grades') {
    $controller = new GradeController($db);

    
    if ($method === 'GET' && !isset($segments[1])) {
        $controller->index();

        
    } elseif ($method === 'GET' && isset($segments[1]) && !is_numeric($segments[1]) && !isset($segments[2])) {
        $controller->show($segments[1]);

        
    } elseif ($method === 'GET' && isset($segments[1]) && is_numeric($segments[1]) && !isset($segments[2])) {
        $controller->buscarPorId($segments[1]);

        
    } elseif (
        $method === 'GET' && isset($segments[1]) && is_numeric($segments[1]) &&
        isset($segments[2]) && $segments[2] === 'cursos' && !isset($segments[3])
    ) {
        $controller->listarCursos($segments[1]);

        
    } elseif ($method === 'POST' && !isset($segments[1])) {
        $controller->create();

        
    } elseif (
        $method === 'POST' && isset($segments[1]) && is_numeric($segments[1]) &&
        isset($segments[2]) && $segments[2] === 'cursos' && !isset($segments[3])
    ) {
        $controller->adicionarCurso($segments[1]);

        
    } elseif (
        $method === 'PUT' && isset($segments[1]) && is_numeric($segments[1]) &&
        isset($segments[2]) && $segments[2] === 'cursos' &&
        isset($segments[3]) && $segments[3] === 'reordenar'
    ) {
        $controller->reordenarCursos($segments[1]);

        
    } elseif (
        $method === 'PUT' && isset($segments[1]) && is_numeric($segments[1]) &&
        isset($segments[2]) && $segments[2] === 'cursos' &&
        isset($segments[3]) && is_numeric($segments[3])
    ) {
        $controller->atualizarCurso($segments[1], $segments[3]);

        
    } elseif ($method === 'PUT' && isset($segments[1]) && is_numeric($segments[1]) && !isset($segments[2])) {
        $controller->update($segments[1]);

        
    } elseif ($method === 'DELETE' && isset($segments[1]) && is_numeric($segments[1]) && !isset($segments[2])) {
        $controller->destroy($segments[1]);

        
    } elseif (
        $method === 'DELETE' && isset($segments[1]) && is_numeric($segments[1]) &&
        isset($segments[2]) && $segments[2] === 'cursos' &&
        isset($segments[3]) && is_numeric($segments[3])
    ) {
        $controller->removerCurso($segments[1], $segments[3]);
    } else {
        http_response_code(404);
        echo json_encode(["erro" => "Rota nÃ£o encontrada"]);
    }
}

elseif (isset($segments[0]) && $segments[0] === 'usuarios') {
    $controller = new UsuarioController($db);

    if ($method === 'POST' && isset($segments[1]) && $segments[1] === 'verificar') {
        $controller->verificarExistencia();
    } elseif ($method === 'POST' && isset($segments[1]) && $segments[1] === 'primeiro-acesso') {
        $controller->primeiroAcesso();
    } elseif ($method === 'GET' && !isset($segments[1])) {
        $controller->index();
    } elseif ($method === 'GET' && isset($segments[1]) && is_numeric($segments[1])) {
        $controller->show($segments[1]);
    } elseif ($method === 'POST' && !isset($segments[1])) {
        $controller->store();
    } elseif ($method === 'PUT' && isset($segments[1]) && is_numeric($segments[1])) {
        $controller->update($segments[1]);
    } elseif ($method === 'DELETE' && isset($segments[1]) && is_numeric($segments[1])) {
        $controller->destroy($segments[1]);
    } else {
        http_response_code(404);
        echo json_encode(["erro" => "Rota nÃ£o encontrada"]);
    }
}

elseif (isset($segments[0]) && $segments[0] === 'pedidos') {
    $controller = new PedidoController($db);

    if ($method === 'POST') {
        $controller->store();
    } elseif ($method === 'GET' && !isset($segments[1])) {
        $controller->index();
    } elseif ($method === 'GET' && isset($segments[1]) && is_numeric($segments[1])) {
        $controller->show($segments[1]);
    } elseif ($method === 'PUT' && isset($segments[1]) && is_numeric($segments[1])) {
        $controller->update($segments[1]);
    } elseif ($method === 'DELETE' && isset($segments[1]) && is_numeric($segments[1])) {
        $controller->destroy($segments[1]);
    } else {
        http_response_code(404);
        echo json_encode(["erro" => "Rota nÃ£o encontrada"]);
    }
}

elseif (isset($segments[0]) && $segments[0] === 'pagamentos') {
    $controller = new PagamentoController($db);

    if ($method === 'POST' && !isset($segments[1])) {
        
        $controller->store();
    } elseif (
        $method === 'POST' &&
        isset($segments[1]) && $segments[1] === 'pedido' &&
        isset($segments[2]) && is_numeric($segments[2]) &&
        isset($segments[3]) && $segments[3] === 'aprovar-teste'
    ) {
        $controller->approveForDevelopmentByPedido((int)$segments[2]);
    } elseif (
        $method === 'GET' &&
        isset($segments[1]) && $segments[1] === 'pedido' &&
        isset($segments[2]) && is_numeric($segments[2]) &&
        isset($segments[3]) && $segments[3] === 'ultimo'
    ) {
        $controller->latestByPedido((int)$segments[2]);
    } elseif ($method === 'GET' && isset($segments[1]) && is_numeric($segments[1])) {
        
        $controller->show($segments[1]);
    } else {
        http_response_code(404);
        echo json_encode(["erro" => "Rota nÃ£o encontrada"]);
    }
}

elseif (isset($segments[0]) && $segments[0] === 'config') {
    $controller = new ConfigController($db);

    if ($method === 'GET') {
        $controller->index();
    } elseif ($method === 'PUT') {
        $controller->update();
    } else {
        http_response_code(404);
        echo json_encode(["erro" => "Rota nÃ£o encontrada"]);
    }
}

elseif (isset($segments[0]) && $segments[0] === 'homepage') {
    
    if (isset($segments[1]) && $segments[1] === 'upload') {
        $uploadController = new HomepageUploadController();
        $uploadController->handleUpload();
    } else {
        $controller = new HomepageController($db);

        
        if (isset($segments[1]) && $segments[1] === 'banners') {
            if ($method === 'GET' && isset($segments[2]) && $segments[2] === 'all') {
                $resultado = $controller->getAllBanners();
                echo json_encode($resultado);
            } elseif ($method === 'GET') {
                $resultado = $controller->getBanners();
                echo json_encode($resultado);
            } elseif ($method === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                if ($controller->createBanner($data)) {
                    echo json_encode(["success" => true, "msg" => "Banner criado"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["erro" => "Erro ao criar banner"]);
                }
            } elseif ($method === 'PUT' && isset($segments[2]) && is_numeric($segments[2])) {
                $data = json_decode(file_get_contents("php://input"), true);
                if ($controller->updateBanner($segments[2], $data)) {
                    echo json_encode(["success" => true, "msg" => "Banner atualizado"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["erro" => "Erro ao atualizar banner"]);
                }
            } elseif ($method === 'DELETE' && isset($segments[2]) && is_numeric($segments[2])) {
                if ($controller->deleteBanner($segments[2])) {
                    echo json_encode(["success" => true, "msg" => "Banner deletado"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["erro" => "Erro ao deletar banner"]);
                }
            }
        }
        
        elseif (isset($segments[1]) && $segments[1] === 'grades-carousel') {
            if ($method === 'GET') {
                $resultado = $controller->getGradesCarousel();
                echo json_encode($resultado);
            } elseif ($method === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                $id_grade = $data['id_grade'] ?? null;
                $ordem = $data['ordem'] ?? 0;
                $ativo = $data['ativo'] ?? 1;
                if ($id_grade && $controller->addGradeToCarousel($id_grade, $ordem, $ativo)) {
                    echo json_encode(["success" => true, "msg" => "Grade adicionada"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["erro" => "Erro ao adicionar grade"]);
                }
            } elseif ($method === 'DELETE' && isset($segments[2]) && is_numeric($segments[2])) {
                if ($controller->removeGradeFromCarousel($segments[2])) {
                    echo json_encode(["success" => true, "msg" => "Grade removida"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["erro" => "Erro ao remover grade"]);
                }
            } elseif ($method === 'PUT' && isset($segments[2]) && is_numeric($segments[2])) {
                $data = json_decode(file_get_contents("php://input"), true);
                if ($controller->updateGradeCarousel($segments[2], $data)) {
                    echo json_encode(["success" => true, "msg" => "Grade atualizada"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["erro" => "Erro ao atualizar grade"]);
                }
            }
        }
        
        elseif (isset($segments[1]) && $segments[1] === 'stats') {
            if ($method === 'GET') {
                $resultado = $controller->getStats();
                echo json_encode($resultado);
            } elseif ($method === 'PUT' && isset($segments[2])) {
                $data = json_decode(file_get_contents("php://input"), true);
                if ($controller->updateStat($segments[2], $data)) {
                    echo json_encode(["success" => true, "msg" => "Estatí­stica atualizada"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["erro" => "Erro ao atualizar estatí­stica"]);
                }
            }
        }
        
        elseif (isset($segments[1]) && $segments[1] === 'categories') {
            if ($method === 'GET') {
                $resultado = $controller->getCategoriesHomepage();
                echo json_encode($resultado);
            } elseif ($method === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                $id_categoria = $data['id_categoria'] ?? null;
                $imagem = $data['imagem'] ?? null;
                $ordem = $data['ordem'] ?? 0;
                $ativo = $data['ativo'] ?? 1;
                if ($id_categoria && $controller->createCategory($id_categoria, $imagem, $ordem, $ativo)) {
                    echo json_encode(["success" => true, "msg" => "Categoria adicionada"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["erro" => "Erro ao adicionar categoria"]);
                }
            } elseif ($method === 'PUT' && isset($segments[2]) && is_numeric($segments[2])) {
                $data = json_decode(file_get_contents("php://input"), true);
                if ($controller->updateCategory($segments[2], $data)) {
                    echo json_encode(["success" => true, "msg" => "Categoria atualizada"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["erro" => "Erro ao atualizar categoria"]);
                }
            } elseif ($method === 'DELETE' && isset($segments[2]) && is_numeric($segments[2])) {
                if ($controller->deleteCategory($segments[2])) {
                    echo json_encode(["success" => true, "msg" => "Categoria deletada"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["erro" => "Erro ao deletar categoria"]);
                }
            }
        }
        
        elseif (isset($segments[1]) && $segments[1] === 'testimonials') {
            if ($method === 'GET') {
                $resultado = $controller->getTestimonials();
                echo json_encode($resultado);
            } elseif ($method === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                if ($controller->createTestimonial($data)) {
                    echo json_encode(["success" => true, "msg" => "Depoimento criado"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["erro" => "Erro ao criar depoimento"]);
                }
            } elseif ($method === 'PUT' && isset($segments[2]) && is_numeric($segments[2])) {
                $data = json_decode(file_get_contents("php://input"), true);
                if ($controller->updateTestimonial($segments[2], $data)) {
                    echo json_encode(["success" => true, "msg" => "Depoimento atualizado"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["erro" => "Erro ao atualizar depoimento"]);
                }
            } elseif ($method === 'DELETE' && isset($segments[2]) && is_numeric($segments[2])) {
                if ($controller->deleteTestimonial($segments[2])) {
                    echo json_encode(["success" => true, "msg" => "Depoimento deletado"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["erro" => "Erro ao deletar depoimento"]);
                }
            }
        }
        
        elseif (isset($segments[1]) && $segments[1] === 'faq') {
            if ($method === 'GET') {
                $resultado = $controller->getFAQ();
                echo json_encode($resultado);
            } elseif ($method === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                $pergunta = $data['pergunta'] ?? null;
                $resposta = $data['resposta'] ?? null;
                $ordem = $data['ordem'] ?? 0;
                $ativo = $data['ativo'] ?? 1;
                if ($pergunta && $resposta && $controller->createFAQ($pergunta, $resposta, $ordem, $ativo)) {
                    echo json_encode(["success" => true, "msg" => "FAQ criada"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["erro" => "Erro ao criar FAQ"]);
                }
            } elseif ($method === 'PUT' && isset($segments[2]) && is_numeric($segments[2])) {
                $data = json_decode(file_get_contents("php://input"), true);
                if ($controller->updateFAQ($segments[2], $data)) {
                    echo json_encode(["success" => true, "msg" => "FAQ atualizada"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["erro" => "Erro ao atualizar FAQ"]);
                }
            } elseif ($method === 'DELETE' && isset($segments[2]) && is_numeric($segments[2])) {
                if ($controller->deleteFAQ($segments[2])) {
                    echo json_encode(["success" => true, "msg" => "FAQ deletada"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["erro" => "Erro ao deletar FAQ"]);
                }
            }
        } else {
            http_response_code(404);
            echo json_encode(["erro" => "Endpoint de homepage nÃ£o encontrado"]);
        }
    }
}

elseif (isset($segments[0]) && $segments[0] === 'cursos-personalizados') {
    $controller = new CursoController($db);

    if ($method === 'GET') {
        $controller->cursosPorsonalizados();
    } else {
        http_response_code(405);
        echo json_encode(["erro" => "MÃ©todo nÃ£o permitido"]);
    }
}

elseif (isset($segments[0]) && $segments[0] === 'configuracoes') {
    $controller = new ConfigController($db);

    if ($method === 'GET' && !isset($segments[1])) {
        
        $controller->index();
    } elseif ($method === 'GET' && isset($segments[1]) && $segments[1] === 'valor-hora') {
        
        $controller->valorHora();
    } elseif ($method === 'POST') {
        
        $controller->update();
    } else {
        http_response_code(404);
        echo json_encode(["erro" => "Rota de configuraÃ§Ã£o nÃ£o encontrada"]);
    }
}

else {
    http_response_code(404);
    echo json_encode([
        "erro" => "Recurso nÃ£o encontrado",
        "uri" => $_SERVER['REQUEST_URI'],
        "route_param" => $route,
        "segments" => $segments
    ]);
}
