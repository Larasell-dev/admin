<?php

namespace Larasell\Admin\Console\Media;

use Illuminate\Console\Command;
use Larasell\Admin\Jobs\Media\GenerateProductImageThumbnail;
use Larasell\Admin\Models\Media\ProductImageThumbnail;
use Larasell\Larasell\Models\ProductImage;

class GenerateProductImageThumbnailsCommand extends Command
{
    protected $signature = 'larasell:media-thumbnails';

    protected $description = 'Queue thumbnails for the product images that have none yet, e.g. those uploaded before the media module made them.';

    public function handle(): int
    {
        $count = 0;

        ProductImage::query()
            ->whereNotIn('id', ProductImageThumbnail::query()->select('product_image_id'))
            ->lazyById()
            ->each(function (ProductImage $image) use (&$count): void {
                GenerateProductImageThumbnail::dispatch($image);
                $count++;
            });

        $this->components->info("Queued {$count} thumbnail(s).");

        return self::SUCCESS;
    }
}
