<?php

namespace Core;

class FileUploader
{
    private $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    private $uploadDir;

    public function __construct($uploadDir)
    {
        $this->uploadDir = $uploadDir;
    }

    public function uploadFiles($files)
    {
        $this->ensureUploadDirectory();

        $uploadedFiles = [];

        foreach ($files['name'] as $index => $fileName) {
            $fileType = $files['type'][$index];
            if (in_array($fileType, $this->allowedTypes)) {
                $randomName = $this->generateRandomName($fileName);
                $uploadPath = $this->uploadDir . '/' . $randomName;

                if (move_uploaded_file($files['tmp_name'][$index], $uploadPath)) {
                    $uploadedFiles[] = $randomName;
                }
            }
        }
        return $uploadedFiles;
    }

    private function generateRandomName($fileName)
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $randomName = $this->generateUUID() . '.' . $extension;
        return $randomName;
    }

    private function generateUUID()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function ensureUploadDirectory()
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
            $indexFilePath = $this->uploadDir . '/index.php';
            file_put_contents($indexFilePath, "<?php // Diretório protegido");
        }
    }
}
