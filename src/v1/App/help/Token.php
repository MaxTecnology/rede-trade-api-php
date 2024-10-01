<?php

namespace App\help;

use App\Models\Config;

class Token
{
  static function validation(string $token)
  {
    $conf = new Config();
    $key = $conf->getByName("access_key");
    return $key == $token;
  }
}
