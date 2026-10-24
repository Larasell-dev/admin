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
