import {
  defineAppHotkeys,
  HotkeysProvider,
  useAppHotkeys,
  useHotkey,
} from "@tanstack/react-hotkeys";
import type { ReactNode } from "react";
import { cycleTheme } from "./theme";

/** Scope for hotkeys that keep working while a dialog, menu or other overlay is open. */
const alwaysScope = "always";

/**
 * For overlays: suspends the page's hotkeys while open, but keeps the always-on ones.
 * Pass as `exclusive` to the overlay's scoped `HotkeysProvider`.
 */
export const overlayExclusive = { allow: [alwaysScope] };

/** Every app hotkey. Give one `scope: alwaysScope` to keep it working inside overlays. */
export const appHotkeys = defineAppHotkeys({
  "theme.cycle": {
    scope: alwaysScope,
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
      {/* Mounted for the app's lifetime, so the always-on scope stays activated, which an
          overlay's `allow` requires. */}
      <HotkeysProvider scope={alwaysScope}>
        <GlobalHotkeys />
      </HotkeysProvider>
      {children}
    </HotkeysProvider>
  );
}

function GlobalHotkeys() {
  useHotkey(useAppHotkeys()["theme.cycle"], cycleTheme);
  return null;
}
