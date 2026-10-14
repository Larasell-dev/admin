<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests;

use Illuminate\Foundation\Application;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Larasell\Admin\AdminServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->artisan('migrate', ['--database' => 'testing'])->run();
    }

    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app)
    {
        return [
            AdminServiceProvider::class,
            InertiaServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app)
    {
        config([
            'database.default' => 'testing',
            'database.connections.testing' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);
    }
}
