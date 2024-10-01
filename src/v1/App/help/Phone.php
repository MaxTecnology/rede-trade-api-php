<?php

namespace App\help;

class Phone
{

    static function clear(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }

    static function isValid(string $phone): bool
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) == 10) {
            $valid = preg_match('/^[1-9]{2}[2-9]{1}[0-9]{3}[0-9]{4}$/', $phone);
        } elseif (strlen($phone) == 11) {
            $valid = preg_match('/^[1-9]{2}9[0-9]{4}[0-9]{4}$/', $phone);
        } else {
            return false;
        }
        if ($valid && !preg_match('/^(.)\1{9,}$/', $phone)) {
            return true;
        } else {
            return false;
        }
    }
}
