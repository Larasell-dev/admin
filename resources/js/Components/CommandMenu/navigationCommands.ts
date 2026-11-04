import { router, usePage } from "@inertiajs/react";
import { CompassLine, LogoutBoxRLine, UserLine } from "@larasell-dev/ui/icons";
import type { SharedProps } from "../../types";
import { navigationIcon } from "../../utils/navigationIcon";
import { defineCommand } from "./commands";

type NavigationItem = Larasell.Admin.Data.ViewModel.NavigationItemData;

/** The pages an item stands for: itself, or the pages under it, e.g. those of Settings. */
const pages = (item: NavigationItem): NavigationItem[] =>
  item.children.length > 0 ? item.children.flatMap(pages) : [item];

/** Goes to a page of the navigation, so it offers the same pages, plugins' included, or the profile. */
export const goToCommand = defineCommand({
  id: "go-to",
  use() {
    const { navigation, profileUrl } = usePage<SharedProps>().props;

    return {
      label: "Go to",
      icon: CompassLine,
      keywords: ["navigate", "open", "page"],
      placeholder: "Search pages",
      items: navigation
        .flatMap(pages)
        .map((page) => ({
          id: page.id,
          label: page.label,
          icon: navigationIcon(page.icon, false),
          keywords: page.id === "larasell.admin.home" ? ["dashboard"] : undefined,
          run: () => router.visit(page.url),
        }))
        .concat({
          id: "profile",
          label: "Profile",
          icon: UserLine,
          keywords: ["account", "avatar", "password"],
          run: () => router.visit(profileUrl),
        }),
    };
  },
});

/** Logs out after a yes, so a stray Enter doesn't. */
export const logOutCommand = defineCommand({
  id: "log-out",
  use() {
    const { logoutUrl } = usePage<SharedProps>().props;

    return {
      label: "Log out",
      icon: LogoutBoxRLine,
      keywords: ["sign out"],
      placeholder: "Log out?",
      searchItemsFromRoot: false,
      items: [
        // First, so it's the one highlighted when the view opens. Picking it closes the menu.
        { id: "no", label: "No", run: () => {} },
        { id: "yes", label: "Yes", run: () => router.post(logoutUrl) },
      ],
    };
  },
});
