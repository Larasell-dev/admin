# Default table titles no longer end in "table"

A table's default title now leaves out a `Table` suffix in its class name, so
`ProductReviewsTable` is titled "Product reviews" instead of "Product reviews
table". Tables can now be named with the suffix they infer their model from,
like `ProductReviewTable`, without it showing up in the title.

## Who is affected

Tables whose class name ends in `Table` and that don't override `title()`. The
new title also shows up in the page title of their `Route::index` route. Tables
without the suffix, and tables that define `title()`, are not affected.

## How to upgrade

To keep the old title, return it from `title()`:

```php
use Larasell\Admin\Tables\Table;

class ProductReviewsTable extends Table
{
    // Before: no title() method, titled "Product reviews table"

    // After
    public function title(): string
    {
        return 'Product reviews table';
    }
}
```
