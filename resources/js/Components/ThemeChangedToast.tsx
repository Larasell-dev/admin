import { Inset, useToast } from "@larasell-dev/ui";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { useEffect, useRef, useState } from "react";
import { themeLabels, useTheme } from "../utils/theme";

/**
 * Shows "Changed theme to …" whenever the theme changes, from the profile page or the hotkey.
 * Further changes update the toast in place, without a bump, and restart its timeout.
 */
export function ThemeChangedToasts() {
  const toast = useToast();
  const theme = useTheme();
  const previous = useRef(theme);

  useEffect(() => {
    if (previous.current === theme) return;
    previous.current = theme;
    toast.add({ id: "theme-changed", bump: false, children: <ThemeChangedToast /> });
  }, [theme, toast]);

  return null;
}

/** Follows the theme itself, so the word swaps as soon as it changes. */
function ThemeChangedToast() {
  const label = themeLabels[useTheme()];
  const reduceMotion = useReducedMotion();
  // Each change gets its own key, so the word always spawns a new element that rises in, even
  // when it comes back while its previous element is still on its way out.
  const [shown, setShown] = useState({ label, change: 0 });
  if (shown.label !== label) setShown({ label, change: shown.change + 1 });

  return (
    <Inset>
      <p className="flex type-body-md whitespace-nowrap text-mauve-950 dark:text-mauve-50">
        Changed theme to&nbsp;
        <span className="relative inline-flex overflow-y-clip">
          <AnimatePresence mode="popLayout" initial={false}>
            <motion.span
              key={shown.change}
              initial={{ y: "100%", opacity: 0 }}
              animate={{ y: 0, opacity: 1 }}
              exit={{ y: "-100%", opacity: 0 }}
              transition={
                reduceMotion
                  ? { duration: 0 }
                  : { type: "spring", bounce: 0.1, visualDuration: 0.3 }
              }
            >
              {shown.label}
            </motion.span>
          </AnimatePresence>
        </span>
      </p>
    </Inset>
  );
}
