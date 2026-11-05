import { router, usePage } from "@inertiajs/react";
import { LogoutBoxRLine } from "@larasell-dev/ui/icons";
import type { SharedProps } from "../../types";
import { defineCommand } from "./commands";

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
