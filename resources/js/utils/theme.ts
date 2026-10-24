export enum Theme {
  Light = "light",
  Dark = "dark",
  System = "system",
}

/** Labels keyed by theme, like the `HasLabel` enums on the PHP side. */
export const themeLabels: Record<Theme, string> = {
  [Theme.Light]: "Light",
  [Theme.Dark]: "Dark",
  [Theme.System]: "System",
};

/**
 * The localStorage key holding the theme, so each device keeps its own. The pre-paint script in
 * resources/views/app.blade.php can't import this and repeats the key; keep the two in sync.
 */
export const themeStorageKey = "larasell-admin-theme";

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
}
