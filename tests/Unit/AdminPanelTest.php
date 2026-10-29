<?php

declare(strict_types=1);

use Larasell\Admin\AdminPanel;

it('resolves the singleton instance from the container', function () {
    expect(AdminPanel::resolve())->toBe(app(AdminPanel::class))
        ->toBeInstanceOf(AdminPanel::class);
});

it('registers scripts and page paths', function () {
    $panel = new AdminPanel;

    $panel->script('resources/js/admin/app.tsx');
    $panel->pagePath('/path/to/pages');

    expect($panel->scripts())->toBe(['resources/js/admin/app.tsx'])
        ->and($panel->pagePaths())->toBe(['/path/to/pages']);
});

it('registers package scripts by name', function () {
    $panel = new AdminPanel;

    $panel->packageScript('acme-reviews', '/path/to/acme/public/', 'resources/js/admin/app.tsx');

    expect($panel->packageScripts())->toBe([
        'acme-reviews' => ['publicPath' => '/path/to/acme/public', 'entry' => 'resources/js/admin/app.tsx'],
    ]);
});

it('serves a package script from the package\'s own dev server', function () {
    $this->withVite();
    $public = sys_get_temp_dir().'/'.uniqid('acme-public-');
    mkdir($public);
    file_put_contents("{$public}/hot", 'http://localhost:5199');

    $panel = new AdminPanel;
    $panel->packageScript('acme-reviews', $public, 'resources/js/admin/app.tsx');

    expect((string) $panel->packageVite('acme-reviews')('resources/js/admin/app.tsx'))
        ->toContain('src="http://localhost:5199/resources/js/admin/app.tsx"');

    unlink("{$public}/hot");
    rmdir($public);
});

it('serves a package script from its build linked into vendor/{name}', function () {
    $this->withVite();
    $build = public_path('vendor/acme-reviews');
    mkdir($build, recursive: true);
    file_put_contents("{$build}/manifest.json", json_encode([
        'resources/js/admin/app.tsx' => ['file' => 'assets/app-abc123.js', 'src' => 'resources/js/admin/app.tsx', 'isEntry' => true],
    ]));

    $panel = new AdminPanel;
    $panel->packageScript('acme-reviews', '/path/to/acme/public', 'resources/js/admin/app.tsx');

    expect((string) $panel->packageVite('acme-reviews')('resources/js/admin/app.tsx'))
        ->toContain('/vendor/acme-reviews/assets/app-abc123.js');

    unlink("{$build}/manifest.json");
    rmdir($build);
});

it('refuses to serve a package script that is not registered', function () {
    (new AdminPanel)->packageVite('acme-reviews');
})->throws(InvalidArgumentException::class, 'acme-reviews');
