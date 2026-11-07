<?php

namespace Workbench\App\Storefront;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Seo\Facades\Seo;
use Larasell\Larasell\Seo\SeoUrlManager;
use Larasell\Larasell\Storefront\StorefrontContext;

/**
 * A bare storefront for the playground, to try the SEO URLs the admin edits: a home page that
 * links every product and category through its canonical URL, and Route::seo() pages for them.
 * Each page shows the request's context and the page's URL rows, and the home page lists every
 * redirect in the channel and language, so redirects can be clicked.
 */
class StorefrontServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(dirname(__DIR__, 2).'/resources/views/storefront', 'storefront');

        Route::storefront(function (): void {
            Route::get('/', function (StorefrontContext $storefront) {
                $products = Product::query()->where('status', Visibility::Visible)->orderBy('id')->get();
                $categories = Category::query()->where('status', Visibility::Visible)->orderBy('position')->orderBy('id')->get();
                Seo::preload([...$products, ...$categories]);

                return view('storefront::home', ['storefront' => $storefront, 'products' => $products, 'categories' => $categories, 'redirects' => self::redirects($storefront)]);
            })->name('home');

            Route::seo([
                Product::class => fn (Product $product, StorefrontContext $storefront) => view('storefront::page', [
                    'storefront' => $storefront,
                    'title' => $product->name->get($storefront->sales->locale->value),
                    'detail' => $product->price->amount().' (minor units) · '.$storefront->sales->currency->value,
                    'urls' => self::urls('product', $product->id, $storefront),
                ]),
                Category::class => fn (Category $category, StorefrontContext $storefront) => view('storefront::page', [
                    'storefront' => $storefront,
                    'title' => $category->name->get($storefront->sales->locale->value),
                    'detail' => $category->products()->count().' products',
                    'urls' => self::urls('category', $category->id, $storefront),
                ]),
            ]);
        });
    }

    /**
     * Every old URL in this channel and language, newest first, with where opening it leads: a
     * historic URL to its page's canonical path, a gone one to its replacement's, or nowhere (410).
     *
     * @return list<object{path: string, status: string, resource: string, url: string, target: ?string, target_url: ?string}>
     */
    private static function redirects(StorefrontContext $storefront): array
    {
        $manager = app(SeoUrlManager::class);

        return DB::table('larasell_seo_urls')
            ->where(['sales_channel_id' => $storefront->sales->channelId->value, 'locale' => $storefront->sales->locale->value])
            ->where('status', '!=', 'canonical')
            ->orderByDesc('id')
            ->get(['path', 'status', 'resource_type', 'resource_id', 'replacement_type', 'replacement_id'])
            ->map(function (object $row) use ($storefront, $manager): object {
                $target = match (true) {
                    $row->status === 'historic' => $manager->canonicalPath($row->resource_type, $row->resource_id, $storefront->sales),
                    $row->replacement_type !== null => $manager->canonicalPath($row->replacement_type, $row->replacement_id, $storefront->sales),
                    default => null,
                };

                return (object) [
                    'path' => $row->path,
                    'status' => $row->status,
                    'resource' => $row->resource_type.' #'.$row->resource_id,
                    'url' => $storefront->url($row->path),
                    'target' => $target,
                    'target_url' => $target === null ? null : $storefront->url($target),
                ];
            })
            ->all();
    }

    /**
     * Every URL the page ever had in this channel and language, newest first.
     *
     * @return list<object{path: string, status: string, is_custom: int|bool, url: string}>
     */
    private static function urls(string $type, int $id, StorefrontContext $storefront): array
    {
        return DB::table('larasell_seo_urls')
            ->where(['resource_type' => $type, 'resource_id' => $id, 'sales_channel_id' => $storefront->sales->channelId->value, 'locale' => $storefront->sales->locale->value])
            ->orderByDesc('is_canonical')->orderByDesc('id')
            ->get(['path', 'status', 'is_custom'])
            ->map(function (object $row) use ($storefront): object {
                $row->url = $storefront->url($row->path);

                return $row;
            })
            ->all();
    }
}
