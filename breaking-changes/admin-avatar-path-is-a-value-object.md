# An admin's `avatar_path` is now an `AvatarPath`

`Admin::$avatar_path` is cast to `Larasell\Admin\Platform\Auth\AvatarPath` instead of a string. The panel
deletes avatars by this path, so it now only holds a file directly in `avatars/`, the folder uploads
are stored in, and never points at another file on the panel disk.

## Who is affected

Apps that read or write `avatar_path` on `Larasell\Admin\Platform\Auth\Models\Admin`. Apps that only
use `Admin::avatarUrl()` are not affected.

Setting a path outside `avatars/`, and reading one stored there by other means, throws
`Larasell\Admin\Platform\Auth\Exceptions\InvalidAvatarPath`.

## How to upgrade

Read the path from the value object. Setting a string in `avatars/` still works.

```php
// Before
$path = $admin->avatar_path;

// After
$path = $admin->avatar_path?->path;
```

If your app stored avatars elsewhere, move them into `avatars/` on the panel disk and update
`avatar_path` before upgrading.
