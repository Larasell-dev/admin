import { useSyncExternalStore } from "react";

export enum Theme {
  Light = "light",
  Dark = "dark",
  System = "system",
}

export const themeLabels: Record<Theme, string> = {
  [Theme.Light]: "Light",
  [Theme.Dark]: "Dark",
  [Theme.System]: "System",
};

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
  setTheme(nextTheme(storedTheme()));
}

export function nextTheme(theme: Theme): Theme {
  const themes = Object.values(Theme);
  return themes[(themes.indexOf(theme) + 1) % themes.length];
}

/** The stored theme, updated whenever `setTheme` changes it. */
export function useTheme(): Theme {
  return useSyncExternalStore((listener) => {
    listeners.add(listener);
    return () => listeners.delete(listener);
  }, storedTheme);
}
