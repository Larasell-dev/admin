import { type ChangeEvent, useEffect, useRef } from "react";

/**
 * Fills the target input with the source input's value as it's typed in, passed through each
 * transform in turn. Editing the target uncouples them, until the target is cleared or the form
 * is reset. Without `enabled`, they're never coupled.
 */
export function useCoupledField(
  transforms: ((value: string) => string)[],
  { enabled }: { enabled: boolean },
) {
  const target = useRef<HTMLInputElement>(null);
  const coupled = useRef(enabled);

  useEffect(() => {
    // Inertia dispatches the event once it has set the inputs back to their defaults.
    const form = target.current?.form;
    const couple = () => {
      coupled.current = enabled;
    };
    form?.addEventListener("reset", couple);
    return () => form?.removeEventListener("reset", couple);
  }, [enabled]);

  return {
    source: {
      onChange: (event: ChangeEvent<HTMLInputElement>) => {
        if (!coupled.current || !target.current) return;
        target.current.value = transforms.reduce(
          (value, transform) => transform(value),
          event.currentTarget.value,
        );
        // The form already compared its data against the defaults before this handler ran, with
        // the old target value. Another input event has it check again. React doesn't see it as
        // a change, since it tracks the value set above, so it doesn't uncouple the target.
        target.current.dispatchEvent(new Event("input", { bubbles: true }));
      },
    },
    target: {
      ref: target,
      onChange: (event: ChangeEvent<HTMLInputElement>) => {
        coupled.current = enabled && event.currentTarget.value === "";
      },
    },
  };
}
