import { Link, usePage } from "@inertiajs/react";
import { Icon } from "@larasell-dev/ui";
import { RovingFocusGroup, RovingFocusItem } from "@roving-focus/react";
import { arrayFirst, assertDefined } from "ts-extras";
import type { NavigationItem, Page } from "../types";
import { navigationIcon } from "../utils/navigationIcon";
import Logo from "./Logo";
import UserMenu from "./UserMenu";

export default function Sidebar() {
  const { props } = usePage<Page>();
  const activeItem = useActiveNavigationItem();
  const home = arrayFirst(props.navigation);
  assertDefined(home, "The admin panel needs at least one navigation item.");

  return (
    <aside className="flex h-dvh w-64 shrink-0 flex-col border-r border-border bg-white dark:bg-mauve-950">
      <div className="flex h-14 shrink-0 items-center border-b border-border px-6">
        <Link
          href={home.url}
          aria-label="Home"
          className="inline-block outline-none focus-visible:focus-ring"
        >
          <Logo variant="wordmark" className="h-6 w-auto text-mauve-950 dark:text-white" />
        </Link>
      </div>

      <RovingFocusGroup
        as="nav"
        orientation="vertical"
        className="flex flex-1 flex-col overflow-y-auto pb-3"
      >
        {props.navigation.map((item) => (
          <RovingFocusItem key={item.url} asChild active={item === activeItem}>
            <Link
              href={item.url}
              aria-current={item === activeItem ? "page" : undefined}
              className={`type-body-md flex items-center gap-3 px-6 py-2 outline-none focus-visible:focus-ring focus-visible:-outline-offset-2 ${
                item === activeItem
                  ? "bg-mauve-100 text-mauve-950 dark:bg-mauve-900 dark:text-mauve-50"
                  : "text-mauve-600 hover:bg-mauve-50 hover:text-mauve-950 dark:text-mauve-400 dark:hover:bg-mauve-900 dark:hover:text-mauve-50"
              }`}
            >
              <span className="flex text-base">
                <Icon icon={navigationIcon(item.icon, item === activeItem)} />
              </span>
              {item.label}
            </Link>
          </RovingFocusItem>
        ))}
      </RovingFocusGroup>

      <UserMenu />
    </aside>
  );
}

const pathOf = (url: string) => new URL(url, window.location.origin).pathname;

const isUnder = (path: string, parent: string) => path.startsWith(`${parent}/`);

/**
 * The navigation item for the current page: the item with its exact path, or else the
 * one with the longest path above it, so `/admin/categories/12` belongs to Categories.
 * An item with other items under it, such as Home at `/admin`, only matches its own page.
 */
function useActiveNavigationItem(): NavigationItem | null {
  const { props, url } = usePage<Page>();
  const path = pathOf(url);
  const paths = props.navigation.map((item) => pathOf(item.url));
  let best: NavigationItem | null = null;
  let bestLength = -1;

  for (const [index, item] of props.navigation.entries()) {
    const itemPath = paths[index];
    if (itemPath === path) return item;
    if (!isUnder(path, itemPath) || paths.some((other) => isUnder(other, itemPath))) continue;
    if (itemPath.length > bestLength) {
      best = item;
      bestLength = itemPath.length;
    }
  }

  return best;
}
