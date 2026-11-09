<?php

namespace Larasell\Admin\Media\Console;

use Illuminate\Console\Command;
use Larasell\Admin\Media\Jobs\GeneratePlaceholder;
use Larasell\Admin\Media\Models\MediaPlaceholder;
use Larasell\Larasell\Media\MediaId;
use Larasell\Larasell\Media\MediaType;
use Larasell\Larasell\Media\Models\Media;

/** Queues the media library's placeholders for images without one, e.g. uploaded before the panel made them. */
final class GeneratePlaceholdersCommand extends Command
{
    protected $signature = 'larasell:generate-media-placeholders {--force : Make every placeholder again}';

    protected $description = 'Queue the media library\'s placeholders for the images and videos that have none';

    public function handle(): int
    {
        // Images, and videos once they have a poster to make it from.
        $query = Media::query()->select('id')->where(fn ($query) => $query
            ->where('type', MediaType::Image)
            ->orWhereHas('thumbnail'));

        if (! $this->option('force')) {
            $query->whereNotIn('id', MediaPlaceholder::query()->select('media_id'));
        }

        $count = 0;
        // In chunks, so a large library never sits in memory at once.
        $query->chunkById(500, function ($media) use (&$count): void {
            foreach ($media as $item) {
                GeneratePlaceholder::dispatch(MediaId::of($item));
                $count++;
            }
        });

        $this->info("Queued {$count} placeholder(s).");

        return self::SUCCESS;
    }
}
