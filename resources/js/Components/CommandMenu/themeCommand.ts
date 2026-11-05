import { ContrastLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import { setTheme, Theme, themeLabel, useTheme } from "../../utils/theme";
import { defineCommand } from "./commands";

/** Switches between the light, dark and system theme, with a check on the one in use. */
export const themeCommand = defineCommand({
  id: "theme",
  use() {
    const current = useTheme();

    return {
      label: fbs(
        "Switch theme",
        "Command menu: switches between the light, dark and system color theme",
      ),
      icon: ContrastLine,
      keywords: [
        fbs(
          "appearance",
          "Command menu: search keyword for the command that switches the color theme",
        ),
        fbs(
          "dark mode",
          "Command menu: search keyword for the command that switches the color theme",
        ),
        fbs(
          "light mode",
          "Command menu: search keyword for the command that switches the color theme",
        ),
      ],
      placeholder: fbs(
        "Search themes",
        "Command menu: search field placeholder in the list of color themes",
      ),
      items: Object.values(Theme).map((theme) => ({
        id: theme,
        label: themeLabel(theme),
        checked: theme === current,
        run: () => setTheme(theme),
      })),
    };
  },
});
