<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(app_path('Tables'));
});

it('scaffolds a table that infers its model', function () {
    $this->artisan('make:larasell-table', ['name' => 'ProductReviews'])->assertSuccessful();

    $contents = File::get(app_path('Tables/ProductReviews.php'));

    expect($contents)
        ->toContain('namespace App\Tables;')
        ->toContain('class ProductReviews extends Table')
        ->toContain('public function columns(): array')
        ->not->toContain('UseModel')
        ->not->toContain('use App\Models');
});

it('uses the given model', function () {
    $this->artisan('make:larasell-table', ['name' => 'Books', '--model' => 'Larasell\Admin\Tests\Fixtures\Book'])->assertSuccessful();

    expect(File::get(app_path('Tables/Books.php')))
        ->toContain('use Larasell\Admin\Tests\Fixtures\Book;')
        ->toContain('use Larasell\Admin\Tables\Attributes\UseModel;')
        ->toContain('#[UseModel(Book::class)]');
});

it('leaves out a given model the table would infer anyway', function () {
    $this->artisan('make:larasell-table', ['name' => 'Shop/ProductReviews', '--model' => 'Shop/ProductReview'])->assertSuccessful();

    expect(File::get(app_path('Tables/Shop/ProductReviews.php')))
        ->toContain('namespace App\Tables\Shop;')
        ->not->toContain('UseModel');
});

it('names a given model the table would not infer', function () {
    $this->artisan('make:larasell-table', ['name' => 'Shop/ProductReviews', '--model' => 'ProductReview'])->assertSuccessful();

    expect(File::get(app_path('Tables/Shop/ProductReviews.php')))
        ->toContain('use App\Models\ProductReview;')
        ->toContain('#[UseModel(ProductReview::class)]');
});
