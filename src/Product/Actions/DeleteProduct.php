<?php

namespace Larasell\Admin\Product\Actions;

use Illuminate\Validation\ValidationException;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Catalog\Actions\DeleteProduct as CoreDeleteProduct;
use Larasell\Larasell\Catalog\Exceptions\ProductInUse;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Catalog\ProductId;

class DeleteProduct
{
    public function __construct(private readonly CoreDeleteProduct $delete) {}

    /**
     * Deletes the product with its variants through the core's action, which also retires its
     * URLs. Past orders keep their own copy of the product. The core refuses while an open order
     * or reserved stock still needs it; the refusal shows in the delete dialog.
     *
     * @throws ValidationException
     */
    public function handle(Product $product, Origin $origin): void
    {
        try {
            $this->delete->handle(ProductId::of($product), $origin);
        } catch (ProductInUse) {
            throw ValidationException::withMessages([
                'confirmation' => __('Open orders or reserved stock still need this product, so it can\'t be deleted yet.'),
            ]);
        }
    }
}
