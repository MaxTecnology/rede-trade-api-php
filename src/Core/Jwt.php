<?php

namespace Core;

class Jwt
{
    private $secret;

    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    public function createToken($payload)    {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', "$header.$payload", $this->secret, true);
        $encodedSignature = base64_encode($signature);
        return "$header.$payload.$encodedSignature";
    }

    public function decodeToken($token)
    {
        list($headerBase64, $payloadBase64, $signatureBase64) = explode('.', $token);
        $header = json_decode(base64_decode($headerBase64), true);
        $payload = json_decode(base64_decode($payloadBase64), true);
        $signature = base64_decode($signatureBase64);
        $expectedSignature = hash_hmac('sha256', "$headerBase64.$payloadBase64", $this->secret, true);
        if (hash_equals($signature, $expectedSignature)) {
            return $payload;
        } else {
            throw new \Exception('Assinatura inválida');
        }
    }

    public function isValidToken($token)
    {
        try {
            $this->decodeToken($token);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}