<?php

declare(strict_types=1);

/*
 * Smoke tests for a fresh Laravel application with larasell-dev/admin installed, served by
 * `php artisan serve`. smoke-tests/run.sh copies this directory into the application and passes
 * the server URL and the admin it created through SMOKE_* environment variables.
 *
 * The login route allows five attempts per minute, so keep the number of sign-ins below that.
 */

use Tests\TestCase;

// The browser plugin needs a booted Laravel application, even though every request goes to the
// separate server process.
pest()->extend(TestCase::class);

function smokeEnv(string $name): string
{
    $value = getenv($name);

    if ($value === false || $value === '') {
        throw new RuntimeException("$name is not set. Run the smoke tests through smoke-tests/run.sh.");
    }

    return $value;
}

function adminPath(string $path = ''): string
{
    return rtrim('/'.trim(smokeEnv('SMOKE_ADMIN_PATH'), '/').'/'.ltrim($path, '/'), '/');
}

function adminUrl(string $path = ''): string
{
    return rtrim(smokeEnv('SMOKE_BASE_URL'), '/').adminPath($path);
}

function signIn(): mixed
{
    return visit(adminUrl('login'))
        ->type('email', smokeEnv('SMOKE_ADMIN_EMAIL'))
        ->type('password', smokeEnv('SMOKE_ADMIN_PASSWORD'))
        ->click('button[type="submit"]')
        ->assertPathIs(adminPath());
}

/**
 * Responses with a 4xx or 5xx status for any script, stylesheet, font, or image the page loaded.
 */
function failedResources(mixed $page): array
{
    return $page->script(<<<'JS'
        performance.getEntriesByType('resource')
            .filter((entry) => entry.responseStatus >= 400)
            .map((entry) => `${entry.responseStatus} ${entry.name}`)
        JS);
}

it('sends guests to the login page', function () {
    $page = visit(adminUrl())
        ->assertPathIs(adminPath('login'))
        ->assertTitle('Sign in - Admin')
        ->assertSee('Enter your credentials to access the admin panel.')
        ->assertVisible('button[type="submit"]')
        ->assertNoSmoke();

    expect(failedResources($page))->toBe([]);

    visit(adminUrl('customers'))->assertPathIs(adminPath('login'));
});

it('signs an admin in and renders the panel', function () {
    $page = signIn()
        ->assertTitle('Dashboard - Admin')
        ->assertSee('Dashboard')
        ->assertSee(smokeEnv('SMOKE_ADMIN_EMAIL'))
        ->assertVisible('#main-content')
        ->assertVisible('aside nav')
        ->assertSeeIn('aside nav', 'Categories')
        ->assertSeeIn('aside nav', 'Customers')
        // app.tsx defines window.LarasellAdmin; it is missing when the panel's bundle failed to load.
        ->assertScript("typeof window.LarasellAdmin === 'object'")
        ->assertNoSmoke();

    expect(failedResources($page))->toBe([]);

    // The panel's own JavaScript and CSS, which larasell:link links into public/vendor/larasell-admin.
    $adminAssetTypes = $page->script(<<<'JS'
        performance.getEntriesByType('resource')
            .map((entry) => new URL(entry.name).pathname)
            .filter((path) => path.startsWith('/vendor/larasell-admin/'))
            .map((path) => path.split('.').pop())
        JS);

    expect($adminAssetTypes)->toContain('js', 'css');
});

it('navigates between the main pages', function () {
    $page = signIn()
        ->click('aside nav a[href$="/categories"]')
        ->assertPathIs(adminPath('categories'))
        ->assertTitle('Categories - Admin')
        ->assertSee('No categories yet')
        ->click('aside nav a[href$="/customers"]')
        ->assertPathIs(adminPath('customers'))
        ->assertTitle('Customers - Admin')
        ->assertSee('No customers yet')
        ->click(smokeEnv('SMOKE_ADMIN_NAME'))
        ->click('Profile')
        ->assertPathIs(adminPath('profile'))
        ->assertTitle('Profile - Admin')
        ->click('aside a[aria-label="Home"]')
        ->assertPathIs(adminPath())
        ->assertTitle('Dashboard - Admin')
        ->assertNoSmoke();

    expect(failedResources($page))->toBe([]);
});
