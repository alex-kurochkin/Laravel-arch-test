<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class ModuleTestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected array $modulesToLoad = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadModules();
        $this->runModuleMigrations();
    }

    protected function loadModules(): void
    {
        foreach ($this->modulesToLoad as $module) {
            $provider = "Modules\\{$module}\\Providers\\{$module}ServiceProvider";

            if (class_exists($provider) && ! $this->app->providerIsLoaded($provider)) {
                $this->app->register($provider);
            }
        }
    }

    protected function runModuleMigrations(): void
    {
        foreach ($this->modulesToLoad as $module) {
            $path = module_path($module, 'Database/Migrations');

            if (is_dir($path)) {
                $this->artisan('migrate', [
                    '--path' => $path,
                    '--database' => 'ShopOneTest',
                    '--realpath' => true,
                ])->run();
            }
        }
    }

    protected function tearDown(): void
    {
        $this->artisan('migrate:rollback', ['--database' => 'ShopOneTest'])->run();

        parent::tearDown();
    }
}
