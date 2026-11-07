import { router, usePage } from "@inertiajs/react";
import { CompassLine, UserLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import type { SharedProps } from "../../../../types";
import { navigationIcon } from "../../../Support/navigationIcon";
import { defineCommand } from "./commands";

type NavigationItem = Larasell.Admin.Platform.Navigation.Data.NavigationItemData;

/** The pages an item stands for: itself, or the pages under it, e.g. those of Settings. */
const pages = (item: NavigationItem): NavigationItem[] =>
  item.children.length > 0 ? item.children.flatMap(pages) : [item];

/** Goes to a page of the navigation, so it offers the same pages, plugins' included, or the profile. */
export const goToCommand = defineCommand({
  id: "go-to",
  use() {
    const { navigation, profileUrl } = usePage<SharedProps>().props;

    return {
      label: fbs("Go to", "Command menu: opens the list of pages of the admin panel to go to"),
      icon: CompassLine,
      keywords: [
        fbs("navigate", "Command menu: search keyword for the command that goes to a page"),
        fbs("open", "Command menu: search keyword for the command that goes to a page"),
        fbs("page", "Command menu: search keyword for the command that goes to a page"),
      ],
      placeholder: fbs(
        "Search pages",
        "Command menu: search field placeholder in the list of pages to go to",
      ),
      items: navigation
        .flatMap(pages)
        .map((page) => ({
          id: page.id,
          label: page.label,
          icon: navigationIcon(page.icon, false),
          keywords:
            page.id === "larasell.admin.home"
              ? [fbs("dashboard", "Command menu: search keyword for going to the home page")]
              : undefined,
          run: () => router.visit(page.url),
        }))
        .concat({
          id: "profile",
          label: fbs(
            "Profile",
            "Command menu: goes to the page where the admin edits their own name, avatar and password",
          ),
          icon: UserLine,
          keywords: [
            fbs("account", "Command menu: search keyword for going to the admin's profile page"),
            fbs("avatar", "Command menu: search keyword for going to the admin's profile page"),
            fbs("password", "Command menu: search keyword for going to the admin's profile page"),
          ],
          run: () => router.visit(profileUrl),
        }),
    };
  },
});
