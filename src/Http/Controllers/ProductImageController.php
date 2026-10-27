<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Larasell\Admin\Data\ViewModel\ProductImageData;
use Larasell\Admin\Http\Requests\ProductImagesRequest;
use Larasell\Larasell\Models\ProductImage;

/**
 * Stores an uploaded image without linking it to a product. The product form submits the ids of
 * its images, and saving the product links them in that order.
 */
class ProductImageController
{
    public function store(ProductImagesRequest $request): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('image');

        return new JsonResponse(['image' => ProductImageData::fromModel(ProductImage::create(['file' => $file]))], 201);
    }
}
