import { Inset, useToast } from "@larasell-dev/ui";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { memo, useEffect, useLayoutEffect, useRef, useState } from "react";
import { themeLabel, useTheme } from "../../../Support/theme";

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

/**
 * Never re-renders, not even when the toast is added again: fbtee gives every result a new
 * component type, so re-rendering the `<fbt>` would remount the word and drop its animation.
 * The word follows the theme instead.
 */
const ThemeChangedToast = memo(function ThemeChangedToast() {
  return (
    <Inset>
      {/* Not a flex row, which would drop the spaces around the theme, wherever a language puts it. */}
      <p className="type-body-md whitespace-nowrap text-base-primary">
        <fbt desc="Toast after the color theme changed, e.g. 'Changed theme to Dark'">
          Changed theme to{" "}
          <fbt:param name="theme">
            <ThemeWord />
          </fbt:param>
        </fbt>
      </p>
    </Inset>
  );
});

/** Follows the theme itself, so the word swaps as soon as it changes. */
function ThemeWord() {
  const label = themeLabel(useTheme());
  const reduceMotion = useReducedMotion();
  // Each change gets its own key, so the word always spawns a new element that rises in, even
  // when it comes back while its previous element is still on its way out.
  const [shown, setShown] = useState({ label, change: 0 });
  if (shown.label !== label) setShown({ label, change: shown.change + 1 });

  // The slot keeps the width of the word it shows and springs to the next word's, so the text
  // after it, wherever a language puts it, slides along instead of jumping.
  // The ref moves to each new word as it mounts, while the old one is still on its way out.
  const word = useRef<HTMLSpanElement>(null);
  const [width, setWidth] = useState<number | "auto">("auto");
  useLayoutEffect(() => {
    if (word.current) setWidth(word.current.getBoundingClientRect().width);
  }, [shown.change]);

  const transition = reduceMotion
    ? { duration: 0 }
    : { type: "spring" as const, bounce: 0.1, visualDuration: 0.3 };

  return (
    <motion.span
      className="relative inline-flex overflow-y-clip align-bottom"
      initial={false}
      animate={{ width }}
      transition={transition}
    >
      <AnimatePresence mode="popLayout" initial={false}>
        <motion.span
          key={shown.change}
          ref={word}
          className="shrink-0"
          initial={{ y: "100%", opacity: 0 }}
          animate={{ y: 0, opacity: 1 }}
          exit={{ y: "-100%", opacity: 0 }}
          transition={transition}
        >
          {shown.label}
        </motion.span>
      </AnimatePresence>
    </motion.span>
  );
}
