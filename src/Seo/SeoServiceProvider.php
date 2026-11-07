<?php

namespace Larasell\Admin\Seo;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Category\Enums\CategorySection;
use Larasell\Admin\Platform\Sections\Facades\Section;
use Larasell\Admin\Platform\Sections\SectionForm;
use Larasell\Admin\Product\Enums\ProductSection;
use Larasell\Admin\Seo\Data\SeoUrlsProps;
use Larasell\Admin\Seo\Http\Controllers\RemoveOldUrlController;
use Larasell\Admin\Seo\Http\Controllers\RestoreOldUrlController;
use Larasell\Admin\Seo\Http\Requests\SeoUrlsRequest;
use Larasell\Larasell\Seo\Exceptions\CustomUrlRejected;
use Larasell\Larasell\Seo\Exceptions\InvalidSeoPath;

/**
 * The SEO module: the URLs sections of the product and category pages, added through the pages'
 * sections like a plugin's, so the Product and Category modules know nothing about URLs. The
 * section's payload is the core's CustomUrls, which the pages pass along with the catalog action;
 * the core saves it in the action's transaction.
 */
class SeoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Section::add(SectionForm::Product, SeoUrlsRequest::SECTION)
            ->after(ProductSection::Identifiers)
            ->props(SeoUrlsProps::class)
            ->request(SeoUrlsRequest::class);

        Section::add(SectionForm::Category, SeoUrlsRequest::SECTION)
            ->after(CategorySection::Products)
            ->props(SeoUrlsProps::class)
            ->request(SeoUrlsRequest::class);

        AdminPanel::resolve()->routes(function (): void {
            Route::delete('seo-urls/{url}', RemoveOldUrlController::class)->where('url', '[1-9][0-9]*')->name('seo-urls.destroy');
            Route::post('seo-urls/{url}/restore', RestoreOldUrlController::class)->where('url', '[1-9][0-9]*')->name('seo-urls.restore');
        });

        // A path the core refused shows on its field, and nothing of the page is saved.
        // Only the HTTP handler maps exceptions; console runs, e.g. under Collision, have no form.
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! $handler instanceof Handler) {
                return;
            }

            $handler->map(CustomUrlRejected::class, fn (CustomUrlRejected $exception): ValidationException => ValidationException::withMessages([
                'sections.'.SeoUrlsRequest::SECTION.".urls.{$exception->context->channelId->value}.path" => match ($exception->reason) {
                    InvalidSeoPath::TAKEN => __('Another page already uses this URL.'),
                    InvalidSeoPath::RESERVED => __('This URL is reserved for other pages of the store.'),
                    default => __('Use letters, numbers, dashes, dots and slashes, without spaces.'),
                },
            ]));
        });
    }
}
