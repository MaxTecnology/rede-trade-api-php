<?php

namespace Core;

class Env
{
    public static function get($key, $defaultValue = null)
    {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);
            $lines = explode("\n", $envContent);
            foreach ($lines as $line) {
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $envKey = trim($parts[0]);
                    $envValue = trim($parts[1], " \t\n\r\0\x0B\"");
                    if ($envKey === $key) {
                        return $envValue;
                    }
                }
            }
        }
        return $defaultValue;
    }

    public static function load() {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);
            $lines = explode("\n", $envContent);
            foreach ($lines as $line) {
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $envKey = trim($parts[0]);
                    $envValue = trim($parts[1], " \t\n\r\0\x0B\"");
                    $_ENV[$envKey] = $envValue;
                }
            }
        }
    }
}
