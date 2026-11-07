import { useFormContext } from "@inertiajs/react";
import { Field, Input, Stack } from "@larasell-dev/ui";
import VisibilityField from "../VisibilityField";
import { fbs } from "fbtee";

type Category = Pick<
  Larasell.Admin.Category.Data.CategoryDetailData,
  "name" | "fallbackName" | "visible"
>;

/**
 * The fields of a category. It must sit inside an Inertia `<Form>`, which submits them
 * and provides their errors; without a category it starts empty and visible.
 */
export default function CategoryForm({ category }: { category?: Category }) {
  const errors = useFormContext()?.errors ?? {};
  return (
    <Stack spacing={5}>
      <Field
        label={fbs("Name", "Category form: label of the field for the category's name")}
        name="name"
        error={errors.name}
      >
        {/* Untranslated fields stay empty and show what the store shows instead. */}
        <Input
          defaultValue={category?.name ?? undefined}
          placeholder={category?.name === null ? category.fallbackName : undefined}
          autoComplete="off"
        />
      </Field>

      <VisibilityField defaultVisible={category?.visible ?? true} error={errors.status} />
    </Stack>
  );
}
