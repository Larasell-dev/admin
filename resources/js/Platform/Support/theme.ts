import { fbs } from "fbtee";
import { useSyncExternalStore } from "react";

export enum Theme {
  Light = "light",
  Dark = "dark",
  System = "system",
}

/** The theme's name, in the interface language. */
export function themeLabel(theme: Theme): string {
  switch (theme) {
    case Theme.Light:
      return fbs("Light", "Name of the light color theme");
    case Theme.Dark:
      return fbs("Dark", "Name of the dark color theme");
    case Theme.System:
      return fbs(
        "System",
        "Name of the color theme that follows the operating system's light or dark setting",
      );
  }
}

const themeStorageKey = "larasell-admin-theme";

const listeners = new Set<() => void>();

export function storedTheme(): Theme {
  const theme = localStorage.getItem(themeStorageKey);
  return theme === Theme.Light || theme === Theme.Dark ? theme : Theme.System;
}

export function setTheme(theme: Theme) {
  if (theme === Theme.System) localStorage.removeItem(themeStorageKey);
  else localStorage.setItem(themeStorageKey, theme);

  const dark =
    theme === Theme.Dark ||
    (theme === Theme.System && window.matchMedia("(prefers-color-scheme: dark)").matches);
  document.documentElement.classList.toggle("dark", dark);
  listeners.forEach((listener) => listener());
}

/** Moves to the next theme in the order light, dark, system. */
export function cycleTheme() {
  const themes = Object.values(Theme);
  setTheme(themes[(themes.indexOf(storedTheme()) + 1) % themes.length]);
}

/** The stored theme, updated whenever `setTheme` changes it. */
export function useTheme(): Theme {
  return useSyncExternalStore((listener) => {
    listeners.add(listener);
    return () => listeners.delete(listener);
  }, storedTheme);
}
