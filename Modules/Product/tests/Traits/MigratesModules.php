<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait MigratesModules
{
    /**
     * Определяем порядок миграций модулей
     */
    protected array $moduleMigrationOrder = [
        'Catalogue', // Должен быть первым (создает categories)
        'Product',   // Вторым (использует categories)
    ];

    protected function migrateModulesInOrder(): void
    {
        // Отключаем проверку внешних ключей
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Очищаем таблицы если нужно
        if (property_exists($this, 'dropTablesBeforeMigration') && $this->dropTablesBeforeMigration) {
            $this->dropAllTables();
        }

        // Выполняем миграции в правильном порядке
        foreach ($this->moduleMigrationOrder as $module) {
            $this->runModuleMigrations($module);
        }

        // Включаем проверку внешних ключей
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    protected function runModuleMigrations(string $module): void
    {
        $migrationPath = module_path($module, 'database/migrations');

        if (!is_dir($migrationPath)) {
            return;
        }

        $this->artisan('migrate', [
            '--path' => $migrationPath,
            '--realpath' => true,
            '--force' => true,
            '--no-interaction' => true,
        ]);
    }

    protected function dropAllTables(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = DB::select('SHOW TABLES');
        $dbName = 'Tables_in_' . config('database.connections.mysql.database');

        foreach ($tables as $table) {
            $tableName = $table->$dbName;
            Schema::dropIfExists($tableName);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    protected function refreshTestDatabase(): void
    {
        $this->migrateModulesInOrder();
    }
}
