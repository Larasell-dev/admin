import { useFormContext } from "@inertiajs/react";
import { Field, Input, Stack } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import { slugify } from "../../Platform/Support/slugify";
import { truncate } from "../../Platform/Support/truncate";
import Textarea from "../../Platform/Panel/Components/Textarea";
import { useCoupledField } from "../../Platform/Support/useCoupledField";

type Brand = Larasell.Admin.Brand.Data.BrandDetailData;

/**
 * The fields of a brand, in the content locale. It must sit inside an Inertia `<Form>`. A new
 * brand's slug follows its name until the admin edits it.
 */
export default function BrandForm({
  brand,
  errors: own,
}: {
  brand?: Brand;
  /** Its own errors, when it isn't the surrounding form's, e.g. in the product form's slide-over. */
  errors?: Record<string, string | undefined>;
}) {
  const context: Record<string, string | undefined> = useFormContext()?.errors ?? {};
  const errors = own ?? context;
  const slug = useCoupledField([slugify, truncate(64), slugify], { enabled: !brand });

  return (
    <Stack spacing={5}>
      <div className="@container">
        <div className="grid items-start gap-5 @md:grid-cols-2">
          <Field
            label={fbs("Name", "Brand form: label of the field for the brand's name")}
            name="name"
            error={errors.name}
            // Left empty, the store shows another language's name.
            required={!brand?.fallbackName}
          >
            <Input
              {...slug.source}
              defaultValue={brand?.name ?? undefined}
              // Left empty, the store shows another language's name, as the placeholder does.
              placeholder={brand?.fallbackName ?? undefined}
              autoComplete="off"
            />
          </Field>
          <Field
            label={fbs(
              "Slug",
              "Brand form: label of the field for the brand's slug, its key in URLs",
            )}
            name="slug"
            error={errors.slug}
            required
            description={fbs(
              "Lowercase letters and numbers, joined by - or _.",
              "Brand form: hint below the brand's slug",
            )}
          >
            <Input
              {...slug.target}
              defaultValue={brand?.slug}
              autoComplete="off"
              spellCheck={false}
            />
          </Field>
        </div>
      </div>
      <Field
        label={fbs("Description", "Brand form: label of the brand's optional description")}
        name="description"
        error={errors.description}
      >
        <Textarea name="description" rows={4} defaultValue={brand?.description ?? ""} />
      </Field>
    </Stack>
  );
}
