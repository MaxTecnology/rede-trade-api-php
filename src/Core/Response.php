<?php

namespace Core;

class Response
{
    public function status(int $code)
    {
        http_response_code($code);
        return $this; 
    }

    public function body(array $payload)
    {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        return $this; 
    }

    public function error(int $code, string $message)
    {
        return $this->status($code)->body(['error' => $message]);
    }

    public function json(array $payload)
    {
        return $this->body($payload);
    }
}
