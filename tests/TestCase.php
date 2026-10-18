<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests;

use Illuminate\Foundation\Application;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Larasell\Admin\AdminServiceProvider;
use Larasell\Larasell\LarasellServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * The environment the application boots in. Routes that only exist in some
     * environments are registered during boot, so tests switch it with inEnvironment().
     */
    protected string $environment = 'testing';

    protected function inEnvironment(string $environment): static
    {
        $this->environment = $environment;
        $this->refreshApplication();
        $this->withoutVite();
        $this->artisan('migrate', ['--database' => 'testing', '--force' => true])->run();

        return $this;
    }

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
            LarasellServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['env'] = $this->environment;

        config([
            'cache.default' => 'array',
            'database.default' => 'testing',
            'database.connections.testing' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);
    }
}
