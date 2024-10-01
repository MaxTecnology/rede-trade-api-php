<?php

namespace Core;

class Server
{
  static function ip()
  {
    $ipAddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
      $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
      $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
      $ipAddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
      $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
      $ipAddress = $_SERVER['HTTP_FORWARDED'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
      $ipAddress = $_SERVER['REMOTE_ADDR'];
    }
    return $ipAddress;
  }
  static function agent()
  {
    return $_SERVER['HTTP_USER_AGENT'];
  }
}
