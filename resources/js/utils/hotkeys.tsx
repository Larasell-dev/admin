import {
  defineAppHotkeys,
  HotkeysProvider,
  useAppHotkeys,
  useHotkey,
} from "@tanstack/react-hotkeys";
import type { CustomHotkeys } from "@tanstack/react-hotkeys";
import type { ReactNode } from "react";
import { cycleTheme } from "./theme";

/** Every app hotkey. Admins can rebind them; their choices are saved on this device. */
export const appHotkeys = defineAppHotkeys({
  "theme.cycle": {
    hotkey: "Mod+Shift+L",
    meta: { name: "Cycle theme", description: "Switch between light, dark and system." },
  },
});

declare module "@tanstack/react-hotkeys" {
  interface Register {
    appHotkeys: typeof appHotkeys;
  }
}

const customHotkeysStorageKey = "larasell-admin-hotkeys";

function storedCustomHotkeys(): CustomHotkeys | null {
  try {
    return JSON.parse(localStorage.getItem(customHotkeysStorageKey) ?? "null");
  } catch {
    return null;
  }
}

/** Provides the app hotkeys with the admin's saved bindings, and registers the global ones. */
export function AppHotkeysProvider({ children }: { children: ReactNode }) {
  return (
    <HotkeysProvider
      appHotkeys={appHotkeys}
      customHotkeys={storedCustomHotkeys()}
      onCustomHotkeysChange={(custom) =>
        localStorage.setItem(customHotkeysStorageKey, JSON.stringify(custom))
      }
    >
      <GlobalHotkeys />
      {children}
    </HotkeysProvider>
  );
}

function GlobalHotkeys() {
  useHotkey(useAppHotkeys()["theme.cycle"], cycleTheme);
  return null;
}
