<?php

function gerarToken($usuarioId = null, $secretKey = null, $expiraEm = 3600)
{
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
    $payload = json_encode([
        'iat' => time(),
        'exp' => time() + $expiraEm,
        'usuarioId' => $usuarioId
    ]);
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secretKey, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    $token = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    return $token;
}


function enviarEmail($emailDestinatario, $assuntoEmail, $corpoEmail) {
    $cabecalhos = "MIME-Version: 1.0" . "\r\n";
    $cabecalhos .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $cabecalhos .= "From: seu_email@exemplo.com" . "\r\n";
    if (mail($emailDestinatario, $assuntoEmail, $corpoEmail, $cabecalhos)) {
        return true;
    } else {
        return false;
    }
}