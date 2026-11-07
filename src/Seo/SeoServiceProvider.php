<?php

namespace Larasell\Admin\Seo;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\Category\Enums\CategorySection;
use Larasell\Admin\Category\Events\CategorySaved;
use Larasell\Admin\Platform\Sections\Facades\Section;
use Larasell\Admin\Platform\Sections\SectionForm;
use Larasell\Admin\Product\Enums\ProductSection;
use Larasell\Admin\Product\Events\ProductSaved;
use Larasell\Admin\Seo\Actions\SaveSeoUrls;
use Larasell\Admin\Seo\Data\SeoUrlsProps;
use Larasell\Admin\Seo\Http\Requests\SeoUrlsRequest;

/**
 * The SEO module: the URLs sections of the product and category pages, added through the pages'
 * sections like a plugin's, so the Product and Category modules know nothing about URLs. Writes
 * go through the core's SeoUrlManager, in the product's or category's transaction.
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

        Event::listen(ProductSaved::class, [SaveSeoUrls::class, 'product']);
        Event::listen(CategorySaved::class, [SaveSeoUrls::class, 'category']);
    }
}
