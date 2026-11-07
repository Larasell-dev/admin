import { Checkbox } from "@larasell-dev/ui";
import { useEffect, useRef, useState } from "react";

/**
 * Submits `value` when checked and `uncheckedValue` when not, so the form always sends the field.
 * The form resets its hidden input directly, which the checkbox doesn't notice, so on reset it
 * reads the input back.
 */
export default function FormCheckbox({
  defaultChecked,
  ...props
}: {
  name: string;
  value: string;
  uncheckedValue: string;
  defaultChecked: boolean;
}) {
  const [checked, setChecked] = useState(defaultChecked);
  const input = useRef<HTMLInputElement>(null);

  useEffect(() => {
    const form = input.current?.form;
    if (!form) return;
    // Inertia dispatches the event once it has set the inputs. Read it right away: React puts
    // the old value back into the input on its next render.
    const sync = () => setChecked(input.current?.checked ?? defaultChecked);
    form.addEventListener("reset", sync);
    return () => form.removeEventListener("reset", sync);
  }, [defaultChecked]);

  return (
    <Checkbox.Indicator
      {...props}
      checked={checked}
      onCheckedChange={setChecked}
      inputRef={input}
    />
  );
}
