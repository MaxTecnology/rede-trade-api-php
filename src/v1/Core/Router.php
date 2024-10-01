<?php

namespace Core;

use Core\Request;
use Core\Response;

class Router
{
    private $method;
    private $uri;
    private $routers = [];
    private $params = [];

    function __construct()
    {
        $this->method =  $_SERVER['REQUEST_METHOD'];
        $this->uri    =  $_SERVER['REQUEST_URI'];
    }

    function compareRoutes($route, $requestUri = null)
    {
        if ($requestUri == null) $requestUri = explode('?', $this->uri)[0];
        $route = array_values(array_filter(explode('/', $route)));
        $requestUri = array_values(array_filter(explode('/', $requestUri)));
        if (count($route) !== count($requestUri)) {
            return false;
        }
        for ($i = 0; $i < count($route); $i++) {
            if (stripos($route[$i], '{') !== false) {
                $index =  str_replace(['{', '}'], '', $route[$i]);
                $this->params[$index] = $requestUri[$i];
                $route[$i] = $requestUri[$i];
            }
        }
        return implode('/', $route) == implode('/', $requestUri);
    }

    public function request($method, $route, $fn, $middlewares = [])
    {
        $this->routers[$method][] = [
            "route"       => $route,
            "fn"          => $fn,
            "middlewares" => $middlewares,
        ];
    }

    public function get($route, $fn, $middlewares = [])
    {
        $this->request('GET', $route, $fn, $middlewares);
    }

    public function post($route, $fn, $middlewares = [])
    {
        $this->request('POST', $route, $fn, $middlewares);
    }

    public function put($route, $fn, $middlewares = [])
    {
        $this->request('PUT', $route, $fn, $middlewares);
    }

    public function delete($route, $fn, $middlewares = [])
    {
        $this->request('DELETE', $route, $fn, $middlewares);
    }


    public function build()
    {
        foreach ($this->routers  as $method => $routes) {
            if ($this->method == $method) {
                foreach ($routes as $router) {
                    extract($router);
                    if ($this->compareRoutes($route)) {                       
                        foreach ($middlewares as $midd) {
                            $exec = call_user_func($midd, $this->params);
                            if ($exec['next']) {
                                echo json_encode($exec);
                                die;
                            }
                        }
                        $req = new Request($this->params);
                        $res = new Response();
                        call_user_func($fn, $req, $res);
                        die;
                    }
                }
            }
        }
    }
}
