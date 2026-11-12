import { cn } from "cn";
import { Field as BaseField } from "@base-ui/react/field";
import { Link, useFormContext } from "@inertiajs/react";
import {
  Checkbox,
  Error,
  Field,
  Icon,
  Input,
  Section,
  SectionStack,
  Select,
  Stack,
} from "@larasell-dev/ui";
import { AddLine, ArrowDownSLine, ArrowRightSLine, CloseLine } from "@larasell-dev/ui/icons";
import {
  type ChangeEventHandler,
  Fragment,
  type ReactNode,
  type Ref,
  type RefObject,
  useEffect,
  useRef,
  useState,
} from "react";
import CategoryCheckboxTree from "../../Category/Components/CategoryCheckboxTree";
import ExtensionSections from "../../Platform/Sections/Components/ExtensionSections";
import FormCheckbox from "../../Platform/Panel/Components/FormCheckbox";
import VisibilityField from "../../Platform/Panel/Components/VisibilityField";
import RichTextEditor from "../../Platform/RichText/RichTextEditor";
import { fbs } from "fbtee";

type Product = Omit<Larasell.Admin.Product.Data.ProductDetailData, "id">;
type Category = Larasell.Admin.Category.Data.CategoryOptionData;
type Errors = Record<string, string | undefined>;
type ExtensionSection = Larasell.Admin.Platform.Sections.Data.SectionData;
type ProductType = Larasell.Admin.Product.Data.ProductTypeData;
/** The product form's own sections, generated from the ProductSection enum. */
type Anchor = Larasell.Admin.Product.Enums.ProductSection;

/**
 * The sections of a product's fields. It must sit inside an Inertia `<Form>`, which submits them
 * and provides their errors; without a product it starts empty, visible and in no category.
 */
export default function ProductForm({
  product,
  categories,
  createCategoryUrl,
  sections = [],
  type,
  attributes = [],
  attributesUrl = "",
  countries = [],
}: {
  /** The catalog's attributes page. */
  attributesUrl?: string;
  /** Every country to pick the country of origin from. */
  countries?: { code: string; label: string }[];
  /** The attributes the product has values of, each with the product's. */
  attributes?: Larasell.Admin.Product.Data.ProductAttributeFieldData[];
  product?: Product;
  /** The product's type; its capabilities decide which sections show. */
  type: ProductType;
  /** The store's categories to pick the product's from. */
  categories: Category[];
  /** Where the store's first categories are created, offered while it has none. Null while the
   * categories are turned off. */
  createCategoryUrl: string | null;
  /** The sections that modules and plugins add, placed around the product's own. */
  sections?: ExtensionSection[];
}) {
  const errors: Errors = useFormContext()?.errors ?? {};
  // Every one of ProductSection's anchors, in the order they show: a new one fails to compile here
  // until the form renders it, so no section placed at it goes missing.
  const own = {
    general: (
      <Section.Root>
        <Section.Header
          title={fbs(
            "General",
            "Product form: heading of the section with the product's name and visibility",
          )}
          description={fbs(
            "How the product is named and found in the store.",
            "Product form: text below the heading of the General section, with the name and visibility",
          )}
        />
        <GeneralFields product={product} errors={errors} />
      </Section.Root>
    ),
    categories: (
      <Section.Root>
        <Section.Header
          title={fbs(
            "Categories",
            "Product form: heading of the section that picks the categories the product is listed in",
          )}
          description={fbs(
            "Where the product is listed in the store.",
            "Product form: text below the heading of the Categories section",
          )}
        />
        <CategoryCheckboxTree
          categories={categories}
          defaultSelectedIds={product?.categoryIds ?? []}
          createUrl={createCategoryUrl}
        />
      </Section.Root>
    ),
    stock: type.capabilities.includes("stock") && (
      <Section.Root>
        <Section.Header
          title={fbs(
            "Stock",
            "Product form: heading of the section with the stock level, backorders and order quantity limits",
          )}
          description={fbs(
            "How many can be sold, and how many a customer can buy at once.",
            "Product form: text below the heading of the Stock section",
          )}
        />
        <StockFields product={product} errors={errors} />
      </Section.Root>
    ),
    "weight-and-dimensions": type.capabilities.includes("dimensions") && (
      <Section.Root>
        <Section.Header
          title={fbs(
            "Weight and dimensions",
            "Product form: heading of the section with the product's shipping weight, length, width and height",
          )}
          description={fbs(
            "Used to choose shipping methods and their prices. Leave them empty for products that aren't shipped.",
            "Product form: text below the heading of the Weight and dimensions section",
          )}
        />
        <WeightAndDimensionsFields product={product} errors={errors} />
      </Section.Root>
    ),
    identifiers: (
      <Section.Root>
        <Section.Header
          title={fbs(
            "Identifiers",
            "Product form: heading of the section with the SKU, barcode (e.g. EAN or UPC) and manufacturer part number",
          )}
          description={fbs(
            "Identify the product in your inventory and when it's scanned. Each belongs to one product only.",
            "Product form: text below the heading of the SKU and barcode section",
          )}
        />
        <IdentifierFields product={product} errors={errors} />
      </Section.Root>
    ),
    customs: type.capabilities.includes("dimensions") && (
      <Section.Root>
        <Section.Header
          title={fbs(
            "Customs",
            "Product form: heading of the section with the country of origin and HS code",
          )}
          description={fbs(
            "What customs need to know when the product is shipped abroad. Variants take these values unless they set their own.",
            "Product form: text below the heading of the Customs section",
          )}
        />
        <div className="@container">
          <div className="grid items-start gap-5 @md:grid-cols-2">
            <Field
              label={fbs(
                "Country of origin",
                "Product form: label of the country the goods were made in",
              )}
              name="country_of_origin"
              error={errors.country_of_origin}
            >
              <Select
                items={[{ code: "", label: "—" }, ...countries]}
                itemToKey={(country) => country.code}
                itemToLabel={(country) => country.label}
                defaultValue={product?.countryOfOrigin ?? ""}
                filter={countryFilter()}
              />
            </Field>
            <Field
              label={fbs("HS code", "Product form: label of the customs tariff number")}
              name="hs_code"
              error={errors.hs_code}
            >
              <Input defaultValue={product?.hsCode ?? ""} autoComplete="off" spellCheck={false} />
            </Field>
          </div>
        </div>
      </Section.Root>
    ),
    attributes: attributes.length > 0 && (
      <Section.Root>
        <Section.Header
          title={fbs(
            "Attributes",
            "Product form: heading of the section with the product's attribute values, e.g. its material",
          )}
          description={fbs(
            "Details customers can filter and compare by, such as material. Variants take these values unless they set their own.",
            "Product form: text below the heading of the Attributes section",
          )}
        />
        <AttributeFields attributes={attributes} attributesUrl={attributesUrl} errors={errors} />
      </Section.Root>
    ),
  } satisfies Record<Anchor, ReactNode>;

  return (
    <SectionStack>
      {Object.entries(own).map(([anchor, section]) => (
        <Fragment key={anchor}>
          <ExtensionSections sections={sections} before={anchor} />
          {section}
          <ExtensionSections sections={sections} after={anchor} />
        </Fragment>
      ))}
      <ExtensionSections sections={sections} anchors={Object.keys(own)} />
    </SectionStack>
  );
}

function GeneralFields({ product, errors }: { product?: Product; errors: Errors }) {
  // Untranslated fields stay empty and show what the store shows instead.
  const untranslated = product?.name === null;

  return (
    <Stack spacing={5}>
      <Field
        label={fbs("Name", "Product form: label of the field for the product's name")}
        name="name"
        error={errors.name}
      >
        <Input
          defaultValue={product?.name ?? undefined}
          placeholder={untranslated ? product.fallbackName : undefined}
          autoComplete="off"
        />
      </Field>

      <div className="flex flex-col gap-1.5">
        <span className="type-label-md text-base-primary" aria-hidden>
          <fbt desc="Product form: label of the formatted text field for the product's description">
            Description
          </fbt>
        </span>
        <RichTextEditor
          name="description"
          label={fbs(
            "Description",
            "Product form: accessible name of the formatted text field for the product's description",
          )}
          defaultValue={product?.description ?? null}
          placeholder={fbs(
            "Enter a description…",
            "Product form: placeholder in the empty description field",
          )}
          invalid={!!errors.description}
        />
        {errors.description && <Error match>{errors.description}</Error>}
      </div>

      <VisibilityField defaultVisible={product?.visible ?? true} error={errors.status} />
    </Stack>
  );
}

/** Both optional; the barcode is saved as its digits, without spaces or dashes. */
/** Also on the variant page, where each variant has its own identifiers. */
export function IdentifierFields({
  product,
  errors,
}: {
  product?: Pick<Product, "sku" | "barcode" | "mpn">;
  errors: Errors;
}) {
  return (
    <div className="@container">
      <div className="grid items-start gap-5 @md:grid-cols-2">
        <Field
          label={fbs(
            "SKU",
            "Product form: label of the field for the stock keeping unit, the store's own product code",
          )}
          name="sku"
          error={errors.sku}
        >
          <Input defaultValue={product?.sku ?? ""} autoComplete="off" spellCheck={false} />
        </Field>

        <Field
          label={fbs(
            "Barcode",
            "Product form: label of the field for the product's barcode number, e.g. EAN or UPC",
          )}
          name="barcode"
          error={errors.barcode}
        >
          <Input defaultValue={product?.barcode ?? ""} autoComplete="off" spellCheck={false} />
        </Field>

        <Field
          label={fbs("MPN", "Product form: label of the field for the manufacturer part number")}
          name="mpn"
          error={errors.mpn}
        >
          <Input defaultValue={product?.mpn ?? ""} autoComplete="off" spellCheck={false} />
        </Field>
      </div>
    </div>
  );
}

/** Every number is optional: without a stock it isn't tracked, without a limit there is none. */
function StockFields({ product, errors }: { product?: Product; errors: Errors }) {
  return (
    <Stack spacing={5}>
      <div className="@container">
        <div className="grid items-start gap-5 @md:grid-cols-2">
          <Field
            label={fbs("Stock", "Product form: label of the field for how many items are in stock")}
            name="stock"
            error={errors.stock}
            description={fbs(
              "Leave empty to sell without counting.",
              "Product form: hint below the stock field, saying an empty stock isn't tracked",
            )}
          >
            <Input defaultValue={product?.stock ?? ""} inputMode="numeric" autoComplete="off" />
          </Field>

          <BaseField.Root
            name="allow_backorders"
            invalid={!!errors.allow_backorders}
            // Relative keeps the checkbox's absolutely positioned hidden input inside the scrolling
            // content. Beside the stock, the padding skips its label to center on its input.
            className="relative flex flex-col gap-1.5 @md:pt-9"
          >
            <Checkbox.Label>
              <FormCheckbox
                name="allow_backorders"
                value="1"
                uncheckedValue="0"
                defaultChecked={product?.allowBackorders ?? false}
              />
              <fbt desc="Product form: label of the checkbox that lets customers order the product while it's out of stock">
                Allow backorders
              </fbt>
            </Checkbox.Label>
            {errors.allow_backorders && <Error match>{errors.allow_backorders}</Error>}
          </BaseField.Root>
        </div>
      </div>

      <div className="@container">
        <div className="grid items-start gap-5 @md:grid-cols-2">
          <Field
            label={fbs(
              "Minimum quantity",
              "Product form: label of the field for the fewest items of the product a customer must buy at once",
            )}
            name="min_quantity"
            error={errors.min_quantity}
          >
            <Input
              defaultValue={product?.minQuantity ?? ""}
              inputMode="numeric"
              autoComplete="off"
            />
          </Field>

          <Field
            label={fbs(
              "Maximum quantity",
              "Product form: label of the field for the most items of the product a customer can buy at once",
            )}
            name="max_quantity"
            error={errors.max_quantity}
          >
            <Input
              defaultValue={product?.maxQuantity ?? ""}
              inputMode="numeric"
              autoComplete="off"
            />
          </Field>
        </div>
      </div>
    </Stack>
  );
}

/** The dimensions, with their field's label and their unit select's, in the interface language. */
export function axes() {
  return [
    {
      axis: "length",
      label: fbs("Length", "Product form: label of the field for the product's shipping length"),
      unitLabel: fbs(
        "Length unit",
        "Product form: accessible name of the select next to the length field that picks the unit, e.g. cm",
      ),
    },
    {
      axis: "width",
      label: fbs("Width", "Product form: label of the field for the product's shipping width"),
      unitLabel: fbs(
        "Width unit",
        "Product form: accessible name of the select next to the width field that picks the unit, e.g. cm",
      ),
    },
    {
      axis: "height",
      label: fbs("Height", "Product form: label of the field for the product's shipping height"),
      unitLabel: fbs(
        "Height unit",
        "Product form: accessible name of the select next to the height field that picks the unit, e.g. cm",
      ),
    },
  ] as const;
}

/** The units Larasell's WeightUnit and LengthUnit define. */
export const WEIGHT_UNITS = ["g", "kg", "oz", "lb"];
export const LENGTH_UNITS = ["mm", "cm", "m", "in", "ft"];

/**
 * Metric by default; saved values keep the unit they were entered in. Changing a unit only
 * relabels the amounts, it never converts them.
 */
function WeightAndDimensionsFields({ product, errors }: { product?: Product; errors: Errors }) {
  const weightUnit = product?.weight?.unit ?? "kg";
  const dimensionsUnit = product?.dimensions?.unit ?? "cm";
  // Dimensions share one unit, so every select shows it and changing one changes all.
  const dimensionsUnits = useRef<(HTMLSelectElement | null)[]>([]);
  const syncDimensionsUnits: ChangeEventHandler<HTMLSelectElement> = (event) => {
    for (const select of dimensionsUnits.current) {
      if (select) select.value = event.currentTarget.value;
    }
  };

  return (
    // More room than between the fields, so the weight and the dimensions read as two groups.
    <Stack spacing={12}>
      {/* The same two columns as the dimensions, with the weight in the first. They stack until
            the form is nearly its full width, since amounts with a unit need the room. */}
      <div className="@container">
        <div className="grid items-start gap-5 @min-[40rem]:grid-cols-2">
          <Field
            label={fbs(
              "Weight",
              "Product form: label of the field for the product's shipping weight",
            )}
            name="weight[amount]"
            error={errors["weight.amount"] ?? errors["weight.unit"]}
          >
            <Input
              defaultValue={product?.weight?.amount}
              inputMode="decimal"
              autoComplete="off"
              suffix={
                <UnitSelect
                  name="weight[unit]"
                  label={fbs(
                    "Weight unit",
                    "Product form: accessible name of the select next to the weight field that picks the unit, e.g. kg",
                  )}
                  units={WEIGHT_UNITS}
                  defaultValue={weightUnit}
                />
              }
            />
          </Field>
        </div>
      </div>

      <div className="@container">
        <div className="grid items-start gap-5 @min-[40rem]:grid-cols-2">
          {axes().map(({ axis, label, unitLabel }, index) => (
            <Field
              key={axis}
              label={label}
              name={`dimensions[${axis}]`}
              error={
                errors[`dimensions.${axis}`] ??
                (index === 0 ? errors["dimensions.unit"] : undefined)
              }
            >
              <Input
                defaultValue={product?.dimensions?.[axis]}
                inputMode="decimal"
                autoComplete="off"
                // Only the first unit select is submitted.
                suffix={
                  <UnitSelect
                    ref={(select) => {
                      dimensionsUnits.current[index] = select;
                    }}
                    onChange={syncDimensionsUnits}
                    name={index === 0 ? "dimensions[unit]" : undefined}
                    label={unitLabel}
                    units={LENGTH_UNITS}
                    defaultValue={dimensionsUnit}
                  />
                }
              />
            </Field>
          ))}
        </div>
      </div>
    </Stack>
  );
}

/**
 * A native select in an Input's suffix, since the UI library has no select. It's styled like the
 * PasswordInput's visibility toggle, filling the input's height up to its right edge, with a
 * border that separates it from the amount and our own chevron in place of the browser's.
 * Errors show on the amount, since the unit is always one of the options.
 */
export function UnitSelect({
  label,
  units,
  ref,
  readOnly = false,
  ...props
}: {
  /** Shows the unit but keeps it from changing, while staying focusable. */
  readOnly?: boolean;
  /** Left out for selects that only mirror another one's unit. */
  name?: string;
  /** The accessible name, which says what the unit is for. */
  label: string;
  units: string[];
  defaultValue: string;
  ref?: Ref<HTMLSelectElement>;
  onChange?: ChangeEventHandler<HTMLSelectElement>;
}) {
  return (
    // The wrapper takes the suffix's place; the select fills it so the whole area opens it.
    <div className="border-base-subtle relative -mr-3.5 flex self-stretch border-l">
      <select
        {...props}
        {...readOnlySelect(readOnly)}
        ref={ref}
        aria-label={label}
        className={cn(
          "type-input-md cursor-pointer appearance-none bg-transparent pr-9 pl-3.5 text-base-primary select-none",
          !readOnly && "hover:bg-action-neutral-ghost-hover",
          "focus-visible:focus-ring focus-visible:outline-offset-0",
        )}
      >
        {units.map((unit) => (
          <option key={unit} value={unit}>
            {unit}
          </option>
        ))}
      </select>
      {/* Clicks pass through to the select underneath. */}
      <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-base-primary">
        <Icon icon={ArrowDownSLine} />
      </span>
    </div>
  );
}

/**
 * Native selects have no `readonly`, and a disabled one can't be focused, so screen readers skip
 * its value. This keeps the select focusable and announced as read-only, but ignores the pointer
 * and the keys that would change it.
 */
export function readOnlySelect(readOnly: boolean) {
  if (!readOnly) return {};
  return {
    "aria-readonly": true,
    onMouseDown: (event: { preventDefault: () => void }) => event.preventDefault(),
    onKeyDown: (event: { key: string; preventDefault: () => void }) => {
      if (event.key !== "Tab") event.preventDefault();
    },
  };
}

/** A list's items with "—" first, which picks none and submits an empty value. */
export function withNone<Item extends { id: number | string; name: string }>(items: Item[]) {
  return [
    { id: "", name: "—" },
    ...items.map((item) => ({ id: String(item.id), name: item.name })),
  ];
}

/** The search in the long list of countries. */
export function countryFilter() {
  return {
    placeholder: fbs(
      "Search countries",
      "Placeholder of the search field in the list of countries",
    ),
    empty: fbs("No country found", "Shown in the list of countries when the search matches none"),
  };
}

/** One select per attribute, with "—" for no value. */
function AttributeFields({
  attributes,
  attributesUrl,
  errors,
}: {
  attributesUrl: string;
  attributes: Larasell.Admin.Product.Data.ProductAttributeFieldData[];
  errors: Errors;
}) {
  // Like the options: a row per attribute the product has values of, and a select that adds one.
  const [shown, setShown] = useState(() =>
    attributes.filter((attribute) => attribute.value.length > 0).map((attribute) => attribute.id),
  );
  const root = useRef<HTMLDivElement>(null);
  // Removing a row only removes inputs, which fire no input event, so tell the form it changed.
  useMarkDirty(root, shown);

  const pinned = usePinnedAnchor();
  return (
    <div ref={root} className="flex flex-col gap-3">
      {attributes.map((attribute) =>
        shown.includes(attribute.id) ? (
          <div key={attribute.id} className="flex flex-col gap-1">
            <div className="flex flex-wrap items-center gap-2">
              <span className="w-28 type-label-md text-base-primary">{attribute.name}</span>
              <AttributeValuesPicker
                name={`attributes[${attribute.id}]`}
                label={attribute.name}
                values={attribute.values}
                defaultValue={attribute.value}
                single={!attribute.multiple}
              />
            </div>
            {errors[`attributes.${attribute.id}`] && (
              <Error match>{errors[`attributes.${attribute.id}`]}</Error>
            )}
          </div>
        ) : (
          // Not shown, so the product keeps no value of it.
          <input key={attribute.id} type="hidden" name={`attributes[${attribute.id}]`} value="" />
        ),
      )}
      <div className="flex items-center gap-1">
        <Select.Root
          multiple
          items={attributes}
          itemToKey={(attribute) => String(attribute.id)}
          itemToLabel={(attribute) => attribute.name}
          value={shown.map(String)}
          onValueChange={(keys) => setShown(keys.map(Number))}
          filter={{
            placeholder: fbs(
              "Search attributes",
              "Product form: placeholder of the search in the list of attributes to add",
            ),
            empty: fbs(
              "No attributes found",
              "Product form: shown in the list of attributes when the search matches none",
            ),
          }}
        >
          <span {...pinned.trigger}>
            <Select.Trigger render={<button type="button" className={ghostAction} />}>
              <Icon icon={AddLine} />
              <fbt desc="Product form: button that opens the list of attributes to give the product">
                Add attribute
              </fbt>
            </Select.Trigger>
          </span>
          <Select.Popup anchor={pinned.anchor} />
        </Select.Root>
        <Link
          href={attributesUrl}
          className={cn(ghostAction, "w-6.5 justify-center px-0")}
          aria-label={fbs(
            "Attributes page",
            "Product form: accessible name of the button next to Add attribute that opens the catalog's attributes",
          )}
        >
          <Icon icon={ArrowRightSLine} />
        </Link>
      </div>
    </div>
  );
}

/**
 * The values of a multi-value attribute as chips, like a product's option values, with a select
 * that adds one. Submits `name[]`, or an empty `name` while none is picked.
 */
export function AttributeValuesPicker({
  name,
  label,
  values,
  defaultValue,
  readOnly = false,
  single = false,
}: {
  /** Takes one value at most; the add select hides once it has one. */
  single?: boolean;
  name: string;
  label: string;
  values: { id: number; name: string }[];
  defaultValue: number[];
  readOnly?: boolean;
}) {
  const [picked, setPicked] = useState(defaultValue);
  const root = useRef<HTMLDivElement>(null);
  const changed = useRef(false);
  const pick = (next: number[]) => {
    changed.current = true;
    setPicked(next);
  };
  // Inertia's <Form> compares its data on input events; hidden inputs fire none.
  useEffect(() => {
    if (!changed.current) return;
    changed.current = false;
    root.current?.dispatchEvent(new Event("input", { bubbles: true }));
  }, [picked]);
  return (
    <div ref={root} className="flex flex-wrap items-center gap-2">
      {/* Without values, an empty field still says the list was emptied. */}
      {picked.length === 0 && <input type="hidden" name={name} value="" />}
      {picked.length === 0 && readOnly && (
        <span className="type-body-md text-base-secondary">—</span>
      )}
      {values.length === 0 && !readOnly && (
        <span className="type-body-sm text-base-secondary">
          {fbs(
            "No values yet. Add them on the Attributes page.",
            "Product form: shown next to an attribute that has no values to pick from yet",
          )}
        </span>
      )}
      {values
        .filter((value) => picked.includes(value.id))
        .map((value) => (
          <span
            key={value.id}
            className="flex items-center gap-1 rounded-full bg-action-neutral-secondary-hover h-6.5 pr-0.5 pl-2.5 type-body-sm"
          >
            <input type="hidden" name={`${name}[]`} value={value.id} />
            {value.name}
            {!readOnly && (
              <button
                type="button"
                className="flex size-5.5 cursor-pointer items-center justify-center rounded-full bg-transparent text-base-primary hover:bg-action-neutral-ghost-hover focus-visible:focus-ring"
                aria-label={fbs(
                  ["Remove ", fbs.param("value", value.name)],
                  "Product form: accessible name of the button on an attribute value that removes it, e.g. 'Remove Cotton'",
                )}
                onClick={() => pick(picked.filter((id) => id !== value.id))}
              >
                <Icon icon={CloseLine} />
              </button>
            )}
          </span>
        ))}
      {!readOnly && values.length > 0 && (
        <ValuesPopover
          label={label}
          values={values}
          picked={picked}
          single={single}
          onChange={pick}
          anchor={root}
        />
      )}
    </div>
  );
}

/**
 * A round "+" that opens every value with a checkbox, ticked while picked, and a search. The
 * list stays where it opened while chips come and go, so the trigger moving doesn't move it.
 */
function ValuesPopover({
  label,
  values,
  picked,
  single,
  onChange,
  anchor,
}: {
  label: string;
  values: { id: number; name: string }[];
  picked: number[];
  single: boolean;
  onChange: (picked: number[]) => void;
  /** The row the list is placed below, at the trigger's position when it opened. */
  anchor: RefObject<HTMLElement | null>;
}) {
  const trigger = useRef<HTMLSpanElement>(null);
  // The trigger's offset in the row when the list opened; the row itself doesn't move.
  const offset = useRef(0);
  const keys = picked.map(String);
  const measure = () => {
    const row = anchor.current?.getBoundingClientRect();
    const button = trigger.current?.getBoundingClientRect();
    offset.current = row && button ? button.left - row.left : 0;
  };
  const addLabel = fbs(
    ["Add values to ", fbs.param("attribute", label)],
    "Product form: accessible name of the round button that opens the list of an attribute's values, e.g. 'Add values to Material'",
  );

  return (
    <Select.Root
      multiple
      items={values}
      itemToKey={(value) => String(value.id)}
      itemToLabel={(value) => value.name}
      value={keys}
      onValueChange={(next) => {
        const added = next.filter((key) => !keys.includes(key));
        // One value at most: picking another replaces it.
        onChange((single ? added.slice(-1) : next).map(Number));
      }}
      filter={{
        placeholder: fbs(
          "Search values",
          "Product form: placeholder of the search in the list of an attribute's values",
        ),
        empty: fbs(
          "No values found",
          "Product form: shown in the list of an attribute's values when the search matches none",
        ),
      }}
    >
      {/* Measures where the trigger is when the list opens, by mouse or by key. */}
      <span
        ref={trigger}
        className="flex"
        onPointerDownCapture={measure}
        onKeyDownCapture={measure}
      >
        <Select.Trigger
          // A tertiary button's styles, as tall as the chips (26px) and round.
          render={
            <button
              type="button"
              className="flex size-6.5 cursor-pointer items-center justify-center rounded-full border border-transparent bg-transparent text-base-primary outline-none not-data-disabled:hover:bg-action-neutral-ghost-hover focus-visible:focus-ring data-popup-open:bg-action-neutral-ghost-hover"
            />
          }
          aria-label={addLabel}
        >
          <Icon icon={AddLine} />
        </Select.Trigger>
      </span>
      <Select.Popup
        anchor={() => {
          const row = anchor.current;
          const button = trigger.current;
          if (!row || !button) return null;
          return {
            getBoundingClientRect: () => {
              const r = row.getBoundingClientRect();
              const b = button.getBoundingClientRect();
              return new DOMRect(r.left + offset.current, b.top, b.width, b.height);
            },
          };
        }}
      />
    </Select.Root>
  );
}

/**
 * A quiet action in a section, such as "Add attribute": secondary text without a background,
 * which shows on hover and while its list is open. As tall and round as the chips.
 */
export const ghostAction =
  "flex h-6.5 cursor-pointer items-center gap-1.5 self-start rounded-full px-2.5 type-label-md text-base-secondary outline-none hover:bg-action-neutral-ghost-hover focus-visible:focus-ring data-popup-open:bg-action-neutral-ghost-hover";

/** Fires an input event on `root` after `value` changes, so Inertia's <Form> sees it's dirty. */
export function useMarkDirty(root: RefObject<HTMLElement | null>, value: unknown) {
  const first = useRef(true);
  useEffect(() => {
    if (first.current) {
      first.current = false;
      return;
    }
    root.current?.dispatchEvent(new Event("input", { bubbles: true }));
  }, [root, value]);
}

/**
 * Keeps a popup where its trigger was when it opened, as the page moves under it while rows
 * come and go. Spread `trigger` on a wrapper of the trigger and pass `anchor` to the popup.
 */
export function usePinnedAnchor() {
  const ref = useRef<HTMLSpanElement>(null);
  const at = useRef({ x: 0, y: 0, width: 0, height: 0 });
  const measure = () => {
    const r = ref.current?.getBoundingClientRect();
    if (r)
      at.current = {
        x: r.left + window.scrollX,
        y: r.top + window.scrollY,
        width: r.width,
        height: r.height,
      };
  };
  return {
    trigger: { ref, className: "flex", onPointerDownCapture: measure, onKeyDownCapture: measure },
    anchor: () => ({
      getBoundingClientRect: () =>
        new DOMRect(
          at.current.x - window.scrollX,
          at.current.y - window.scrollY,
          at.current.width,
          at.current.height,
        ),
    }),
  };
}
