import { router, usePage } from "@inertiajs/react";
import { LogoutBoxRLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import type { SharedProps } from "../../types";
import { defineCommand } from "./commands";

/** Logs out after a yes, so a stray Enter doesn't. */
export const logOutCommand = defineCommand({
  id: "log-out",
  use() {
    const { logoutUrl } = usePage<SharedProps>().props;

    return {
      label: fbs("Log out", "Command menu: signs the admin out of the panel"),
      icon: LogoutBoxRLine,
      keywords: [
        fbs("sign out", "Command menu: search keyword for the command that logs the admin out"),
      ],
      placeholder: fbs(
        "Log out?",
        "Command menu: question asked before logging out, answered with Yes or No",
      ),
      searchItemsFromRoot: false,
      items: [
        // First, so it's the one highlighted when the view opens. Picking it closes the menu.
        {
          id: "no",
          label: fbs("No", "Command menu: answer to 'Log out?' that keeps the admin signed in"),
          run: () => {},
        },
        {
          id: "yes",
          label: fbs("Yes", "Command menu: answer to 'Log out?' that logs the admin out"),
          run: () => router.post(logoutUrl),
        },
      ],
    };
  },
});
