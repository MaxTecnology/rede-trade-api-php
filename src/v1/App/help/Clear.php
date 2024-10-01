<?php

namespace App\help;

class Clear {
    static function number($n) {
        return $cpf = preg_replace('/\D/is', '', $n);
    }
}