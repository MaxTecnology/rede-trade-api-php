<?php

namespace Core;

class MigrationGenerator
{
    public static function generate($migrationName)
    {
        $timestamp = time();
        $fileName = __DIR__ . "/../App/Banco/Migrations/{$timestamp}_{$migrationName}.php";
        $content  = "<?php\n\n";
        $content .= "namespace App\Banco\Migrations;\n\n";
        $content .= "use Core\Migration;\n\n";
        $content .= "class $migrationName extends Migration\n";
        $content .= "{\n";
        $content .= "    public function up()\n";
        $content .= "    {\n";
        $content .= "        // Escreva suas instruções de migração 'up' aqui\n";
        $content .= "    }\n\n";
        $content .= "    public function down()\n";
        $content .= "    {\n";
        $content .= "        // Escreva suas instruções de migração 'down' aqui\n";
        $content .= "    }\n";
        $content .= "}\n";
        file_put_contents($fileName, $content);
        echo "Migration gerada: $fileName\n";
    }
}