# Products and categories have URLs per sales channel instead of a slug

The product and category forms no longer have a **Slug** field. A product's
storefront address is now an SEO URL from the core's SEO module: one per sales
channel and language, made from the name. Old paths keep redirecting.

## Why

The core dropped the `slug` columns: a URL now belongs to a sales channel and
language, not to the product, and the core keeps their history for redirects.

## Who is affected

Apps that post to the product or category routes themselves, read the page
props, or extend the panel's catalog classes. Everyone else only needs the
new core version.

- The `slug` input of `products.store`, `products.update`, `categories.store`
  and `categories.update` is gone.
- `ProductDetailData` and `CategoryDetailData` no longer have `slug` and
  `fallbackSlug`.
- The core models moved to `Larasell\Larasell\Catalog\Models`:

```php
// Before
use Larasell\Larasell\Models\Product;

// After
use Larasell\Larasell\Catalog\Models\Product;
```

- `Larasell\Admin\Category\Actions\DeleteCategory` and `MoveCategory` now take
  the core's actions in their constructors; resolve them from the container
  instead of calling `new`.
