<?php

namespace Workbench\App\ProductNotes;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Platform\Sections\Facades\Section;
use Larasell\Admin\Platform\Sections\SectionForm;
use Larasell\Admin\Product\Enums\ProductSection;
use Larasell\Admin\Product\Events\ProductSaved;

/**
 * An example plugin for the playground: a note for the team on each product, which the product
 * page shows and saves as a section of its own. The Product module knows nothing about it.
 */
class ProductNotesServiceProvider extends ServiceProvider
{
    public const SECTION = 'workbench-notes';

    public function boot(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__, 2).'/database/migrations');

        // The section's data, and the request that validates its fields.
        Section::add(SectionForm::Product, self::SECTION)
            ->after(ProductSection::General)
            ->props(ProductNotesProps::class)
            ->request(ProductNotesRequest::class);

        // Its business logic. Listening right away saves the note in the product's transaction.
        Event::listen(ProductSaved::class, SaveProductNote::class);

        // Its component, once `npm run build:workbench` built it; `testbench larasell:link` links it.
        $public = dirname(__DIR__, 2).'/public';

        if (is_file("{$public}/vendor/workbench/manifest.json") || is_file("{$public}/hot")) {
            AdminPanel::resolve()->vite('workbench', $public, ['resources/js/admin.tsx']);
        }
    }
}
