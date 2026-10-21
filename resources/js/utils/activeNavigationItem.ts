import type { NavigationItem } from "../types";

const pathOf = (url: string) => new URL(url, window.location.origin).pathname;

const isUnder = (path: string, parent: string) => path.startsWith(`${parent}/`);

/**
 * The navigation item for the page at `path`: the item with that exact path, or else the
 * one with the longest path above it, so `/admin/categories/12` belongs to Categories.
 * An item with other items under it, such as Home at `/admin`, only matches its own page.
 */
export function activeNavigationItem(items: NavigationItem[], path: string): NavigationItem | null {
  const paths = items.map((item) => pathOf(item.url));
  let best: NavigationItem | null = null;
  let bestLength = -1;

  for (const [index, item] of items.entries()) {
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
