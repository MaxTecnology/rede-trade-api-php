<?php

namespace Core;

class Response
{
    public function status(int $code): void
    {
        http_response_code($code);
    }

    public function body(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
