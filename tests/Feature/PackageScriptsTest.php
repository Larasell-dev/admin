<?php

declare(strict_types=1);

use Larasell\Admin\AdminPanel;

beforeEach(function () {
    $this->panelHot = dirname(__DIR__, 2).'/public/hot';
    $this->packagePublic = sys_get_temp_dir().'/'.uniqid('acme-public-');
    mkdir($this->packagePublic);
});

afterEach(function () {
    @unlink($this->panelHot);
    @unlink("{$this->packagePublic}/hot");
    @rmdir($this->packagePublic);
});

it('loads package scripts after the panel\'s script and before the panel boots', function () {
    file_put_contents($this->panelHot, 'http://localhost:5173');
    file_put_contents("{$this->packagePublic}/hot", 'http://localhost:5199');
    $public = $this->packagePublic;
    $this->bootingWith(fn () => AdminPanel::resolve()->packageScript('acme-reviews', $public, 'resources/js/admin/app.tsx'));
    $this->withVite();

    $html = $this->get(route('larasell.admin.login'))->assertOk()->getContent();

    $panel = strpos($html, 'src="http://localhost:5173/resources/js/app.tsx"');
    $package = strpos($html, 'src="http://localhost:5199/resources/js/admin/app.tsx"');
    $boot = strpos($html, 'window.LarasellAdmin.boot()');

    expect($panel)->toBeInt()
        ->and($package)->toBeInt()->toBeGreaterThan($panel)
        ->and($boot)->toBeGreaterThan($package);
});
