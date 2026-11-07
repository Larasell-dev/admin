import { animate, motion, useMotionValue, useReducedMotion } from "motion/react";
import { useLayoutEffect, useRef, type ComponentProps } from "react";

// BEST-BEFORE 2027-02-01: a copy of @larasell-dev/ui's PopupMotion, which it doesn't export.
// Import it from there once it does, and delete this file.

const DISTANCE = 4;

/** Where the popup starts and ends: a few pixels towards its trigger. */
function offset(side: string): { x: number; y: number } {
  switch (side) {
    case "bottom":
      return { x: 0, y: -DISTANCE };
    case "top":
      return { x: 0, y: DISTANCE };
    case "right":
    case "inline-end":
      return { x: -DISTANCE, y: 0 };
    case "left":
    case "inline-start":
      return { x: DISTANCE, y: 0 };
    default:
      return { x: 0, y: 0 };
  }
}

const spring = { type: "spring", bounce: 0, duration: 0.2 } as const;
const instant = { duration: 0 };

/**
 * Animates a Base UI popup in and out, like the UI library's select and tag combobox. Use it as
 * the popup's `render` inside an `<AnimatePresence>` that mounts the popup while open.
 */
export default function PopupMotion({
  side,
  ...props
}: ComponentProps<typeof motion.div> & { side: string }) {
  const reduceMotion = useReducedMotion();
  const currentSide = reduceMotion ? "none" : side;
  const transition = reduceMotion ? instant : spring;
  const hidden = offset(currentSide);
  const x = useMotionValue(hidden.x);
  const y = useMotionValue(hidden.y);
  const previousSide = useRef(currentSide);

  // When the popup flips sides mid-animation, the slide continues from the new side.
  useLayoutEffect(() => {
    const from = offset(previousSide.current);
    previousSide.current = currentSide;
    if (from.x === hidden.x && from.y === hidden.y) return;
    if (from.x) x.set((x.get() / from.x) * hidden.x);
    if (from.y) y.set((y.get() / from.y) * hidden.y);
    animate(x, 0, transition);
    animate(y, 0, transition);
  }, [currentSide, hidden.x, hidden.y, x, y, transition]);

  return (
    <motion.div
      {...props}
      style={{ ...props.style, x, y }}
      initial={{ opacity: 0 }}
      animate={{ opacity: 1, x: 0, y: 0 }}
      exit={{ opacity: 0, ...hidden }}
      transition={transition}
    />
  );
}
