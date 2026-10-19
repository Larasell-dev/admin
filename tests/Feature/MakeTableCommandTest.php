<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(app_path('Tables'));
});

it('scaffolds a table for the singular model', function () {
    $this->artisan('make:larasell-table', ['name' => 'ProductReviews'])->assertSuccessful();

    $contents = File::get(app_path('Tables/ProductReviews.php'));

    expect($contents)
        ->toContain('namespace App\Tables;')
        ->toContain('use App\Models\ProductReview;')
        ->toContain('class ProductReviews extends Table')
        ->toContain('protected ?string $model = ProductReview::class;')
        ->toContain('public function columns(): array');
});

it('uses the given model', function () {
    $this->artisan('make:larasell-table', ['name' => 'Admins', '--model' => 'Larasell\Admin\Models\Admin'])->assertSuccessful();

    expect(File::get(app_path('Tables/Admins.php')))
        ->toContain('use Larasell\Admin\Models\Admin;')
        ->toContain('protected ?string $model = Admin::class;');
});
