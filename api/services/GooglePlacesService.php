<?php

class GooglePlacesService
{
    private $config;
    private $cacheFile;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config/google.php';

        
        if (!file_exists($this->config['cache_dir'])) {
            mkdir($this->config['cache_dir'], 0755, true);
        }

        $this->cacheFile = $this->config['cache_dir'] . '/reviews_cache.json';
    }

    

    public function getReviews()
    {
        
        $cached = $this->getCachedReviews();
        if ($cached !== null) {
            return $cached;
        }

        
        try {
            $reviews = $this->fetchFromGoogleAPI();

            
            $this->saveCache($reviews);

            return $reviews;
        } catch (Exception $e) {
            
            $expired = $this->getCachedReviews(true);
            if ($expired !== null) {
                return $expired;
            }

            throw $e;
        }
    }

    

    private function fetchFromGoogleAPI()
    {
        $apiKey = $this->config['api_key'];
        $placeId = $this->config['place_id'];

        if (empty($apiKey) || empty($placeId)) {
            throw new Exception('API Key ou Place ID não configurados. Verifique o arquivo api/config/google.php');
        }

        $url = "https://maps.googleapis.com/maps/api/place/details/json";
        $params = [
            'place_id' => $placeId,
            'fields' => $this->config['fields'],
            'key' => $apiKey,
            'language' => 'pt-BR'
        ];

        $url .= '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Erro ao conectar com Google API: " . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception("Google API retornou código: " . $httpCode);
        }

        $data = json_decode($response, true);

        if (!isset($data['status']) || $data['status'] !== 'OK') {
            $errorMsg = $data['error_message'] ?? $data['status'] ?? 'Erro desconhecido';
            throw new Exception("Erro na API do Google: " . $errorMsg);
        }

        return $this->formatResponse($data['result']);
    }

    

    private function formatResponse($result)
    {
        return [
            'name' => $result['name'] ?? '',
            'rating' => $result['rating'] ?? 0,
            'total_ratings' => $result['user_ratings_total'] ?? 0,
            'google_url' => $result['url'] ?? '',
            'reviews' => array_map(function ($review) {
                return [
                    'author' => $review['author_name'] ?? 'Anônimo',
                    'rating' => $review['rating'] ?? 0,
                    'text' => $review['text'] ?? '',
                    'time' => $review['time'] ?? 0,
                    'relative_time' => $review['relative_time_description'] ?? '',
                    'profile_photo' => $review['profile_photo_url'] ?? ''
                ];
            }, $result['reviews'] ?? []),
            'cached_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', time() + $this->config['cache_duration'])
        ];
    }

    

    private function getCachedReviews($ignoreExpiration = false)
    {
        if (!file_exists($this->cacheFile)) {
            return null;
        }

        $content = file_get_contents($this->cacheFile);
        $data = json_decode($content, true);

        if (!$data) {
            return null;
        }

        
        if (!$ignoreExpiration) {
            $expiresAt = strtotime($data['expires_at'] ?? '1970-01-01');
            if (time() > $expiresAt) {
                return null;
            }
        }

        return $data;
    }

    

    private function saveCache($data)
    {
        file_put_contents($this->cacheFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    

    public function clearCache()
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
            return true;
        }
        return false;
    }

    

    public function getConfigStatus()
    {
        return [
            'api_key_configured' => !empty($this->config['api_key']),
            'place_id_configured' => !empty($this->config['place_id']),
            'cache_dir_writable' => is_writable($this->config['cache_dir']),
            'cache_exists' => file_exists($this->cacheFile),
            'cache_valid' => $this->getCachedReviews() !== null
        ];
    }
}
