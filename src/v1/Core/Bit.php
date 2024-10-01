<?php

namespace Core;

class Bit {
    public static function flip($position, $string) {
        if ($position >= 0 && $position < strlen($string)) {
            return substr_replace($string, '', $position, 1);
        } else {
            return $string;
        }
    }
}
