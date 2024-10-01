<?php

namespace Core;

class Crip
{
    public static function pass($password)
    {
        $reversedPassword = strrev($password);
        $firstCharacter = substr($reversedPassword, 0, 1);
        $lastCharacter = substr($reversedPassword, -1);
        $modifiedPassword = $lastCharacter . substr($reversedPassword, 1, -1) . $firstCharacter;
        $hash = hash('sha256', $modifiedPassword);
        return $hash;
    }
}
