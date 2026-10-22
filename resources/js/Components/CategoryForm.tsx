import { Field as BaseField } from "@base-ui/react/field";
import { useFormContext } from "@inertiajs/react";
import { Checkbox, Error, Field, Input, Stack } from "@larasell-dev/ui";
import { useEffect, useRef, useState } from "react";

type Category = Pick<Larasell.Admin.Data.ViewModel.CategoryDetailData, "name" | "slug" | "visible">;

/**
 * The fields of a category. It must sit inside an Inertia `<Form>`, which submits them
 * and provides their errors; without a category it starts empty and visible.
 */
export default function CategoryForm({ category }: { category?: Category }) {
  const errors = useFormContext()?.errors ?? {};

  return (
    <Stack spacing={5}>
      {/* Side by side when the form is wide enough, stacked otherwise. It queries the form's
          own width, since the sidebars decide how much of the viewport it gets. */}
      <div className="@container">
        <div className="grid items-start gap-5 @md:grid-cols-2">
          <Field label="Name" name="name" error={errors.name}>
            <Input defaultValue={category?.name} autoComplete="off" />
          </Field>

          <Field label="Slug" name="slug" error={errors.slug}>
            <Input defaultValue={category?.slug} autoComplete="off" spellCheck={false} />
          </Field>
        </div>
      </div>

      <BaseField.Root name="status" invalid={!!errors.status} className="flex flex-col gap-1.5">
        <Checkbox.Label>
          <VisibleCheckbox defaultChecked={category?.visible ?? true} />
          Visible in the store
        </Checkbox.Label>
        {errors.status && <Error match>{errors.status}</Error>}
      </BaseField.Root>
    </Stack>
  );
}

/**
 * Submits the category's status either way. The form resets its hidden input directly,
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
