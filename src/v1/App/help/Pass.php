<?php

namespace App\help;

class Pass
{

    static function isValid($senha)
    {
        if (strlen($senha) < 8) {
            return false;
        }
        if (!preg_match('/[A-Z]/', $senha)) {
            return false;
        }
        if (!preg_match('/[a-z]/', $senha)) {
            return false;
        }
        if (!preg_match('/\d/', $senha)) {
            return false;
        }
        if (!preg_match('/[\W_]/', $senha)) {
            return false;
        }
        return true;
    }
}
