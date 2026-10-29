<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Larasell\Admin\AdminPanel;

beforeEach(function () {
    $this->panelHot = dirname(__DIR__, 2).'/public/hot';
    $this->extensionPublic = sys_get_temp_dir().'/'.uniqid('acme-public-');
    mkdir($this->extensionPublic);
});

afterEach(function () {
    @unlink($this->panelHot);
    File::deleteDirectory($this->extensionPublic);
    File::delete(public_path('vendor/acme-wishlists'));
});

it('loads extension assets after the panel\'s script and before the panel boots', function () {
    file_put_contents($this->panelHot, 'http://localhost:5173');
    file_put_contents("{$this->extensionPublic}/hot", 'http://localhost:5199');
    $public = $this->extensionPublic;
    $this->bootingWith(fn () => AdminPanel::resolve()->vite('acme-wishlists', $public, [
        'resources/js/admin.ts',
        'resources/css/admin.css',
    ]));
    $this->withVite();

    $html = $this->get(route('larasell.admin.login'))->assertOk()->getContent();

    $panel = strpos($html, 'src="http://localhost:5173/resources/js/app.tsx"');
    $script = strpos($html, 'src="http://localhost:5199/resources/js/admin.ts"');
    $style = strpos($html, 'href="http://localhost:5199/resources/css/admin.css"');
    $boot = strpos($html, 'window.LarasellAdmin.boot()');

    expect($panel)->toBeInt()
        ->and($script)->toBeInt()->toBeGreaterThan($panel)
        ->and($style)->toBeInt()->toBeGreaterThan($panel)
        ->and($boot)->toBeGreaterThan($script);
});

it('sets up React Refresh for an extension\'s dev server', function () {
    file_put_contents($this->panelHot, 'http://localhost:5173');
    file_put_contents("{$this->extensionPublic}/hot", 'http://localhost:5199');
    $public = $this->extensionPublic;
    $this->bootingWith(fn () => AdminPanel::resolve()->vite('acme-wishlists', $public, ['resources/js/admin.ts']));
    $this->withVite();

    $html = $this->get(route('larasell.admin.login'))->assertOk()->getContent();

    expect($html)->toContain('import RefreshRuntime from \'http://localhost:5199/@react-refresh\'');
});

it('links extension builds into the app\'s public directory', function () {
    mkdir("{$this->extensionPublic}/vendor/acme-wishlists", recursive: true);
    file_put_contents("{$this->extensionPublic}/vendor/acme-wishlists/manifest.json", '{}');
    AdminPanel::resolve()->vite('acme-wishlists', $this->extensionPublic, ['resources/js/admin.ts']);

    $this->artisan('larasell:link')
        ->expectsOutputToContain('Assets of [acme-wishlists] linked.')
        ->assertSuccessful();

    expect(public_path('vendor/acme-wishlists/manifest.json'))->toBeReadableFile();
});

it('fails when an extension is not built', function () {
    AdminPanel::resolve()->vite('acme-wishlists', $this->extensionPublic, ['resources/js/admin.ts']);

    $this->artisan('larasell:link')
        ->expectsOutputToContain('Assets of [acme-wishlists] are not built.')
        ->assertFailed();
});
