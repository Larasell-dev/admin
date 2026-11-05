# The Admins settings page is now Team

The settings page that lists the people who can sign in to the panel is called **Team** now. It
used to be **Admins**, which named the role rather than the people who run the store with you. Its
address, route names and Inertia page names changed with it, and in its copy the people on the
page are "members". The page moved into a `Team` module, which also
registers it and the profile pages.

The account itself is still an admin: the `Admin` model, the `larasell-admin` guard, the
`CreateAdmin`, `UpdateAdmin` and `DeleteAdmin` actions, the admin commands and the `admin.*` audit
events keep their names.

## Who is affected

- Apps that link to the page by route name or address, e.g. `route('larasell.admin.settings.admins.index')`
  or `/admin/settings/admins`.
- Apps that place a navigation item next to it, e.g. `->after('larasell.admin.settings.admins.index')`.
- Apps that override the `Admin/Index`, `Admin/Create` or `Admin/Edit` pages, or read their props.
- Apps that use the renamed classes in the table below.

Apps that only sign in and use the panel are not affected. Old bookmarks to `/admin/settings/admins`
now show the 404 page.

## How to upgrade

Use the new route names and addresses:

| Before                                                                 | After                                                              |
| ---------------------------------------------------------------------- | ------------------------------------------------------------------ |
| `larasell.admin.settings.admins.index` (`settings/admins`)             | `larasell.admin.settings.team.index` (`settings/team`)             |
| `larasell.admin.settings.admins.create` (`settings/admins/create`)     | `larasell.admin.settings.team.create` (`settings/team/create`)     |
| `larasell.admin.settings.admins.store`                                 | `larasell.admin.settings.team.store`                               |
| `larasell.admin.settings.admins.edit` (`settings/admins/{admin}/edit`) | `larasell.admin.settings.team.edit` (`settings/team/{admin}/edit`) |
| `larasell.admin.settings.admins.update`                                | `larasell.admin.settings.team.update`                              |

```php
// Before
Navigation::add('Roles', 'roles.index', Icon::Shield)
    ->under(NavItem::Settings)
    ->after('larasell.admin.settings.admins.index');

// After
Navigation::add('Roles', 'roles.index', Icon::Shield)
    ->under(NavItem::Settings)
    ->after('larasell.admin.settings.team.index');
```

Pages that override the panel's own move from `resources/js/admin/pages/Admin/` to
`resources/js/admin/pages/Team/`. Their props changed:

| Page         | Before        | After     |
| ------------ | ------------- | --------- |
| `Team/Index` | `admins`      | `members` |
| `Team/Edit`  | `editedAdmin` | `member`  |

The dashboard's `inviteAdminUrl` prop is now `inviteMemberUrl`.

The renamed classes, all in `Larasell\Admin\Team`:

| Before                             | After                             |
| ---------------------------------- | --------------------------------- |
| `Http\Controllers\AdminController` | `Http\Controllers\TeamController` |
| `Http\Requests\AdminRequest`       | `Http\Requests\MemberRequest`     |
| `Data\AdminsPageData`              | `Data\TeamPageData`               |
| `Data\CreateAdminPageData`         | `Data\CreateMemberPageData`       |
| `Data\EditAdminPageData`           | `Data\EditMemberPageData`         |
| `Tables\Admins`                    | `Tables\Members`                  |

The entry "The code is grouped by module" lists their old namespaces.
