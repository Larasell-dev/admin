<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(config_path('larasell'));
});

it('publishes the config files to config/larasell/admin', function () {
    $this->artisan('vendor:publish', ['--tag' => 'larasell-admin-config'])->assertSuccessful();

    expect(config_path('larasell/admin/panel.php'))->toBeFile()
        ->and(File::get(config_path('larasell/admin/panel.php')))
        ->toBe(File::get(__DIR__.'/../../config/larasell/admin/panel.php'));
});
