<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(config_path('larasell'));
});

it('publishes the config files to config/larasell/admin', function () {
    $this->artisan('vendor:publish', ['--tag' => 'larasell-admin-config'])->assertSuccessful();

    foreach (['panel.php', 'products.php', 'customers.php', 'orders.php', 'api_tokens.php', 'audit.php'] as $file) {
        expect(config_path("larasell/admin/{$file}"))->toBeFile()
            ->and(File::get(config_path("larasell/admin/{$file}")))
            ->toBe(File::get(__DIR__."/../../config/larasell/admin/{$file}"));
    }
});
