<?php

class ExtractHttp
{
  private $path;
  private $tupla = [];
  private $data = [];

  public function __construct($path)
  {
    $this->path = $path;
    $this->load();
    $this->handle();
  }

  public function load()
  {
    $this->tupla = file($this->path);
  }

  public function handle()
  {

    $ln = 0;
    $isBody = false;
    $tmp_body = "";
    foreach ($this->tupla as $line) {
      $line =  trim($line);
      $line =  str_replace(["\n", "\r"], '',  $line);
      if (substr($line, 0, 3) == '###') {
        $ln++;
      }
      if (substr($line, 0, 3) == '###') {
        $this->data[$ln]["title"] = $line;
      }
      if (substr($line, 0, 4) == 'POST') {
        $this->data[$ln]["method"] = 'POST';
        $this->data[$ln]["link"] = $line;
      }
      if (substr($line, 0, 3) == 'GET') {
        $this->data[$ln]["method"] = 'GET';
        $this->data[$ln]["link"] = $line;
      }
      if (substr($line, 0, 12) == 'Content-Type') {
        $this->data[$ln]["header"][] = $line;
      }
      if (substr($line, 0, 1) == '{') {
        $isBody = true;
        $tmp_body = "";
      }
      if ($isBody) {
        $tmp_body .= $line;
      }
      if (substr($line, 0, 1) == '}') {
        $isBody = false;
        $this->data[$ln]["body"] = json_decode($tmp_body);
        $tmp_body = "";
      }
    }
  }

  public function response()
  {
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(array_values($this->data));
  }

  public function getData()
  {
    return  array_values($this->data);
  }
}
