import { Field as BaseField } from "@base-ui/react/field";
import { useFormContext } from "@inertiajs/react";
import { Checkbox, Field, Input, Select, Stack } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import FormCheckbox from "../../Platform/Panel/Components/FormCheckbox";
import { slugify } from "../../Platform/Support/slugify";
import { truncate } from "../../Platform/Support/truncate";
import { useCoupledField } from "../../Platform/Support/useCoupledField";

type Attribute = Larasell.Admin.Product.Data.AttributeDetailData;
type Scope = Larasell.Admin.Product.Data.AttributeScopeOptionData;
export type AttributeType = Attribute["type"];

/** The types an attribute can have, with their labels. A function, so it runs once translations load. */
export function attributeTypes(): { value: AttributeType; label: string }[] {
  return [
    {
      value: "text",
      label: fbs(
        "Text",
        "Attribute form: type of an attribute whose values are words, e.g. Cotton",
      ),
    },
    {
      value: "number",
      label: fbs(
        "Number",
        "Attribute form: type of an attribute whose values are numbers, e.g. 180",
      ),
    },
    {
      value: "boolean",
      label: fbs(
        "Yes or no",
        "Attribute form: type of an attribute that is either yes or no, e.g. Waterproof",
      ),
    },
  ];
}

/**
 * The fields of an attribute. It must sit inside an Inertia `<Form>`. A new attribute's slug
 * follows its name until the admin edits it; an existing one's slug can change, with a warning,
 * since storefront filter links use it.
 */
export default function AttributeForm({
  attribute,
  scopes,
  type,
  onTypeChange,
}: {
  attribute?: Attribute;
  scopes: Scope[];
  /** A new attribute's type, picked here; an existing one's can't change. */
  type?: AttributeType;
  onTypeChange?: (type: AttributeType) => void;
}) {
  const errors = useFormContext()?.errors ?? {};
  const slug = useCoupledField([slugify, truncate(64), slugify], { enabled: !attribute });

  return (
    <Stack spacing={5}>
      <div className="@container">
        <div className="grid items-start gap-5 @md:grid-cols-2">
          <Field
            label={fbs("Name", "Attribute form: label of the field for the attribute's name")}
            name="name"
            error={errors.name}
          >
            <Input
              {...slug.source}
              defaultValue={attribute?.name ?? undefined}
              // Left empty, the store shows another language's name, as the placeholder does.
              placeholder={attribute?.fallbackName ?? undefined}
              autoComplete="off"
            />
          </Field>
          <Field
            label={fbs(
              "Slug",
              "Attribute form: label of the field for the attribute's slug, its key in URLs",
            )}
            name="slug"
            error={errors.slug}
            description={
              attribute
                ? fbs(
                    "Storefront filter links use it. Changing it breaks links that use the old one.",
                    "Attribute form: hint below the slug of an existing attribute",
                  )
                : fbs(
                    "Used in storefront filter links. Lowercase letters and numbers, joined by - or _.",
                    "Attribute form: hint below the slug of a new attribute",
                  )
            }
          >
            <Input
              {...slug.target}
              defaultValue={attribute?.slug}
              autoComplete="off"
              spellCheck={false}
            />
          </Field>
        </div>
      </div>

      <Field
        label={fbs("Type", "Attribute form: label of the attribute's type, e.g. Number")}
        name="type"
        error={errors.type}
        description={
          attribute
            ? fbs(
                "It can't change, since products picked its values.",
                "Attribute form: hint below the read-only type of an existing attribute",
              )
            : fbs(
                "It can't change later.",
                "Attribute form: hint below the type select of a new attribute",
              )
        }
      >
        {attribute ? (
          <Input
            value={attributeTypes().find((option) => option.value === attribute.type)?.label}
            readOnly
          />
        ) : (
          <Select
            items={attributeTypes()}
            itemToKey={(option) => option.value}
            itemToLabel={(option) => option.label}
            value={type ?? "text"}
            onValueChange={(value) => value && onTypeChange?.(value as AttributeType)}
          />
        )}
      </Field>

      <Field
        label={fbs(
          "Describes",
          "Attribute form: label of the select for whether the attribute describes products, variants or both",
        )}
        name="scope"
        error={errors.scope}
      >
        <Select
          items={scopes}
          itemToKey={(scope) => scope.value}
          itemToLabel={(scope) => scope.label}
          defaultValue={attribute?.scope ?? "product"}
        />
      </Field>

      <BaseField.Root name="multiple" className="relative flex flex-col gap-1.5">
        <Checkbox.Label>
          <FormCheckbox
            name="multiple"
            value="1"
            uncheckedValue="0"
            defaultChecked={attribute?.multiple ?? false}
          />
          <fbt desc="Attribute form: checkbox that lets products and variants have several values of the attribute, e.g. a blend of materials">
            Several values per product or variant
          </fbt>
        </Checkbox.Label>
        {errors.multiple && (
          <p role="alert" className="type-body-sm text-intent-negative">
            {errors.multiple}
          </p>
        )}
      </BaseField.Root>
    </Stack>
  );
}
