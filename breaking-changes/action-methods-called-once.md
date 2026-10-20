# Action configuration methods can only be called once

Calling `to()`, `route()`, `confirm()`, `destructive()`, `disabled()` or
`icon()` a second time on the same action used to silently replace the first
call. It now throws an `InvalidTableAction` exception with the code
`larasell-admin.table.action.configured-twice`, so a call that has no effect
can't hide in a table definition.

## Who is affected

Tables that call one of these methods more than once on the same
`Larasell\Admin\Tables\Actions\Action`, including `to()` followed by `route()`,
since both set the link. If every method is called at most once per action,
you can skip this entry.

## How to upgrade

Keep only the call you want to take effect, usually the last one:

```php
use Larasell\Admin\Tables\Actions\Action;

// Before
Action::make('Delete', handle: fn (Book $book) => $book->delete())
    ->disabled()
    ->disabled(fn (Book $book) => $book->is_locked);

// After
Action::make('Delete', handle: fn (Book $book) => $book->delete())
    ->disabled(fn (Book $book) => $book->is_locked);
```
