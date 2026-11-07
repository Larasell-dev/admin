import { Field } from "@base-ui/react/field";
import { Error, Label, Select } from "@larasell-dev/ui";
import { type ComponentProps, type ReactNode, useEffect, useRef, useState } from "react";

type Item = { value: string; label: string };

/**
 * A `Select` that takes part in the surrounding form: it submits the picked items' values as
 * `name`, one hidden checkbox per item with `multiple` (so name it `something[]`), tells the form
 * when the admin picks something, so it turns dirty, and reads its inputs back when the form
 * resets. Without `multiple`, a value that is no longer offered falls back to the first item.
 * It is its own field, laid out like `Field`: in a `Field`, the select would submit under the
 * field's name as well.
 */
export default function FormSelect({
  label,
  description,
  error,
  name,
  items,
  itemToLabel = (item) => item.label,
  multiple = false,
  defaultValue,
  onValueChange,
  filter,
  disabled,
  placeholder,
  children,
}: {
  label: string;
  description?: string;
  error?: string;
  name: string;
  items: Item[];
  itemToLabel?: (item: Item) => string;
  multiple?: boolean;
  /** The picked items' values. */
  defaultValue: string[];
  onValueChange?: (value: string[]) => void;
  filter?: ComponentProps<typeof Select.Root>["filter"];
  disabled?: boolean;
  placeholder?: string;
  /** Renders each item in the list; defaults to its label. */
  children?: (item: Item) => ReactNode;
}) {
  const [value, setValue] = useState(defaultValue);
  const inputs = useRef<HTMLSpanElement>(null);
  const picked = items.filter((item) => value.includes(item.value));
  const single = picked[0] ?? items[0] ?? null;
  const submitted = multiple ? picked.map((item) => item.value) : single ? [single.value] : [];

  const change = (next: string[]) => {
    changed.current = true;
    setValue(next);
    onValueChange?.(next);
  };

  const changed = useRef(false);
  useEffect(() => {
    if (!changed.current) return;
    changed.current = false;
    inputs.current?.dispatchEvent(new Event("change", { bubbles: true }));
  }, [value]);

  // The form's reset sets the inputs directly. Read them back right away, before React's next
  // render puts the old value back into them.
  useEffect(() => {
    const form = inputs.current?.closest("form");
    if (!form) return;
    const sync = () => {
      const elements = inputs.current?.querySelectorAll("input") ?? [];
      const next = Array.from(elements)
        .filter((input) => input.type === "hidden" || input.checked)
        .map((input) => input.value);
      setValue(next);
      onValueChange?.(next);
    };
    form.addEventListener("reset", sync);
    return () => form.removeEventListener("reset", sync);
  }, [onValueChange]);

  // The trigger has no invalid look of its own; this borrows Input's.
  const trigger = (
    <Select.Trigger
      placeholder={placeholder}
      DANGEROUS_className="data-invalid:border-intent-negative"
    />
  );
  const popup = (
    <Select.Popup>
      {children &&
        ((item: Item) => (
          <Select.Item key={item.value} item={item}>
            {multiple && <Select.ItemCheckbox />}
            <span className="min-w-0 flex-1">{children(item)}</span>
          </Select.Item>
        ))}
    </Select.Popup>
  );

  return (
    <Field.Root invalid={!!error} className="flex w-full flex-col items-start gap-1.5">
      <Label>{label}</Label>
      {multiple ? (
        <Select.Root
          multiple
          items={items}
          itemToKey={(item) => item.value}
          itemToLabel={itemToLabel}
          value={picked}
          onValueChange={(next) => change(next.map((item) => item.value))}
          filter={filter}
          disabled={disabled}
        >
          {trigger}
          {popup}
        </Select.Root>
      ) : (
        <Select.Root
          items={items}
          itemToKey={(item) => item.value}
          itemToLabel={itemToLabel}
          value={single}
          onValueChange={(next) => change(next ? [next.value] : [])}
          filter={filter}
          disabled={disabled}
        >
          {trigger}
          {popup}
        </Select.Root>
      )}
      <span ref={inputs} hidden>
        {multiple ? (
          items.map((item) => (
            <input
              key={item.value}
              type="checkbox"
              name={name}
              value={item.value}
              checked={submitted.includes(item.value)}
              readOnly
            />
          ))
        ) : (
          <input type="hidden" name={name} value={submitted[0] ?? ""} />
        )}
      </span>
      {description && (
        <Field.Description className="type-caption-lg text-base-secondary">
          {description}
        </Field.Description>
      )}
      {error && <Error match>{error}</Error>}
    </Field.Root>
  );
}
