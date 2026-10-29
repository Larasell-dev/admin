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

it('registers extension assets by name', function () {
    $panel = new AdminPanel;

    $panel->vite('acme-wishlists', '/path/to/acme/public/', ['resources/js/admin.ts', 'resources/css/admin.css']);

    expect($panel->extensions())->toBe([
        'acme-wishlists' => ['publicPath' => '/path/to/acme/public', 'entries' => ['resources/js/admin.ts', 'resources/css/admin.css']],
    ]);
});

it('serves extension assets from the extension\'s own dev server', function () {
    $this->withVite();
    $public = sys_get_temp_dir().'/'.uniqid('acme-public-');
    mkdir($public);
    file_put_contents("{$public}/hot", 'http://localhost:5199');

    $panel = new AdminPanel;
    $panel->vite('acme-wishlists', $public, ['resources/js/admin.ts']);

    expect((string) $panel->extensionVite('acme-wishlists')('resources/js/admin.ts'))
        ->toContain('src="http://localhost:5199/resources/js/admin.ts"');

    unlink("{$public}/hot");
    rmdir($public);
});

it('serves extension assets from their build linked into vendor/{name}', function () {
    $this->withVite();
    $build = public_path('vendor/acme-wishlists');
    mkdir($build, recursive: true);
    file_put_contents("{$build}/manifest.json", json_encode([
        'resources/js/admin.ts' => ['file' => 'assets/admin-abc123.js', 'src' => 'resources/js/admin.ts', 'isEntry' => true],
        'resources/css/admin.css' => ['file' => 'assets/admin-def456.css', 'src' => 'resources/css/admin.css', 'isEntry' => true],
    ]));

    $panel = new AdminPanel;
    $panel->vite('acme-wishlists', '/path/to/acme/public', ['resources/js/admin.ts', 'resources/css/admin.css']);

    expect((string) $panel->extensionVite('acme-wishlists')(['resources/js/admin.ts', 'resources/css/admin.css']))
        ->toContain('/vendor/acme-wishlists/assets/admin-abc123.js')
        ->toContain('/vendor/acme-wishlists/assets/admin-def456.css');

    unlink("{$build}/manifest.json");
    rmdir($build);
});

it('refuses to serve assets of an extension that is not registered', function () {
    (new AdminPanel)->extensionVite('acme-wishlists');
})->throws(InvalidArgumentException::class, 'acme-wishlists');
