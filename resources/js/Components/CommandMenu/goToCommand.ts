import { router, usePage } from "@inertiajs/react";
import { CompassLine, UserLine } from "@larasell-dev/ui/icons";
import type { SharedProps } from "../../types";
import { navigationIcon } from "../../utils/navigationIcon";
import { defineCommand } from "./commands";

type NavigationItem = Larasell.Admin.Platform.Navigation.NavigationItemData;

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
