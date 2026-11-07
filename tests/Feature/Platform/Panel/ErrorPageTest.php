<?php

declare(strict_types=1);

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Symfony\Component\HttpFoundation\Response;

it('shows the 404 page in the panel for unknown admin addresses and missing records', function (string $uri) {
    // Arrange
    $this->actingAs(
        Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']),
        'larasell-admin',
    );

    // Act
    $response = $this->get($uri)
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Platform/Panel/Pages/Error', shouldExist: false)
            ->where('status', 404)
            ->where('admin.email', 'ada@example.com')
            ->has('navigation'));

    // Assert
    expect($response->getContent())->toContain('>Page not found - Admin</title>');
})->with([
    'unknown address' => '/admin/does/not/exist',
    'missing admin' => '/admin/settings/team/999/edit',
]);

it('shows guests the error page without the panel', function () {
    // Act & Assert
    $this->get('/admin/does-not-exist') // @phpstan-ignore larasell.preferRouteHelper
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Platform/Panel/Pages/GuestError', shouldExist: false)
            ->where('status', 404)
            ->where('admin', null));
});

it("keeps the app's own error response callback for other addresses", function () {
    // Arrange
    $this->bootingWith(fn () => app(ExceptionHandler::class)->respondUsing(
        fn (Response $response) => $response->setContent('The app 404 page'),
    ));

    // Act & Assert
    $this->get('/does-not-exist')->assertNotFound()->assertSee('The app 404 page'); // @phpstan-ignore larasell.preferRouteHelper
});

it('keeps plain 404s for other methods, JSON requests and other addresses', function () {
    // Arrange
    $this->actingAs(
        Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']),
        'larasell-admin',
    );

    // Act & Assert
    $this->post('/admin/does-not-exist')->assertNotFound()->assertHeaderMissing('X-Inertia'); // @phpstan-ignore larasell.preferRouteHelper
    $this->getJson(route('larasell.admin.settings.team.edit', 999))->assertNotFound()->assertJsonMissingPath('component');
    expect($this->get('/does-not-exist')->assertNotFound()->getContent())->not->toContain('data-page'); // @phpstan-ignore larasell.preferRouteHelper
});

it('shows the error page for forbidden pages and, unless debugging, server errors', function (string $uri, int $status, string $title) {
    // Arrange
    config(['app.debug' => false]);
    $this->actingAs(
        Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']),
        'larasell-admin',
    );
    AdminPanel::resolve()->routes(function () {
        Route::get('forbidden', fn () => abort(403))->name('forbidden');
        Route::get('broken', fn () => throw new RuntimeException('Broken'))->name('broken');
        Route::get('maintenance', fn () => abort(503))->name('maintenance');
    });

    // Act
    $response = $this->get($uri)
        ->assertStatus($status)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Platform/Panel/Pages/Error', shouldExist: false)
            ->where('status', $status)
            ->has('navigation'));

    // Assert
    expect($response->getContent())->toContain(">{$title} - Admin</title>");
})->with([
    'forbidden' => ['/admin/forbidden', 403, 'Access denied'],
    'server error' => ['/admin/broken', 500, 'Something went wrong'],
    'unavailable' => ['/admin/maintenance', 503, 'Temporarily unavailable'],
]);

it("keeps Laravel's stack trace for server errors while debugging", function () {
    // Arrange
    config(['app.debug' => true]);
    $this->actingAs(
        Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']),
        'larasell-admin',
    );
    AdminPanel::resolve()->routes(function () {
        Route::get('broken', fn () => throw new RuntimeException('Broken'))->name('broken');
    });

    // Act
    $response = $this->get('/admin/broken'); // @phpstan-ignore larasell.preferRouteHelper

    // Assert
    $response->assertInternalServerError()->assertSee('<title>Broken (500 Internal Server Error)', false);
});
