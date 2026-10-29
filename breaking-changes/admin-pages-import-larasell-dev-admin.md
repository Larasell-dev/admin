# Admin pages import `@larasell-dev/admin` and register with `Larasell.booting()`

The `larasell-admin/register`, `larasell-admin/components` and `larasell-admin/layouts` modules
are now one module, `@larasell-dev/admin`. Pages are registered in a `Larasell.booting()`
callback and load when they're first opened instead of on every admin page. Every export of
React, Inertia and `@larasell-dev/ui` is now available to admin pages, not only the hooks and
components that were listed by hand.

## Who is affected

Apps with their own admin pages in `resources/js/admin`, built with the `admin()` Vite plugin.
Apps without that directory are not affected.

## How to upgrade

Register pages with a loader in `resources/js/admin/app.tsx`:

```tsx
// Before
import { page } from "larasell-admin/register";
import Report from "./pages/Report";

page.add("Report", Report);

// After
import { Larasell } from "@larasell-dev/admin";

Larasell.booting(({ pages }) => {
  pages.register("Report", () => import("./pages/Report"));
});
```

Import components and layouts from `@larasell-dev/admin`:

```tsx
// Before
import { ResourceTable } from "larasell-admin/components";
import { SidebarLayout } from "larasell-admin/layouts";

// After
import { ResourceTable, SidebarLayout } from "@larasell-dev/admin";
```

The `TableResource` type and its parts now match what tables send: they include
`headerActions`, `pagination`, badge columns and row links.
