<?php

namespace App\help;

class Cnpj
{
    static function isValid(string $cnpj): bool
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) != 14) {
            return false;
        }
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        $peso1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma1 = 0;
        for ($i = 0; $i < 12; $i++) {
            $soma1 += $cnpj[$i] * $peso1[$i];
        }
        $resto1 = $soma1 % 11;
        $digito1 = $resto1 < 2 ? 0 : 11 - $resto1;
        $peso2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma2 = 0;
        for ($i = 0; $i < 13; $i++) {
            $soma2 += $cnpj[$i] * $peso2[$i];
        }
        $resto2 = $soma2 % 11;
        $digito2 = $resto2 < 2 ? 0 : 11 - $resto2;
        if ($cnpj[12] == $digito1 && $cnpj[13] == $digito2) {
            return true;
        } else {
            return false;
        }
    }
}
