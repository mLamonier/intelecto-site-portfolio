<?php

require_once __DIR__ . '/../services/GooglePlacesService.php';

class GoogleReviewsController
{
    private $service;

    public function __construct()
    {
        $this->service = new GooglePlacesService();
    }

    

    public function getReviews()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $reviews = $this->service->getReviews();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $reviews
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erro ao buscar avaliações. Verifique a configuração da API do Google.'
            ]);
        }
    }

    

    public function clearCache()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $cleared = $this->service->clearCache();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => $cleared ? 'Cache limpo com sucesso' : 'Nenhum cache para limpar'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    

    public function getStatus()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $status = $this->service->getConfigStatus();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $status
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
