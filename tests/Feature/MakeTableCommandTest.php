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
    $this->artisan('make:larasell-table', ['name' => 'Books', '--model' => 'Larasell\Admin\Tests\Fixtures\Book'])->assertSuccessful();

    expect(File::get(app_path('Tables/Books.php')))
        ->toContain('use Larasell\Admin\Tests\Fixtures\Book;')
        ->toContain('protected ?string $model = Book::class;');
});
