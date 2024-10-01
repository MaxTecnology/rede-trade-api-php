<?php

namespace Core;

class Request
{
    private $method;
    private $uri;
    private $body;
    private $headers;
    private $parans = [];

    function __construct($parans = [])
    {
        $this->parans = $parans;
        $this->body = file_get_contents('php://input');
        $this->headers = getallheaders();
        $this->getAllParameters();
    }

    function getAllParameters()
    {
        $params = array_merge($_GET, $_POST);
        $json = file_get_contents('php://input');
        $decodedJson = json_decode($json, true);

        if ($decodedJson !== null) {
            $params = array_merge($params, $decodedJson);
        }
        $this->parans = array_merge($this->parans, $params);

        return $this->parans;
    }

    function get($name, $ms = null)
    {
        $val = $this->parans[$name] ?? null;
        if ($ms != null && $val == null) {
            echo json_encode([
                "next" => false,
                "message" => $ms,
                "payload" => []
            ]);
            die;
        }
        return  $val;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getHeader($name)
    {
        $headerName = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return isset($_SERVER[$headerName]) ? $_SERVER[$headerName] : null;
    }

    public function readCookie($name)
    {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        } else {
            return null;
        }
    }
}
