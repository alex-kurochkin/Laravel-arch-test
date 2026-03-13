<?php

declare(strict_types=1);

namespace Modules\Product\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Product\Tests\Traits\WithProductFactory;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase, WithProductFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runMigrationsInOrder();
    }

    private function runMigrationsInOrder(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->dropAllTables();

        // Миграции Catalogue
        $this->artisan('migrate', [
            '--path' => 'Modules/Catalogue/database/migrations',
            '--realpath' => true,
            '--force' => true,
        ]);

        // Миграции Product
        $this->artisan('migrate', [
            '--path' => 'Modules/Product/database/migrations',
            '--realpath' => true,
            '--force' => true,
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function dropAllTables(): void
    {
        $tables = DB::select('SHOW TABLES');
        $dbName = 'Tables_in_' . config('database.connections.mysql.database');

        foreach ($tables as $table) {
            $tableName = $table->$dbName;
            DB::statement("DROP TABLE IF EXISTS `{$tableName}`");
        }
    }
}
