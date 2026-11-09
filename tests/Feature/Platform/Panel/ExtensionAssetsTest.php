<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Platform\Panel\Exceptions\InvalidExtension;

beforeEach(function () {
    $this->panelHot = dirname(__DIR__, 4).'/public/hot';
    $this->extensionPublic = sys_get_temp_dir().'/'.uniqid('acme-public-');
    mkdir($this->extensionPublic);
});

afterEach(function () {
    @unlink($this->panelHot);
    File::deleteDirectory($this->extensionPublic);
    File::delete(public_path('vendor/acme-wishlists'));
});

it('loads extension assets after the panel\'s script and before the panel boots', function () {
    // Arrange
    file_put_contents($this->panelHot, 'http://localhost:5173');
    file_put_contents("{$this->extensionPublic}/hot", 'http://localhost:5199');
    $public = $this->extensionPublic;
    $this->bootingWith(fn () => AdminPanel::resolve()->vite('acme-wishlists', $public, [
        'resources/js/admin.ts',
        'resources/css/admin.css',
    ]));
    $this->withVite();

    // Act
    $html = $this->get(route('larasell.admin.login'))->assertOk()->getContent();

    // Assert
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
    // Arrange
    file_put_contents($this->panelHot, 'http://localhost:5173');
    file_put_contents("{$this->extensionPublic}/hot", 'http://localhost:5199');
    $public = $this->extensionPublic;
    $this->bootingWith(fn () => AdminPanel::resolve()->vite('acme-wishlists', $public, ['resources/js/admin.ts']));
    $this->withVite();

    // Act
    $html = $this->get(route('larasell.admin.login'))->assertOk()->getContent();

    // Assert
    expect($html)->toContain('import RefreshRuntime from \'http://localhost:5199/@react-refresh\'');
});

it('links extension builds into the app\'s public directory', function () {
    // Arrange
    mkdir("{$this->extensionPublic}/vendor/acme-wishlists", recursive: true);
    file_put_contents("{$this->extensionPublic}/vendor/acme-wishlists/manifest.json", '{}');
    AdminPanel::resolve()->vite('acme-wishlists', $this->extensionPublic, ['resources/js/admin.ts']);

    // Act
    $this->artisan('larasell:link')
        ->expectsOutputToContain('Assets of [acme-wishlists] linked.')
        ->assertSuccessful();

    // Assert
    expect(public_path('vendor/acme-wishlists/manifest.json'))->toBeReadableFile();
});

it('fails when an extension is not built', function () {
    // Arrange
    AdminPanel::resolve()->vite('acme-wishlists', $this->extensionPublic, ['resources/js/admin.ts']);

    // Act & Assert
    $this->artisan('larasell:link')
        ->expectsOutputToContain('Assets of [acme-wishlists] are not built.')
        ->assertFailed();
});

it('rejects an extension name that is not a plain slug', function (string $name) {
    // Act & Assert
    expect(fn () => AdminPanel::resolve()->vite($name, $this->extensionPublic, ['resources/js/admin.ts']))
        ->toThrow(InvalidExtension::class);
})->with(['../../storage', 'acme/wishlists', '.', '..', '']);

it('refuses to link an extension outside public/vendor without deleting anything', function () {
    // Arrange
    $outside = public_path('acme-escape');
    File::ensureDirectoryExists($outside);
    file_put_contents("{$outside}/keep.txt", 'keep');
    mkdir("{$this->extensionPublic}/vendor");
    mkdir("{$this->extensionPublic}/acme-escape");
    // Registered past vite()'s check, as production does when that check only reports.
    (fn (string $publicPath) => $this->extensions['../acme-escape'] = ['publicPath' => $publicPath, 'entries' => []])
        ->call(AdminPanel::resolve(), $this->extensionPublic);

    // Act
    $run = fn () => $this->artisan('larasell:link')->run();

    // Assert
    try {
        expect($run)->toThrow(InvalidExtension::class);
        expect("{$outside}/keep.txt")->toBeReadableFile();
    } finally {
        is_link($outside) ? File::delete($outside) : File::deleteDirectory($outside);
    }
});
