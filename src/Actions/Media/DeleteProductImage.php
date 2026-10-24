<?php

namespace Larasell\Admin\Actions\Media;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Larasell\Larasell\Models\ProductImage;

class DeleteProductImage
{
    /**
     * Deletes the product image and its file, and removes it from every product that shows it.
     */
    public function handle(ProductImage $image): void
    {
        DB::transaction(function () use ($image): void {
            // Detached explicitly, since the pivot's cascade needs foreign keys, which SQLite may not enforce.
            $image->products()->detach();
            $image->delete();
        });

        // After the record is gone: a failed file deletion leaves an orphaned file, never an image without one.
        Storage::disk(config('larasell.images.disk'))->delete($image->file);
    }
}
