# The `Slug` validation rule is gone

Products and categories no longer have slugs, so nothing in the panel validated
with `Larasell\Admin\Platform\Support\Rules\Slug` anymore.

## Who is affected

Apps and extensions that use `Larasell\Admin\Platform\Support\Rules\Slug` in
their own validation rules. Everyone else can skip this entry.

## How to upgrade

Use a `regex` rule with the same pattern:

```php
// Before
use Larasell\Admin\Platform\Support\Rules\Slug;

'handle' => ['required', 'string', new Slug],

// After
'handle' => ['required', 'string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
```
