import {
  defineAppHotkeys,
  HotkeysProvider,
  useAppHotkeys,
  useHotkey,
} from "@tanstack/react-hotkeys";
import type { ReactNode } from "react";
import { cycleTheme } from "./theme";

/** Every app hotkey. */
export const appHotkeys = defineAppHotkeys({
  "theme.cycle": {
    // Meta, not Mod: Mod is Control on Windows and Linux, which would leave plain Ctrl+A.
    hotkey: "Control+Meta+A",
    meta: { name: "Cycle theme", description: "Switch between light, dark and system." },
  },
});

declare module "@tanstack/react-hotkeys" {
  interface Register {
    appHotkeys: typeof appHotkeys;
  }
}

/** Provides the app hotkeys and registers the global ones. */
export function AppHotkeysProvider({ children }: { children: ReactNode }) {
  return (
    <HotkeysProvider appHotkeys={appHotkeys}>
      <GlobalHotkeys />
      {children}
    </HotkeysProvider>
  );
}

function GlobalHotkeys() {
  useHotkey(useAppHotkeys()["theme.cycle"], cycleTheme);
  return null;
}
