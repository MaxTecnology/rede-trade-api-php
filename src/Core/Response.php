<?php

namespace Core;

class Response
{
    public function status(int $code): void
    {
        http_response_code($code);
    }

    public function body(array $payload)
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function json(int $code, array $payload): void
    {
        http_response_code($code);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
    
    public function error(int $code, string $message): void
    {
        http_response_code($code);
        echo $message;
    }
}
