export type Theme = "light" | "dark" | "system";

// Kept in localStorage, so each device keeps its own theme. resources/views/app.blade.php reads
// the same key before the first paint; keep the two in sync.
const storageKey = "larasell-admin-theme";

export function storedTheme(): Theme {
  const theme = localStorage.getItem(storageKey);
  return theme === "light" || theme === "dark" ? theme : "system";
}

export function setTheme(theme: Theme) {
  if (theme === "system") localStorage.removeItem(storageKey);
  else localStorage.setItem(storageKey, theme);

  const dark =
    theme === "dark" ||
    (theme === "system" && window.matchMedia("(prefers-color-scheme: dark)").matches);
  document.documentElement.classList.toggle("dark", dark);
}
