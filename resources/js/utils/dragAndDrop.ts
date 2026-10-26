import { defaultPreset, Feedback, PointerActivationConstraints } from "@dnd-kit/dom";
import { PointerSensor } from "@dnd-kit/react";

// A mouse drags once it moved a little, so a click still follows a link or opens a menu. Touch
// waits for a long press, so a swipe still scrolls.
export const sensors = [
  PointerSensor.configure({
    activationConstraints: (event) =>
      event.pointerType === "mouse"
        ? [new PointerActivationConstraints.Distance({ value: 5 })]
        : [new PointerActivationConstraints.Delay({ value: 250, tolerance: 5 })],
  }),
];

// A dropped item jumps straight to its new place instead of animating there.
export const plugins = (defaults: typeof defaultPreset.plugins) =>
  defaults.map((plugin) =>
    plugin === Feedback ? Feedback.configure({ dropAnimation: null }) : plugin,
  );
