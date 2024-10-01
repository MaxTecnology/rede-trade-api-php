<?php

namespace Core;

class Rand
{
    public static function number($quantity = 5)
    {
        $randomNumbers = [];
        for ($i = 0; $i < $quantity; $i++) {
            $randomNumbers[] = rand();
        }
        return $randomNumbers;
    }
}
