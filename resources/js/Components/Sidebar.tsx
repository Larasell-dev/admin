import { Link, usePage } from "@inertiajs/react";
import { Icon, IconButton } from "@larasell-dev/ui";
import { ArrowRightSLine } from "@larasell-dev/ui/icons";
import { motion } from "motion/react";
import { Fragment, type ReactNode, useId, useState } from "react";
import { RovingFocusGroup, RovingFocusItem } from "@roving-focus/react";
import { cn } from "cn";
import { arrayFirst, assertDefined } from "ts-extras";
import type { Page } from "../types";
import { useFrozen } from "./Freeze";
import { navigationIcon } from "../utils/navigationIcon";
import Logo from "./Logo";
import UserMenu from "./UserMenu";

type NavigationItem = Larasell.Admin.Data.ViewModel.NavigationItemData;

/** `frozen` keeps showing the page as it was, such as while the mobile nav slides out. */
export default function Sidebar({ frozen = false }: { frozen?: boolean }) {
  const page = useFrozen(usePage<Page>(), frozen);
  const { props } = page;
  const activeItem = activeNavigationItem(page);
  const [isOpen, toggle] = useOpenGroups(props.navigation, activeItem);
  const groupId = useId();
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
        {props.navigation.map((item, index) => {
          const open = isOpen(item);
          const id = `${groupId}-${index}`;

          return (
            <Fragment key={item.url}>
              <NavigationLink
                item={item}
                active={item === activeItem}
                toggle={
                  item.children.length > 0 && (
                    <GroupToggle
                      label={item.label}
                      open={open}
                      controls={id}
                      onClick={() => toggle(item)}
                    />
                  )
                }
              />
              {item.children.length > 0 && open && (
                <ul id={id} aria-label={item.label}>
                  {item.children.map((child) => (
                    <li key={child.url}>
                      <NavigationLink item={child} active={child === activeItem} nested />
                    </li>
                  ))}
                </ul>
              )}
            </Fragment>
          );
        })}
      </RovingFocusGroup>

      <UserMenu />
    </aside>
  );
}

/**
 * A navigation row. The group toggle sits next to the link, not inside it, so clicking it
 * never follows the link; hovering either highlights the whole row.
 */
function NavigationLink({
  item,
  active,
  nested = false,
  toggle,
}: {
  item: NavigationItem;
  active: boolean;
  nested?: boolean;
  toggle?: ReactNode;
}) {
  return (
    <div className="group/row relative">
      <RovingFocusItem asChild active={active}>
        <Link
          href={item.url}
          aria-current={active ? "page" : undefined}
          className={cn(
            // Row
            "type-body-md flex items-center gap-3 px-6 py-2",
            // Room for the group toggle on the right
            toggle && "pr-10",
            // Focus
            "outline-none focus-visible:focus-ring focus-visible:-outline-offset-2",
            // Current page
            active && "bg-mauve-100 text-mauve-950 dark:bg-mauve-900 dark:text-mauve-50",
            // Other pages, highlighted while the row, toggle included, is hovered
            !active && "text-mauve-600 dark:text-mauve-400",
            !active &&
              "group-hover/row:bg-mauve-50 group-hover/row:text-mauve-950 dark:group-hover/row:bg-mauve-900 dark:group-hover/row:text-mauve-50",
          )}
        >
          {/* Children line up under their parent's label; the MobileNav overrides the link's own padding. */}
          <span className={cn("flex", nested && "pl-7")}>
            <Icon icon={navigationIcon(item.icon, active)} size={4} />
          </span>
          {item.label}
        </Link>
      </RovingFocusItem>
      {toggle && <div className="absolute top-0 right-0 flex items-center">{toggle}</div>}
    </div>
  );
}

/** The chevron that opens and closes a group, styled like a category's chevron. */
function GroupToggle({
  label,
  open,
  controls,
  onClick,
}: {
  label: string;
  open: boolean;
  controls: string;
  onClick: () => void;
}) {
  return (
    <RovingFocusItem asChild>
      <IconButton
        variant="tertiary"
        size="sm"
        aria-label={`${open ? "Close" : "Open"} ${label}`}
        aria-expanded={open}
        aria-controls={open ? controls : undefined}
        onClick={onClick}
        DANGEROUS_className="focus-visible:-outline-offset-2"
      >
        <motion.span
          className="flex"
          initial={false}
          animate={{ rotate: open ? 90 : 0 }}
          transition={{ type: "spring", bounce: 0, visualDuration: 0.15 }}
        >
          <Icon icon={ArrowRightSLine} />
        </motion.span>
      </IconButton>
    </RovingFocusItem>
  );
}

/**
 * Which groups are open. The current page's group opens by default, and again whenever the
 * current page moves to another group; otherwise the toggles decide.
 */
function useOpenGroups(navigation: NavigationItem[], activeItem: NavigationItem | null) {
  const activeGroup = navigation.find(
    (item) => item === activeItem || item.children.some((child) => child === activeItem),
  )?.id;
  const [open, setOpen] = useState(() => new Set(activeGroup ? [activeGroup] : []));
  const [openedFor, setOpenedFor] = useState(activeGroup);

  if (activeGroup !== openedFor) {
    setOpenedFor(activeGroup);
    if (activeGroup) setOpen(new Set(open).add(activeGroup));
  }

  const toggle = (item: NavigationItem) => {
    const next = new Set(open);
    if (!next.delete(item.id)) next.add(item.id);
    setOpen(next);
  };

  return [(item: NavigationItem) => open.has(item.id), toggle] as const;
}

const pathOf = (url: string) => new URL(url, window.location.origin).pathname;

const isUnder = (path: string, parent: string) => path.startsWith(`${parent}/`);

/**
 * The navigation item for the current page: the item with its exact path, or else the
 * one with the longest path above it, so `/admin/categories/12` belongs to Categories.
 * An item with a sibling under it, such as Home at `/admin`, only matches its own page.
 * Only siblings count: top-level items for a top-level item, the other children of the
 * same parent for a child. So neither an item's own children nor another group's child
 * stop Categories from matching `/admin/categories/12`.
 */
function activeNavigationItem({ props, url }: { props: Page; url: string }): NavigationItem | null {
  const path = pathOf(url);
  const groups = [props.navigation, ...props.navigation.map((item) => item.children)];
  let best: NavigationItem | null = null;
  let bestLength = -1;

  for (const siblings of groups) {
    const paths = siblings.map((item) => pathOf(item.url));

    for (const [index, item] of siblings.entries()) {
      const itemPath = paths[index];
      if (itemPath === path) return item;
      if (!isUnder(path, itemPath) || paths.some((other) => isUnder(other, itemPath))) continue;
      if (itemPath.length > bestLength) {
        best = item;
        bestLength = itemPath.length;
      }
    }
  }

  return best;
}
