<?php

namespace App\help;

use Core\Request;
use Core\Response;

class FactorRouter
{
    static function add(array $data)
    {
        return function (Request $req, Response $res) use ($data) {
            $params = [];
            foreach ($data["params"] as $p) {
                [$param_name, $param_message] = $p;
                $params[$param_name] = $req->get($param_name, $param_message);
            }
            [$msg_fail, $msg_success] = $data["message"];
            $case = new $data["case"]($params);
            $status = 400;
            $message = $msg_fail;
            $next = false;
            $payload = [];
            $valid = true;
            foreach ($data["validations"] as $v) {
                @[$fnc_name, $message_error] = $v;
                if (!$case->{$fnc_name}()) {
                    $message = $message_error;
                    $valid = false;
                }
            }            
            if ($valid) {
                $status = 201;
                $message = $msg_success;
                $next = true;
                $payload = $case->{$data["run"]}();
            }
            $res->status($status);
            $res->body([
                "next" =>  $next,
                "message" => $message,
                "payload" => $payload
            ]);
        };
    }
}