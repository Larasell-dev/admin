# Table column names must be unique and not reserved

A table whose columns share a name, or that has a column named `_actions` or
`_link`, now throws an `InvalidTableColumn` exception when it renders. Each row
stores a column's value under its name, so such a column silently overwrote
another column's value or the row's actions or link.

## Who is affected

Tables whose `columns()` return two columns with the same name, or a column
named `_actions` or `_link`. Tables with unique column names that don't start
with an underscore are not affected. An `id` column is still allowed.

## How to upgrade

Give the second column its own name and read the attribute in `map()`:

```php
use Larasell\Admin\Tables\Columns\TextColumn;

// Before
TextColumn::make('created_at', 'Received')->map(fn ($value) => $value?->diffForHumans()),
TextColumn::make('created_at', 'Date'),

// After
TextColumn::make('received')->map(fn ($value, ContactRequest $request) => $request->created_at?->diffForHumans()),
TextColumn::make('created_at', 'Date'),
```
