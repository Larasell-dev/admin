import { ContrastLine } from "@larasell-dev/ui/icons";
import { setTheme, Theme, themeLabels, useTheme } from "../../utils/theme";
import { defineCommand } from "./commands";

/** Switches between the light, dark and system theme, with a check on the one in use. */
export const themeCommand = defineCommand({
  id: "theme",
  use() {
    const current = useTheme();

    return {
      label: "Switch theme",
      icon: ContrastLine,
      keywords: ["appearance", "dark mode", "light mode"],
      placeholder: "Search themes",
      items: Object.values(Theme).map((theme) => ({
        id: theme,
        label: themeLabels[theme],
        checked: theme === current,
        run: () => setTheme(theme),
      })),
    };
  },
});
