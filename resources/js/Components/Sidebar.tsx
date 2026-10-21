import { Link, usePage } from "@inertiajs/react";
import { Icon } from "@larasell-dev/ui";
import { RovingFocusGroup, RovingFocusItem } from "@roving-focus/react";
import { arrayFirst, assertDefined } from "ts-extras";
import type { Page } from "../types";
import { activeNavigationItem } from "../utils/activeNavigationItem";
import { navigationIcon } from "../utils/navigationIcon";
import Logo from "./Logo";
import UserMenu from "./UserMenu";

export default function Sidebar() {
  const { props, url } = usePage<Page>();
  const activeItem = activeNavigationItem(props.navigation, url.split("?")[0]);
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
        {props.navigation.map((item) => {
          const active = item === activeItem;

          return (
            <RovingFocusItem key={item.url} asChild active={active}>
              <Link
                href={item.url}
                aria-current={active ? "page" : undefined}
                className={`type-body-md flex items-center gap-3 px-6 py-2 outline-none focus-visible:focus-ring focus-visible:-outline-offset-2 ${
                  active
                    ? "bg-mauve-100 text-mauve-950 dark:bg-mauve-900 dark:text-mauve-50"
                    : "text-mauve-600 hover:bg-mauve-50 hover:text-mauve-950 dark:text-mauve-400 dark:hover:bg-mauve-900 dark:hover:text-mauve-50"
                }`}
              >
                <span className="flex text-base">
                  <Icon icon={navigationIcon(item.icon, active)} />
                </span>
                {item.label}
              </Link>
            </RovingFocusItem>
          );
        })}
      </RovingFocusGroup>

      <UserMenu />
    </aside>
  );
}
