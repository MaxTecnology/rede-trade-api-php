<?php

namespace Core;

class App
{

    public function loadFiles($folderPath)
    {
        if (!is_dir($folderPath)) {
            throw new \InvalidArgumentException("O diretório '$folderPath' não existe.");
        }
        $files = scandir($folderPath);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                include_once $folderPath . '/' . $file;
            }
        }
    }

    public function build()
    {
        $this->loadFiles(__DIR__ . "/../App/Http/");
    }
}
