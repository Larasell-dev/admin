<?php

namespace Larasell\Admin\Actions\Media;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Models\Media\ProductImageThumbnail;
use Larasell\Larasell\Models\ProductImage;

class DeleteProductImage
{
    /**
     * Deletes the product image, its file and its thumbnail, and removes it from its folder and every product that shows it.
     */
    public function handle(ProductImage $image): void
    {
        $thumbnail = ProductImageThumbnail::query()->whereKey($image->getKey())->first();

        DB::transaction(function () use ($image, $thumbnail): void {
            // Detached explicitly, since the pivots' cascades needs foreign keys, which SQLite may not enforce.
            $image->products()->detach();
            DB::table('larasell_admin_media_folder_product_image')->where('product_image_id', $image->getKey())->delete();
            $thumbnail?->delete();
            $image->delete();
        });

        // After the record is gone: a failed file deletion leaves an orphaned file, never an image without one.
        Storage::disk(config('larasell.images.disk'))->delete(array_filter([$image->file, $thumbnail?->path]));
    }
}
