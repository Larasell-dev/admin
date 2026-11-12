import { Field as BaseField } from "@base-ui/react/field";
import { Form, router } from "@inertiajs/react";
import {
  Button,
  Error,
  Icon,
  Select,
  InheritableLabel,
  Input,
  Section,
  SectionStack,
  Stack,
} from "@larasell-dev/ui";
import { ArrowDownSLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import { type ReactNode, useRef, useState } from "react";
import ContentLocaleSelect from "../../../Platform/ContentLocale/Components/ContentLocaleSelect";
import ConfirmDialog from "../../../Platform/Panel/Components/ConfirmDialog";
import { Page } from "../../../Platform/Panel/Components/Page";
import UnsavedChangesToast from "../../../Platform/Panel/Components/UnsavedChangesToast";
import VisibilityField from "../../../Platform/Panel/Components/VisibilityField";
import SidebarLayout from "../../../Platform/Panel/Layouts/SidebarLayout";
import RichTextEditor from "../../../Platform/RichText/RichTextEditor";
import type { SharedProps } from "../../../types";
import {
  AttributeValuesPicker,
  axes,
  IdentifierFields,
  LENGTH_UNITS,
  countryFilter,
  withNone,
  UnitSelect,
  WEIGHT_UNITS,
} from "../../Components/ProductForm";

type Props = Larasell.Admin.Product.Data.EditVariantPageData & Pick<SharedProps, "contentLocale">;
type Errors = Record<string, string | undefined>;

/**
 * A field a variant can take from its product. Linked, it shows the product's value and submits
 * `{name}[state]=inherited`; unlinked, the variant's own value, which may be empty.
 */
function InheritableField({
  name,
  label,
  inherited,
  onInheritedChange,
  description,
  error,
  children,
}: {
  name: string;
  label: string;
  description?: string;
  inherited: boolean;
  onInheritedChange: (inherited: boolean) => void;
  error?: string;
  /**
   * The inputs; read-only while linked, so they show the product's value and stay focusable for
   * screen readers. The server ignores their values while the state is `inherited`.
   */
  children: (readOnly: boolean) => ReactNode;
}) {
  return (
    <BaseField.Root invalid={!!error} className="flex w-full flex-col items-start gap-1.5">
      <InheritableLabel
        inherited={inherited}
        onInheritedChange={onInheritedChange}
        linkLabel={fbs(
          "Use the product's value",
          "Variant page: chain button that makes a field take the product's value again",
        )}
        unlinkLabel={fbs(
          "Use an own value",
          "Variant page: chain button that gives the variant its own value of a field",
        )}
      >
        {label}
      </InheritableLabel>
      {description && (
        <BaseField.Description className="type-body-sm text-base-secondary">
          {description}
        </BaseField.Description>
      )}
      <input type="hidden" name={`${name}[state]`} value={inherited ? "inherited" : "overridden"} />
      <div className="w-full">{children(inherited)}</div>
      {error && <Error match>{error}</Error>}
    </BaseField.Root>
  );
}

/** Length, width and height in one shared unit, laid out like the product form's. */
function DimensionsInputs({
  readOnly,
  value,
}: {
  readOnly: boolean;
  value: Props["fields"]["dimensions"]["inherited"];
}) {
  const units = useRef<(HTMLSelectElement | null)[]>([]);
  return (
    // The same gap as between a field's label and its input on the product form.
    <div className="@container pt-3">
      <div className="grid items-start gap-5 @min-[40rem]:grid-cols-2">
        {axes().map(({ axis, label, unitLabel }, index) => (
          <label key={axis} className="flex flex-col gap-1.5">
            <span className="type-label-md text-base-primary">{label}</span>
            <Input
              name={`dimensions[${axis}]`}
              readOnly={readOnly}
              defaultValue={value?.[axis] ?? ""}
              inputMode="decimal"
              autoComplete="off"
              suffix={
                <UnitSelect
                  ref={(select) => {
                    units.current[index] = select;
                  }}
                  onChange={(event) => {
                    for (const select of units.current) {
                      if (select) select.value = event.currentTarget.value;
                    }
                  }}
                  name={index === 0 ? "dimensions[unit]" : undefined}
                  readOnly={readOnly}
                  label={unitLabel}
                  units={LENGTH_UNITS}
                  defaultValue={value?.unit ?? "cm"}
                />
              }
            />
          </label>
        ))}
      </div>
    </div>
  );
}

export default function Edit(props: Props) {
  const { fields, attributes, contentLocale } = props;
  const [linked, setLinked] = useState<Record<string, boolean>>({
    ...Object.fromEntries(
      Object.entries(fields).map(([key, field]) => [key, field.state === "inherited"]),
    ),
    ...Object.fromEntries(attributes.map((a) => [`attribute-${a.id}`, a.state === "inherited"])),
  });
  const link = (key: string) => (value: boolean) => setLinked({ ...linked, [key]: value });
  const w = fields.weight;
  const d = fields.dimensions;

  return (
    <>
      <Form
        key={`${props.updateUrl}-${contentLocale?.current}`}
        action={props.updateUrl}
        method="put"
        setDefaultsOnSuccess
        className="contents"
      >
        {({ errors }: { errors: Errors }) => (
          <>
            <Page.Header
              title={props.title}
              backLink={{
                href: props.productUrl,
                label: fbs(
                  "Back to the product",
                  "Variant page: accessible name of the back arrow to the product",
                ),
              }}
              actions={
                <div className="flex items-center gap-2">
                  <div className="w-48">
                    <Select.Root
                      // The product itself first, so it's one pick away like its variants.
                      items={[
                        { id: 0, name: props.productName, url: props.productUrl },
                        ...props.siblings,
                      ]}
                      itemToKey={(sibling) => String(sibling.id)}
                      itemToLabel={(sibling) => sibling.name}
                      value={String(props.id)}
                      onValueChange={(key) => {
                        if (key === "0") return router.visit(props.productUrl);
                        const sibling = props.siblings.find((s) => String(s.id) === key);
                        if (sibling && sibling.id !== props.id) router.visit(sibling.url);
                      }}
                      filter={{
                        placeholder: fbs(
                          "Search variants",
                          "Variant page: placeholder of the search field in the variant select's list",
                        ),
                        empty: (
                          <fbt desc="Variant page: shown in the variant select's list when the search matches no variant">
                            No variants found
                          </fbt>
                        ),
                      }}
                    >
                      <Select.Trigger
                        render={
                          <Button
                            variant="secondary"
                            fullWidth
                            DANGEROUS_className="justify-between"
                          />
                        }
                        aria-label={fbs(
                          "Variant",
                          "Variant page: accessible name of the select that opens another variant of the product",
                        )}
                      >
                        {(selected: Props["siblings"]) => (
                          <span className="min-w-0 truncate">{selected[0]?.name}</span>
                        )}
                        <Icon icon={ArrowDownSLine} />
                      </Select.Trigger>
                      <Select.Popup />
                    </Select.Root>
                  </div>
                  <ContentLocaleSelect contentLocale={contentLocale} />
                </div>
              }
            />
            <Page.Body>
              <SectionStack>
                <Section.Root>
                  <Section.Header
                    title={fbs(
                      "General",
                      "Variant page: heading of the name, description and visibility",
                    )}
                    description={fbs(
                      "How the variant is named and found in the store.",
                      "Variant page: text below the heading of the General section, with the name, description and visibility",
                    )}
                  />
                  <Stack spacing={5}>
                    <InheritableField
                      name="name"
                      label={fbs("Name", "Variant page: label of the variant's name")}
                      inherited={linked.name}
                      onInheritedChange={link("name")}
                      error={errors["name.value"]}
                    >
                      {(readOnly) => (
                        <Input
                          key={String(readOnly)}
                          name="name[value]"
                          readOnly={readOnly}
                          defaultValue={
                            readOnly
                              ? fields.name.inherited
                              : (fields.name.value ?? fields.name.inherited)
                          }
                        />
                      )}
                    </InheritableField>
                    <InheritableField
                      name="description"
                      label={fbs("Description", "Variant page: label of the variant's description")}
                      inherited={linked.description}
                      onInheritedChange={link("description")}
                      error={errors["description.value"]}
                    >
                      {(readOnly) =>
                        readOnly ? (
                          <div>
                            <RichTextEditor
                              key="inherited"
                              readOnly
                              name="description_preview"
                              label={fbs(
                                "Product description",
                                "Variant page: accessible name of the product's description shown while inherited",
                              )}
                              defaultValue={fields.description.inherited ?? null}
                            />
                          </div>
                        ) : (
                          <RichTextEditor
                            key="own"
                            name="description[value]"
                            label={fbs(
                              "Description",
                              "Variant page: accessible name of the variant's own description",
                            )}
                            defaultValue={
                              fields.description.value ?? fields.description.inherited ?? null
                            }
                          />
                        )
                      }
                    </InheritableField>
                    <VisibilityField defaultVisible={props.visible} error={errors.status} />
                  </Stack>
                </Section.Root>

                <Section.Root>
                  <Section.Header
                    title={fbs(
                      "Weight and dimensions",
                      "Variant page: heading of the weight and dimensions",
                    )}
                    description={fbs(
                      "Used to choose shipping methods and their prices. Leave them empty for variants that aren't shipped.",
                      "Variant page: text below the heading of the Weight and dimensions section",
                    )}
                  />
                  <Stack spacing={12}>
                    <div className="@container">
                      <div className="grid items-start gap-5 @min-[40rem]:grid-cols-2">
                        <InheritableField
                          name="weight"
                          label={fbs("Weight", "Variant page: label of the weight")}
                          inherited={linked.weight}
                          onInheritedChange={link("weight")}
                          error={errors["weight.amount"] ?? errors["weight.unit"]}
                        >
                          {(readOnly) => {
                            const value = readOnly
                              ? w.inherited
                              : (w.value ?? (w.state === "inherited" ? w.inherited : null));
                            return (
                              <Input
                                key={String(readOnly)}
                                name="weight[amount]"
                                readOnly={readOnly}
                                defaultValue={value?.amount ?? ""}
                                inputMode="decimal"
                                autoComplete="off"
                                suffix={
                                  <UnitSelect
                                    name="weight[unit]"
                                    readOnly={readOnly}
                                    label={fbs(
                                      "Weight unit",
                                      "Variant page: accessible name of the select next to the weight field that picks the unit, e.g. kg",
                                    )}
                                    units={WEIGHT_UNITS}
                                    defaultValue={value?.unit ?? "kg"}
                                  />
                                }
                              />
                            );
                          }}
                        </InheritableField>
                      </div>
                    </div>
                    <InheritableField
                      name="dimensions"
                      label={fbs(
                        "Dimensions",
                        "Variant page: label of the length, width and height",
                      )}
                      description={fbs(
                        "The packed size. All three share one unit.",
                        "Variant page: text below the label of the length, width and height",
                      )}
                      inherited={linked.dimensions}
                      onInheritedChange={link("dimensions")}
                      error={
                        errors["dimensions.length"] ??
                        errors["dimensions.width"] ??
                        errors["dimensions.height"] ??
                        errors["dimensions.unit"]
                      }
                    >
                      {(readOnly) => {
                        const value = readOnly
                          ? d.inherited
                          : (d.value ?? (d.state === "inherited" ? d.inherited : null));
                        return (
                          <DimensionsInputs
                            key={String(readOnly)}
                            readOnly={readOnly}
                            value={value}
                          />
                        );
                      }}
                    </InheritableField>
                  </Stack>
                </Section.Root>

                <Section.Root>
                  <Section.Header
                    title={fbs(
                      "Identifiers",
                      "Variant page: heading of the SKU, barcode and manufacturer part number",
                    )}
                    description={fbs(
                      "Identify the variant in your inventory and when it's scanned. Each belongs to one variant only.",
                      "Variant page: text below the heading of the Identifiers section",
                    )}
                  />
                  <IdentifierFields product={props} errors={errors} />
                </Section.Root>

                <Section.Root>
                  <Section.Header
                    title={fbs(
                      "Customs",
                      "Variant page: heading of the country of origin and HS code",
                    )}
                    description={fbs(
                      "What customs need to know when the variant is shipped abroad.",
                      "Variant page: text below the heading of the Customs section",
                    )}
                  />
                  <Stack spacing={5}>
                    <InheritableField
                      name="country_of_origin"
                      label={fbs(
                        "Country of origin",
                        "Variant page: label of the country the goods were made in",
                      )}
                      inherited={linked.country_of_origin}
                      onInheritedChange={link("country_of_origin")}
                      error={errors["country_of_origin.value"]}
                    >
                      {(readOnly) => (
                        <Select
                          key={String(readOnly)}
                          name="country_of_origin[value]"
                          readOnly={readOnly}
                          items={[{ code: "", label: "—" }, ...props.countries]}
                          itemToKey={(c) => c.code}
                          itemToLabel={(c) => c.label}
                          filter={countryFilter()}
                          defaultValue={
                            (readOnly || fields.country_of_origin.state === "inherited"
                              ? fields.country_of_origin.inherited
                              : fields.country_of_origin.value) ?? ""
                          }
                        />
                      )}
                    </InheritableField>
                    <InheritableField
                      name="hs_code"
                      label={fbs("HS code", "Variant page: label of the customs tariff number")}
                      inherited={linked.hs_code}
                      onInheritedChange={link("hs_code")}
                      error={errors["hs_code.value"]}
                    >
                      {(readOnly) => (
                        <Input
                          key={String(readOnly)}
                          name="hs_code[value]"
                          readOnly={readOnly}
                          defaultValue={
                            (readOnly ? fields.hs_code.inherited : fields.hs_code.value) ?? ""
                          }
                        />
                      )}
                    </InheritableField>
                  </Stack>
                </Section.Root>

                {attributes.length > 0 && (
                  <Section.Root>
                    <Section.Header
                      title={fbs("Attributes", "Variant page: heading of the variant's attributes")}
                      description={fbs(
                        "Details customers can filter and compare by, such as material. Link one to use the product's value.",
                        "Variant page: text below the heading of the Attributes section",
                      )}
                    />
                    <Stack spacing={5}>
                      {attributes.map((attribute) => {
                        const key = `attribute-${attribute.id}`;
                        const linkedNow = attribute.inheritable && linked[key];
                        return (
                          <InheritableField
                            key={attribute.id}
                            name={`attributes[${attribute.id}]`}
                            label={attribute.name}
                            inherited={linkedNow}
                            onInheritedChange={attribute.inheritable ? link(key) : () => {}}
                          >
                            {(readOnly) =>
                              attribute.multiple ? (
                                <AttributeValuesPicker
                                  key={String(readOnly)}
                                  name={`attributes[${attribute.id}][value]`}
                                  label={attribute.name}
                                  readOnly={readOnly}
                                  values={attribute.values}
                                  defaultValue={
                                    readOnly || attribute.state === "inherited"
                                      ? attribute.inherited
                                      : attribute.value
                                  }
                                />
                              ) : (
                                <Select
                                  key={String(readOnly)}
                                  name={`attributes[${attribute.id}][value]`}
                                  readOnly={readOnly}
                                  items={withNone(attribute.values)}
                                  itemToKey={(v) => String(v.id)}
                                  itemToLabel={(v) => v.name}
                                  defaultValue={String(
                                    (readOnly || attribute.state === "inherited"
                                      ? attribute.inherited
                                      : attribute.value)[0] ?? "",
                                  )}
                                />
                              )
                            }
                          </InheritableField>
                        );
                      })}
                    </Stack>
                  </Section.Root>
                )}
                {props.deleteUrl && (
                  <div className="flex">
                    <ConfirmDialog.Root>
                      <ConfirmDialog.Trigger render={<Button variant="destructive" />}>
                        <fbt desc="Variant page: button that opens the confirmation to delete the variant">
                          Delete variant
                        </fbt>
                      </ConfirmDialog.Trigger>
                      <ConfirmDialog.Popup
                        title={fbs(
                          "Delete variant",
                          "Variant page: title of the confirmation to delete the variant",
                        )}
                        description={fbs(
                          "Carts and orders keep their copies, but the variant can't be bought anymore.",
                          "Variant page: text of the confirmation to delete the variant",
                        )}
                        action={props.deleteUrl}
                      />
                    </ConfirmDialog.Root>
                  </div>
                )}
              </SectionStack>
              <UnsavedChangesToast />
            </Page.Body>
          </>
        )}
      </Form>
    </>
  );
}

Edit.layout = [SidebarLayout];
