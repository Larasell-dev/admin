import { useFormContext } from "@inertiajs/react";
import { Field, Input, Stack } from "@larasell-dev/ui";
import { slugify } from "../utils/slugify";
import { truncate } from "../utils/truncate";
import { useCoupledField } from "../utils/useCoupledField";
import VisibilityField from "./VisibilityField";

type Product = Pick<Larasell.Admin.Data.ViewModel.ProductDetailData, "name" | "slug" | "visible">;

/**
 * The general fields of a product. It must sit inside an Inertia `<Form>`, which submits them
 * and provides their errors; without a product it starts empty and visible.
 */
export default function ProductForm({ product }: { product?: Product }) {
  const errors = useFormContext()?.errors ?? {};
  // A new product's slug follows its name; an existing one keeps the slug its links use.
  const slug = useCoupledField([slugify, truncate(255), slugify], { enabled: !product });

  return (
    <Stack spacing={5}>
      {/* Side by side when the form is wide enough, stacked otherwise. */}
      <div className="@container">
        <div className="grid items-start gap-5 @md:grid-cols-2">
          <Field label="Name" name="name" error={errors.name}>
            <Input {...slug.source} defaultValue={product?.name} autoComplete="off" />
          </Field>

          <Field label="Slug" name="slug" error={errors.slug}>
            <Input
              {...slug.target}
              defaultValue={product?.slug}
              autoComplete="off"
              spellCheck={false}
            />
          </Field>
        </div>
      </div>

      <VisibilityField defaultVisible={product?.visible ?? true} error={errors.status} />
    </Stack>
  );
}
