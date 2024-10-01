<?php

namespace Core;

class MigrationRunner
{
    public static function runAll()
    {
        $migrationFiles = glob(__DIR__ . '/App/Banco/Migrations/*.php');
        $executedMigrations = [];
        foreach ($migrationFiles as $file) {
            if (preg_match('/^(\d+)_([A-Za-z0-9_]+)\.php$/', basename($file), $matches)) {
                require_once $file;
                $migrationClassName = $matches[2];
                if (class_exists($migrationClassName)) {
                    $migration = new $migrationClassName;
                    if (method_exists($migration, 'up')) {
                        $migration->up();
                        $executedMigrations[] = $matches[1];
                        echo "Migration executada: $migrationClassName\n";
                    }
                }
            }
        }
        echo "Migrações concluídas: " . implode(', ', $executedMigrations) . "\n";
    }
}