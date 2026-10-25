<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests;

use Closure;
use Illuminate\Foundation\Application;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Larasell\Admin\AdminServiceProvider;
use Larasell\Larasell\LarasellServiceProvider;
use Laravel\Head\HeadServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * The environment the application boots in. Routes that only exist in some
     * environments are registered during boot, so tests switch it with inEnvironment().
     */
    protected string $environment = 'testing';

    /**
     * Runs while the application boots, before the providers do; set it with bootingWith().
     */
    protected ?Closure $booting = null;

    protected function inEnvironment(string $environment): static
    {
        $this->environment = $environment;

        return $this->reboot();
    }

    /**
     * Reboots the application with $callback run before the providers boot, e.g. to register
     * navigation items as a plugin's service provider would.
     */
    protected function bootingWith(Closure $callback): static
    {
        $this->booting = $callback;

        return $this->reboot();
    }

    protected function reboot(): static
    {
        $this->refreshApplication();
        $this->withoutVite();
        $this->artisan('migrate', ['--database' => 'testing', '--force' => true])->run();

        return $this;
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        if ($this->booting !== null) {
            $app->booting($this->booting);
        }
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
            HeadServiceProvider::class,
            AdminServiceProvider::class,
            InertiaServiceProvider::class,
            LarasellServiceProvider::class,
            LaravelDataServiceProvider::class,
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
                // Like production databases, so deletes cascade as the migrations define.
                'foreign_key_constraints' => true,
            ],
        ]);
    }
}
