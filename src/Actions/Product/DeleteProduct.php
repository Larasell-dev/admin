<?php

namespace Larasell\Admin\Actions\Product;

use Illuminate\Support\Facades\DB;
use Larasell\Larasell\Models\Product;

class DeleteProduct
{
    /**
     * Deletes the product, its variants and its category links. Past orders keep their own copy
     * of the product. Categories and variants are removed here explicitly rather than left to
     * the database's cascading foreign keys.
     */
    public function handle(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->categories()->detach();
            $product->variants()->delete();
            $product->delete();
        });
    }
}
