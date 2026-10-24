# Navigation items can be nested, so the navigation list is now a tree

Navigation items can now be nested one level deep with `under()` or `children()`.
The `navigation` Inertia prop and `AdminPanel::navigationItems()` therefore return
top-level items only, and each item has a `children` array with its nested items.

## Who is affected

Code that reads the navigation list directly: a custom frontend that renders the
`navigation` prop, or PHP code that calls `AdminPanel::navigationItems()`. Nested
items are no longer in the top-level list, so code that loops over it misses them.

Apps that only register items with `Navigation::add()` and use the built-in sidebar
are not affected.

## How to upgrade

Read nested items from `children`. To get every item as a flat list, as before:

```ts
// Before
const items = props.navigation;

// After
const items = props.navigation.flatMap((item) => [item, ...item.children]);
```

```php
// Before
$items = AdminPanel::resolve()->navigationItems();

// After
$items = collect(AdminPanel::resolve()->navigationItems())
    ->flatMap(fn (array $item): array => [$item, ...$item['children']])
    ->all();
```
