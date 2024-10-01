<?php

namespace App\help;

class Keygen
{
  private $time;
  private $secret;
  private $salt;

  public function __construct(int $time, $secret = 'cCwrxA8m388')
  {
    $this->time = $time;
    $this->secret = $secret;
  }

  function setSalt(string $salt)
  {
    $this->salt = $salt;
  }

  public function generate(): string
  {
    $payload = time() + $this->time * 60;
    $data = base64_encode(json_encode([
      "payload" => $payload,
      "hash" => hash_hmac("sha256", $payload, $this->secret . $this->salt)
    ]));
    return rtrim(strtr($data, '+/', '-_'), '=');
  }

  public function validate(string $key): bool
  {
    $data = base64_decode(str_pad($key, strlen($key) % 4, '=', STR_PAD_RIGHT));
    $decoded = json_decode($data, true);
    if (!$decoded || !isset($decoded['payload'], $decoded['hash'])) {
      return false;
    }

    $expectedHash = hash_hmac("sha256", $decoded['payload'], $this->secret . $this->salt);
    return $decoded['hash'] === $expectedHash && $decoded['payload'] > time();
  }
}
