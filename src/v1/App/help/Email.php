<?php

namespace App\help;

class Email
{
    static function isValid(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}
