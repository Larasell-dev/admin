<?php

namespace Larasell\Admin\Seo;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\Platform\Sections\Facades\Section;
use Larasell\Admin\Platform\Sections\SectionForm;
use Larasell\Admin\Product\Enums\ProductSection;
use Larasell\Admin\Product\Events\ProductSaved;
use Larasell\Admin\Seo\Actions\SaveProductUrls;
use Larasell\Admin\Seo\Data\ProductUrlsProps;
use Larasell\Admin\Seo\Http\Requests\ProductUrlsRequest;

/**
 * The SEO module: the URLs section of the product page, added through the page's sections like
 * a plugin's, so the Product module knows nothing about URLs. Writes go through the core's
 * SeoUrlManager, in the product's transaction.
 */
class SeoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Section::add(SectionForm::Product, ProductUrlsRequest::SECTION)
            ->after(ProductSection::Identifiers)
            ->props(ProductUrlsProps::class)
            ->request(ProductUrlsRequest::class);

        Event::listen(ProductSaved::class, SaveProductUrls::class);
    }
}
