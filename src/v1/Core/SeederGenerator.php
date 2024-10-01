<?php

namespace Core;

class SeederGenerator
{
    public static function generate($seederName)
    {
        $fileName = __DIR__ . "/../App/Banco/Seed/{$seederName}Seeder.php";
        $content = "<?php\n\n";
        $content .= "namespace App\Banco\Seed;\n\n";
        $content .= "use Core\Seeder;\n\n";
        $content .= "class $seederName" . "Seeder extends Seeder\n";
        $content .= "{\n";
        $content .= "    public function run()\n";
        $content .= "    {\n";
        $content .= "        // Escreva suas inserções de dados aqui\n";
        $content .= "    }\n";
        $content .= "}\n";
        file_put_contents($fileName, $content);
        echo "Seeder gerada: $fileName\n";
    }
}
