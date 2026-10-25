import { Field as BaseField } from "@base-ui/react/field";
import { Checkbox, Error } from "@larasell-dev/ui";
import { useEffect, useRef, useState } from "react";

/** Whether a category or product shows in the store, submitted as its `status`. */
export default function VisibilityField({
  defaultVisible,
  error,
}: {
  defaultVisible: boolean;
  error?: string;
}) {
  return (
    <BaseField.Root name="status" invalid={!!error} className="flex flex-col gap-1.5">
      <Checkbox.Label>
        <VisibleCheckbox defaultChecked={defaultVisible} />
        Visible in the store
      </Checkbox.Label>
      {error && <Error match>{error}</Error>}
    </BaseField.Root>
  );
}

/**
 * Submits the record's status, "visible" or "hidden", either way. The form resets its hidden input directly,
 * which the checkbox doesn't notice, so on reset it reads the input back.
 */
function VisibleCheckbox({ defaultChecked }: { defaultChecked: boolean }) {
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
      name="status"
      value="visible"
      uncheckedValue="hidden"
      checked={checked}
      onCheckedChange={setChecked}
      inputRef={input}
    />
  );
}
