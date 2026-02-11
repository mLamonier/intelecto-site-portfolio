<?php

class HomepageUploadController
{
    public function handleUpload()
    {
        http_response_code(400);
        echo json_encode([
            'erro' => 'Upload desativado. Selecione um arquivo j?? existente em assets.'
        ]);
    }
}
