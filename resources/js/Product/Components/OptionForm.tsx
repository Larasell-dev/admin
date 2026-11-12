import { useFormContext } from "@inertiajs/react";
import { Field, Input, Select, Stack } from "@larasell-dev/ui";
import { useEffect, useRef } from "react";
import { fbs } from "fbtee";
import { slugify } from "../../Platform/Support/slugify";
import { truncate } from "../../Platform/Support/truncate";
import { useCoupledField } from "../../Platform/Support/useCoupledField";

type Option = Larasell.Admin.Product.Data.OptionDetailData;
type Display = Larasell.Admin.Product.Data.OptionDisplayOptionData;
export type OptionDisplay = Option["display"];

/**
 * The fields of an option. It must sit inside an Inertia `<Form>`. A new option's slug follows
 * its name until the admin edits it.
 */
export default function OptionForm({
  option,
  displays,
  display,
  onDisplayChange,
}: {
  option?: Option;
  displays: Display[];
  /** Picked here; the page shows swatches only while it's "swatch". */
  display: OptionDisplay;
  onDisplayChange: (display: OptionDisplay) => void;
}) {
  const root = useRef<HTMLDivElement>(null);
  // Discarding the form's changes goes back to the saved display.
  useEffect(() => {
    const form = root.current?.closest("form");
    const reset = () => onDisplayChange(option?.display ?? "dropdown");
    form?.addEventListener("reset", reset);
    return () => form?.removeEventListener("reset", reset);
  }, [option, onDisplayChange]);
  const errors = useFormContext()?.errors ?? {};
  const slug = useCoupledField([slugify, truncate(64), slugify], { enabled: !option });

  return (
    <div ref={root}>
      <Stack spacing={5}>
        <div className="@container">
          <div className="grid items-start gap-5 @md:grid-cols-2">
            <Field
              label={fbs("Name", "Option form: label of the field for the option's name")}
              name="name"
              error={errors.name}
            >
              <Input
                {...slug.source}
                defaultValue={option?.name ?? undefined}
                // Left empty, the store shows another language's name, as the placeholder does.
                placeholder={option?.fallbackName ?? undefined}
                autoComplete="off"
              />
            </Field>
            <Field
              label={fbs(
                "Slug",
                "Option form: label of the field for the option's slug, its key in URLs",
              )}
              name="slug"
              error={errors.slug}
              description={fbs(
                "Lowercase letters and numbers, joined by - or _.",
                "Option form: hint below the option's slug",
              )}
            >
              <Input
                {...slug.target}
                defaultValue={option?.slug}
                autoComplete="off"
                spellCheck={false}
              />
            </Field>
          </div>
        </div>
        <Field
          label={fbs("Display", "Option form: label of how the store shows the option's values")}
          name="display"
          error={errors.display}
          description={fbs(
            "Swatches show each value's colour.",
            "Option form: hint below the display select",
          )}
        >
          <Select
            items={displays}
            itemToKey={(display) => display.value}
            itemToLabel={(display) => display.label}
            value={display}
            onValueChange={(value) => value && onDisplayChange(value as OptionDisplay)}
          />
        </Field>
      </Stack>
    </div>
  );
}
