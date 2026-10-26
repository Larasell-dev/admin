<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Larasell\Admin\Data\ViewModel\ProductImageData;
use Larasell\Admin\Http\Requests\ProductImagesRequest;
use Larasell\Larasell\Models\ProductImage;

/**
 * Stores uploaded images without linking them to a product. The product form submits their
 * ids, and saving the product links them in that order.
 */
class ProductImageController
{
    public function store(ProductImagesRequest $request): JsonResponse
    {
        /** @var array<int, UploadedFile> $files */
        $files = $request->file('images');

        $images = array_map(fn (UploadedFile $file) => ProductImage::create(['file' => $file]), array_values($files));

        return new JsonResponse(['images' => array_map(ProductImageData::fromModel(...), $images)], 201);
    }
}
