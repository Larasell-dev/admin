<?php

namespace Larasell\Admin\Http\Controllers\Media;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\Actions\Media\DeleteProductImage;
use Larasell\Larasell\Models\ProductImage;

class ProductImageController
{
    /**
     * Deletes the product image and its file, once its name was typed to confirm, and
     * returns to the page it was deleted on.
     *
     * @throws ValidationException
     */
    public function destroy(Request $request, ProductImage $productImage, DeleteProductImage $deleteProductImage): RedirectResponse
    {
        // The name the media table shows: the file's name without its directory.
        $name = basename($productImage->file);

        if ($request->input('confirmation') !== $name) {
            throw ValidationException::withMessages([
                'confirmation' => "Type \"{$name}\" to confirm.",
            ]);
        }

        $deleteProductImage->handle($productImage);

        return back();
    }
}
