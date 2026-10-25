<?php

namespace Larasell\Admin\Jobs\Media;

use GdImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Models\Media\ProductImageThumbnail;
use Larasell\Larasell\Images\Raster;
use Larasell\Larasell\Models\ProductImage;

/**
 * Stores the small square preview the media table shows instead of the full image. Queued, so
 * uploads don't wait for it; on the sync connection, the default, it runs right away instead.
 */
class GenerateProductImageThumbnail implements ShouldQueue
{
    use Queueable;

    /** Enough for a thumbnail one line of text tall on screens up to 3x. */
    public const SIZE = 96;

    /** An image deleted before the job runs needs no thumbnail. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public ProductImage $image,
    ) {}

    public function handle(): void
    {
        // Null for SVGs, files GD can't read, and without GD; the table then falls back to the placeholder.
        $raster = Raster::decode($this->image);

        if ($raster === null) {
            return;
        }

        $contents = $this->encode($this->square($raster));
        imagedestroy($raster);

        if ($contents === null) {
            return;
        }

        // Named after the image's file, so generating it again overwrites the same file.
        $path = trim((string) config('larasell.images.path'), '/').'/thumbnails/'.pathinfo($this->image->file, PATHINFO_FILENAME).'.webp';

        Storage::disk(config('larasell.images.disk'))->put($path, $contents, ['visibility' => config('larasell.images.visibility')]);

        ProductImageThumbnail::query()->updateOrCreate(
            ['product_image_id' => $this->image->getKey()],
            ['path' => $path],
        );
    }

    /**
     * The centre of the image, cropped square and scaled down to SIZE, but never up.
     */
    private function square(GdImage $raster): GdImage
    {
        $side = min(imagesx($raster), imagesy($raster));
        $edge = max(1, min(self::SIZE, $side));

        $square = imagecreatetruecolor($edge, $edge);
        // Keeps transparency, e.g. of PNG logos.
        imagealphablending($square, false);
        imagesavealpha($square, true);
        imagecopyresampled(
            $square, $raster,
            0, 0, intdiv(imagesx($raster) - $side, 2), intdiv(imagesy($raster) - $side, 2),
            $edge, $edge, $side, $side,
        );

        return $square;
    }

    private function encode(GdImage $square): ?string
    {
        ob_start();
        $encoded = imagewebp($square, null, 80);
        $contents = ob_get_clean();
        imagedestroy($square);

        return $encoded && is_string($contents) && $contents !== '' ? $contents : null;
    }
}
