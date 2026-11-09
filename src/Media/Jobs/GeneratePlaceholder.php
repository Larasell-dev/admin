<?php

namespace Larasell\Admin\Media\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Larasell\Admin\Media\Models\MediaPlaceholder;
use Larasell\Larasell\Media\AssetStatus;
use Larasell\Larasell\Media\Exceptions\MediaNotShared;
use Larasell\Larasell\Media\MediaId;
use Larasell\Larasell\Media\MediaProviders;
use Larasell\Larasell\Media\MediaType;
use Larasell\Larasell\Media\Models\Media;
use Thumbhash\Thumbhash;

/**
 * Makes the ThumbHash the media library shows while an image loads. Queued on
 * larasell.admin.media.queue, so workers do the decoding instead of the web server; a worker on
 * another server needs disks it shares with the app.
 */
final class GeneratePlaceholder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** ThumbHash takes images of at most 100 × 100 pixels. */
    private const MAX_SIZE = 100;

    public function __construct(public readonly MediaId $mediaId)
    {
        $this->onQueue(config('larasell.admin.media.queue'));
    }

    /**
     * @throws MediaNotShared when this worker can't read the original
     */
    public function handle(MediaProviders $providers): void
    {
        $media = Media::query()->with(['original', 'thumbnail'])->find($this->mediaId->value);
        // A video's placeholder comes from its poster, the frame the core's thumbnail job took.
        $source = $media?->type === MediaType::Video ? $media->thumbnail : $media?->original;

        // Deleted before the job ran, or a video without a poster yet.
        if ($media === null || $source === null) {
            return;
        }

        $storage = $providers->provider($source->provider);

        // Not checked by its provider yet, or at one that keeps its bytes to itself, e.g. Mux.
        if ($source->status !== AssetStatus::Ready || ! $storage->readable()) {
            return;
        }

        $stream = $storage->readStream($source->file()) ?? throw MediaNotShared::original((string) $source->disk, $source->path);
        $image = imagecreatefromstring((string) stream_get_contents($stream));
        fclose($stream);

        if ($image === false) {
            return;
        }

        $scale = min(1, self::MAX_SIZE / max(imagesx($image), imagesy($image)));
        $small = imagescale($image, max(1, (int) round(imagesx($image) * $scale)), max(1, (int) round(imagesy($image) * $scale)));
        imagedestroy($image);

        if ($small === false) {
            return;
        }

        [$width, $height] = [imagesx($small), imagesy($small)];
        $rgba = [];

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorsforindex($small, (int) imagecolorat($small, $x, $y));
                // GD's alpha runs from 0 (opaque) to 127 (transparent).
                array_push($rgba, $color['red'], $color['green'], $color['blue'], (int) round(255 - $color['alpha'] * 255 / 127));
            }
        }

        imagedestroy($small);

        MediaPlaceholder::query()->updateOrCreate(
            ['media_id' => $media->id],
            ['thumbhash' => Thumbhash::convertHashToString(Thumbhash::RGBAToHash($width, $height, $rgba))],
        );
    }
}
