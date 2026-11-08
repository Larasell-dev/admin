<?php

namespace Larasell\Admin\Media;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Media\Console\GeneratePlaceholdersCommand;
use Larasell\Admin\Media\Http\Controllers\MediaController;
use Larasell\Admin\Media\Jobs\GeneratePlaceholder;
use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\Platform\Navigation\Facades\Navigation;
use Larasell\Admin\Platform\Navigation\NavItem;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Larasell\Media\Events\MediaThumbnailGenerated;
use Larasell\Larasell\Media\Events\MediaUploaded;
use Larasell\Larasell\Media\MediaType;
use Larasell\Larasell\Media\Models\Media;
use Larasell\Larasell\Media\Models\MediaAsset;

/**
 * The media library: a page to browse, upload and delete images and videos, and one that shows
 * an item full screen to edit its alt text. Images get a ThumbHash placeholder of the panel's own,
 * made on the queue. Writes go through the core's media actions. Setting
 * larasell.admin.media.enabled to false leaves out its routes and navigation item.
 */
class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/larasell/admin/media.php', 'larasell.admin.media');
    }

    public function boot(): void
    {
        if (! config('larasell.admin.media.enabled')) {
            return;
        }

        if ($this->app->runningInConsole()) {
            $this->commands([GeneratePlaceholdersCommand::class]);
        }

        // Once the upload is committed, so the worker finds the media.
        Event::listen(MediaUploaded::class, function (MediaUploaded $event): void {
            if (Media::query()->whereKey($event->mediaId->value)->value('type') === MediaType::Image) {
                GeneratePlaceholder::dispatch($event->mediaId)->afterCommit();
            }
        });

        // A video's comes from its poster, so it waits until the core made one.
        Event::listen(MediaThumbnailGenerated::class, function (MediaThumbnailGenerated $event): void {
            if (Media::query()->whereKey($event->mediaId->value)->value('type') === MediaType::Video) {
                GeneratePlaceholder::dispatch($event->mediaId);
            }
        });

        AdminPanel::resolve()->routes(function (): void {
            Route::get('media', [MediaController::class, 'index'])->name('media.index')->withHead(title: Translate::later('Media'));
            Route::post('media', [MediaController::class, 'store'])->name('media.store');
            Route::get('media/{media}', [MediaController::class, 'show'])->whereNumber('media')->name('media.show');
            Route::put('media/{media}', [MediaController::class, 'update'])->whereNumber('media')->name('media.update');
            // Private files reach the browser only through here, behind the panel's sign-in.
            Route::get('media/{media}/file/{role?}', [MediaController::class, 'file'])->whereNumber('media')->whereIn('role', [MediaAsset::ORIGINAL, MediaAsset::THUMBNAIL])->name('media.file');
            Route::delete('media/{media}', [MediaController::class, 'destroy'])->whereNumber('media')->name('media.destroy');
        });

        // Between Categories and Orders, like an app's own item; the built-in positions stay 1000 apart.
        Navigation::add(Translate::later('Media'), 'larasell.admin.media.index', Icon::Image)->position(NavItem::Categories->position() + 500);
    }
}
