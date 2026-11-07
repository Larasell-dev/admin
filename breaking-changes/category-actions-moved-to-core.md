# The admin's category actions moved to the core

The admin's `MoveCategory`, `MoveCategoryPayload` and `DeleteCategory` are gone: moving a
category among its siblings and deleting it with every category below it are now the core's
`MoveCategory` and `DeleteCategoryTree`, so every app gets the same behavior and events. The
admin also needs the core's category actions that take payloads.

## Who is affected

Plugins and apps that call `Larasell\Admin\Category\Actions\MoveCategory` or
`Larasell\Admin\Category\Actions\DeleteCategory`. Apps that never call them can skip this.

## How to upgrade

```php
// Before
use Larasell\Admin\Category\Actions\DeleteCategory;
use Larasell\Admin\Category\Actions\MoveCategory;
use Larasell\Admin\Category\Actions\MoveCategoryPayload;

app(MoveCategory::class)->handle($boots, new MoveCategoryPayload(parentId: $sale->id, position: 1));
app(DeleteCategory::class)->handle($shoes, 'Shoes');

// After
use Larasell\Larasell\Catalog\Actions\DeleteCategoryTree;
use Larasell\Larasell\Catalog\Actions\MoveCategory;
use Larasell\Larasell\Catalog\Actions\MoveCategoryPayload;
use Larasell\Larasell\Catalog\CategoryId;

app(MoveCategory::class)->handle(CategoryId::of($boots), new MoveCategoryPayload(CategoryId::of($sale), 1));
app(DeleteCategoryTree::class)->handle(CategoryId::of($shoes));
```

The typed confirmation is now checked by the delete route's request, not by the action.
