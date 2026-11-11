import { useFormContext } from "@inertiajs/react";
import {
  Button,
  ColorPicker,
  EmptyState,
  Field,
  Icon,
  Input,
  Section,
  Slideover,
  Stack,
  Table,
  useToast,
  type ColorPickerLabels,
} from "@larasell-dev/ui";
import {
  AddLine,
  ArrowDownLine,
  ArrowUpLine,
  DeleteBinLine,
  EditLine,
  ListCheck,
} from "@larasell-dev/ui/icons";
import { HotkeysProvider } from "@tanstack/react-hotkeys";
import { fbs } from "fbtee";
import { useEffect, useRef, useState } from "react";
import { overlayExclusive } from "../../Platform/Support/hotkeys";
import { slugify } from "../../Platform/Support/slugify";

type Value = Larasell.Admin.Product.Data.OptionValueRowData;

/** A value as the page holds it until it's saved; new ones have no id yet. */
type Row = {
  /** Only identifies the row on the page; never sent. */
  key: string;
  id: number | null;
  name: string;
  slug: string;
  /** A colour such as #000000, or "" for none. */
  swatch: string;
  /** Another language's name, shown while it has none in the content locale; null when none has one. */
  fallbackName: string | null;
  products: number;
  variants: number;
};

const SLUG = /^[a-z0-9]+(?:[-_][a-z0-9]+)*$/;

const rowsOf = (values: Value[]): Row[] =>
  values.map((value) => ({
    key: String(value.id),
    id: value.id,
    name: value.name ?? "",
    slug: value.slug,
    swatch: value.swatch ?? "",
    fallbackName: value.fallbackName,
    products: value.products,
    variants: value.variants,
  }));

const label = (row: Row) => row.name || row.fallbackName || "";

/** "2 variants of 1 product" style usage. */
function usage(row: Row): string {
  return fbs(
    [
      fbs.plural("variant", row.variants, { many: "variants", showCount: "yes", name: "variants" }),
      " of ",
      fbs.plural("product", row.products, { many: "products", showCount: "yes", name: "products" }),
    ],
    "Option page: how many variants of how many products use a value, e.g. '2 variants of 1 product'",
  );
}

/** A small square of the value's colour. */
function Swatch({ color }: { color: string }) {
  return (
    <span
      aria-hidden
      className="inline-block size-4 shrink-0 border border-base-strong"
      style={{ backgroundColor: color }}
    />
  );
}

/**
 * The option's values, edited on the page and saved with the surrounding Inertia `<Form>`: it
 * submits all of them, in order, as values[i][id|name|slug|swatch]. New ones go without an id,
 * and left-out ones are deleted. Values variants use can't be removed: their variants go first,
 * on the product page.
 */
export default function OptionValues({
  values,
  swatches,
}: {
  values: Value[];
  /** Whether the option displays as swatches; only then are the values' colours shown and edited. */
  swatches: boolean;
}) {
  const [rows, setRows] = useState(() => rowsOf(values));
  // The row the slideover edits, "new" while it adds one, or null while it is closed.
  const [editing, setEditing] = useState<Row | "new" | null>(null);
  // Counts the slideover's openings, so each one starts with fresh fields.
  const [opened, setOpened] = useState(0);
  const edit = (row: Row | "new") => {
    setOpened((count) => count + 1);
    setEditing(row);
  };
  const root = useRef<HTMLDivElement>(null);
  const changed = useRef(false);
  const errors = useFormContext()?.errors ?? {};

  const change = (next: Row[]) => {
    changed.current = true;
    setRows(next);
  };

  // Inertia's <Form> compares its data on input events; hidden inputs fire none.
  useEffect(() => {
    if (!changed.current) return;
    changed.current = false;
    root.current?.dispatchEvent(new Event("input", { bubbles: true }));
  }, [rows]);

  // Discarding the form's changes, or saving them, starts again from the saved values.
  useEffect(() => {
    const form = root.current?.closest("form");
    const reset = () => setRows(rowsOf(values));
    form?.addEventListener("reset", reset);
    return () => form?.removeEventListener("reset", reset);
  }, [values]);

  const move = (index: number, by: -1 | 1) => {
    const next = [...rows];
    [next[index], next[index + by]] = [next[index + by]!, next[index]!];
    change(next);
  };
  const toast = useToast();
  const remove = (row: Row) => {
    // Variants use it: they go first, on their product's page.
    if (row.variants > 0) {
      toast.add({
        id: `value-in-use-${row.key}`,
        title: fbs(
          [fbs.param("name", label(row)), " can't be removed"],
          "Option page: toast title when removing a value variants use, e.g. 'Black can't be removed'",
        ),
        description: fbs(
          [
            "It's used by ",
            fbs.param("usage", usage(row)),
            ". Delete those variants on the product page first.",
          ],
          "Option page: toast text when removing a value variants use, e.g. 'It's used by 2 variants of 1 product. Delete those variants on the product page first.'",
        ),
      });
      return;
    }
    change(rows.filter((other) => other !== row));
  };

  const error = (index: number) =>
    errors[`values.${index}.name`] ??
    errors[`values.${index}.slug`] ??
    errors[`values.${index}.swatch`];

  const add = (primary: boolean) => (
    <Button
      variant={primary ? "primary" : "secondary"}
      size={primary ? undefined : "sm"}
      onClick={() => edit("new")}
    >
      <Icon icon={AddLine} />
      <fbt desc="Option page: button that opens the slideover to add a value">Add value</fbt>
    </Button>
  );

  return (
    <Section.Root>
      <Section.Header
        title={fbs("Values", "Option page: heading of the option's values")}
        description={fbs(
          "What variants are told apart by, in the order the store shows them.",
          "Option page: explains the values section",
        )}
        // In the header once there are values; the empty state has its own.
        action={rows.length > 0 && add(false)}
      />
      <div ref={root}>
        {rows.map((row, index) => (
          <span key={row.key} hidden>
            {row.id !== null && (
              <input type="hidden" name={`values[${index}][id]`} value={row.id} />
            )}
            <input type="hidden" name={`values[${index}][name]`} value={row.name} />
            <input type="hidden" name={`values[${index}][slug]`} value={row.slug} />
            <input type="hidden" name={`values[${index}][swatch]`} value={row.swatch} />
          </span>
        ))}
        {/* Without values, an empty field still says the list was emptied. */}
        {rows.length === 0 && <input type="hidden" name="values" value="" />}
      </div>
      {errors.values && (
        <p role="alert" className="type-body-sm text-intent-negative">
          {errors.values}
        </p>
      )}
      {rows.length > 0 ? (
        <Section.Card>
          <Table.Root aria-label={fbs("Values", "Option page: name of the values table")}>
            <Table.Content>
              <Table.Header>
                <Table.Column>
                  <fbt desc="Option page: values table column with the name">Name</fbt>
                </Table.Column>
                <Table.Column>
                  <fbt desc="Option page: values table column with the slug">Slug</fbt>
                </Table.Column>
                <Table.Column type="action">
                  <fbt desc="Option page: values table column with each value's buttons">
                    Actions
                  </fbt>
                </Table.Column>
              </Table.Header>
              <Table.Body>
                {rows.map((row, index) => (
                  <Table.Row key={row.key} onClick={() => edit(row)}>
                    <Table.Cell>
                      <div className="flex min-w-0 flex-col gap-0.5">
                        <span className="flex items-center gap-2">
                          {swatches && row.swatch && <Swatch color={row.swatch} />}
                          <span className={row.name ? undefined : "text-base-secondary"}>
                            {label(row)}
                          </span>
                        </span>
                        {error(index) && (
                          <span role="alert" className="type-body-sm text-intent-negative">
                            {error(index)}
                          </span>
                        )}
                      </div>
                    </Table.Cell>
                    <Table.Cell>
                      <code className="type-body-sm">{row.slug}</code>
                    </Table.Cell>
                    <Table.Cell>
                      {/* Its menu doesn't open the row's slideover. */}
                      <div onClick={(event) => event.stopPropagation()}>
                        <Table.Actions>
                          <Table.ActionsTrigger
                            aria-label={fbs(
                              ["Actions for ", fbs.param("name", label(row))],
                              "Option page: accessible name of the button that opens a value's menu, e.g. 'Actions for Black'",
                            )}
                          />
                          <Table.ActionsPopup>
                            <HotkeysProvider
                              scope="value-actions-menu"
                              exclusive={overlayExclusive}
                            >
                              <Table.ActionsItem icon={EditLine} onClick={() => edit(row)}>
                                <fbt desc="Option page: menu item that opens the slideover to change a value">
                                  Edit
                                </fbt>
                              </Table.ActionsItem>
                              <Table.ActionsItem
                                icon={ArrowUpLine}
                                disabled={index === 0}
                                onClick={() => move(index, -1)}
                              >
                                <fbt desc="Option page: menu item that moves a value one place up">
                                  Move up
                                </fbt>
                              </Table.ActionsItem>
                              <Table.ActionsItem
                                icon={ArrowDownLine}
                                disabled={index === rows.length - 1}
                                onClick={() => move(index, 1)}
                              >
                                <fbt desc="Option page: menu item that moves a value one place down">
                                  Move down
                                </fbt>
                              </Table.ActionsItem>
                              <Table.ActionsItem
                                variant="destructive"
                                icon={DeleteBinLine}
                                onClick={() => remove(row)}
                              >
                                <fbt desc="Option page: menu item that removes a value; saving deletes it, or a toast says which variants use it">
                                  Remove
                                </fbt>
                              </Table.ActionsItem>
                            </HotkeysProvider>
                          </Table.ActionsPopup>
                        </Table.Actions>
                      </div>
                    </Table.Cell>
                  </Table.Row>
                ))}
              </Table.Body>
            </Table.Content>
          </Table.Root>
        </Section.Card>
      ) : (
        <Section.Card>
          <div className="flex justify-center py-8">
            <EmptyState
              icon={ListCheck}
              title={fbs(
                "No values yet",
                "Option page: shown in the values section while the option has none",
              )}
              description={fbs(
                "Add the values variants are told apart by, e.g. Black or White.",
                "Option page: explains the empty values section",
              )}
            >
              {add(true)}
            </EmptyState>
          </div>
        </Section.Card>
      )}
      <Slideover.Root open={editing !== null} onOpenChange={(open) => !open && setEditing(null)}>
        <ValueForm
          key={opened}
          swatches={swatches}
          row={editing === "new" ? null : editing}
          taken={rows.filter((row) => row !== editing).map((row) => row.slug)}
          onApply={(fields) => {
            if (editing === "new") {
              change([
                ...rows,
                {
                  key: crypto.randomUUID(),
                  id: null,
                  ...fields,
                  fallbackName: null,
                  products: 0,
                  variants: 0,
                },
              ]);
            } else if (editing) {
              change(rows.map((row) => (row === editing ? { ...row, ...fields } : row)));
            }
            setEditing(null);
          }}
        />
      </Slideover.Root>
    </Section.Root>
  );
}

/**
 * Adds a value or changes one on the page; the page's Save stores it. A new value's slug follows
 * its name until the admin edits it.
 */
function ValueForm({
  swatches,
  row,
  taken: takenNow,
  onApply,
}: {
  swatches: boolean;
  row: Row | null;
  /** The other values' slugs, which this one can't take. */
  taken: string[];
  onApply: (fields: { name: string; slug: string; swatch: string }) => void;
}) {
  // As they were when it opened: once applied, the list holds this value too.
  const [taken] = useState(takenNow);
  const [name, setName] = useState(row?.name ?? "");
  const [slug, setSlug] = useState(row?.slug ?? "");
  const [swatch, setSwatch] = useState(row?.swatch ?? "");
  const [coupled, setCoupled] = useState(row === null);
  const [tried, setTried] = useState(false);

  const named = name.trim() !== "" || !!row?.fallbackName;
  const slugged = SLUG.test(slug) && !taken.includes(slug);

  return (
    <form
      onSubmit={(event) => {
        // Not the page's form: this only changes the list.
        event.preventDefault();
        event.stopPropagation();
        setTried(true);
        if (named && slugged) onApply({ name: name.trim(), slug, swatch: swatch.toLowerCase() });
      }}
    >
      <Slideover.Header
        title={
          row === null
            ? fbs("Add value", "Option page: title of the slideover that adds a value")
            : fbs("Edit value", "Option page: title of the slideover that changes a value")
        }
        description={fbs(
          "Saved with the option.",
          "Option value slideover: says the change is stored when the option page is saved",
        )}
        closeLabel={fbs(
          "Close",
          "Option page: accessible name of the button that closes the value slideover",
        )}
      />
      <Slideover.Content>
        <Stack spacing={5}>
          <Field
            label={fbs("Name", "Option value slideover: label of the value's name, e.g. Black")}
            name="value_name"
            error={
              tried && !named
                ? fbs("Enter a name.", "Option value slideover: error when the name is empty")
                : undefined
            }
          >
            <Input
              value={name}
              placeholder={row?.fallbackName ?? undefined}
              onChange={(event) => {
                setName(event.target.value);
                if (coupled) setSlug(slugify(event.target.value).slice(0, 64).replace(/-+$/, ""));
              }}
              autoComplete="off"
              autoFocus
            />
          </Field>
          <Field
            label={fbs("Slug", "Option value slideover: label of the value's slug")}
            name="value_slug"
            error={
              !tried || slugged
                ? undefined
                : taken.includes(slug)
                  ? fbs(
                      "Another value has this slug.",
                      "Option value slideover: error when the slug is taken",
                    )
                  : fbs(
                      "Use lowercase letters and numbers, joined by - or _.",
                      "Option value slideover: error when the slug has other characters or is empty",
                    )
            }
          >
            <Input
              value={slug}
              onChange={(event) => {
                setSlug(event.target.value);
                setCoupled(event.target.value === "");
              }}
              autoComplete="off"
              spellCheck={false}
            />
          </Field>
          {swatches && (
            <Field
              label={fbs("Swatch colour", "Option value slideover: label of the value's colour")}
              name="value_swatch"
              description={fbs(
                "The colour of its swatch in the store. Leave empty for none.",
                "Option value slideover: hint below the swatch colour",
              )}
            >
              <ColorPicker.Root
                value={swatch}
                onValueChange={setSwatch}
                labels={colorPickerLabels()}
              >
                <ColorPicker.Input
                  placeholder="#000000"
                  render={(props, { value }) => (
                    <Input
                      {...props}
                      prefix={
                        <ColorPicker.Trigger
                          aria-label={fbs(
                            "Pick a colour",
                            "Option value slideover: accessible name of the button that opens the colour picker",
                          )}
                          className="size-5 shrink-0 cursor-pointer border border-base-strong outline-none focus-visible:focus-ring"
                          style={{ backgroundColor: value || undefined }}
                        />
                      }
                    />
                  )}
                />
                <ColorPicker.Popup />
              </ColorPicker.Root>
            </Field>
          )}
        </Stack>
      </Slideover.Content>
      <Slideover.Footer>
        <Slideover.Actions>
          <Slideover.Close>
            {fbs("Cancel", "Option value slideover: button that closes it without changes")}
          </Slideover.Close>
          <Button type="submit">
            {row === null
              ? fbs("Add", "Option value slideover: button that adds the value to the list")
              : fbs(
                  "Apply",
                  "Option value slideover: button that applies the changes to the value",
                )}
          </Button>
        </Slideover.Actions>
      </Slideover.Footer>
    </form>
  );
}

/** The colour picker's accessible names, translated when shown. */
function colorPickerLabels(): ColorPickerLabels {
  return {
    area: fbs(
      "Saturation and brightness",
      "Colour picker in the option value slideover: accessible name of the area that picks saturation and brightness",
    ),
    hue: fbs(
      "Hue",
      "Colour picker in the option value slideover: accessible name of the hue slider",
    ),
    alpha: fbs(
      "Alpha",
      "Colour picker in the option value slideover: accessible name of the opacity slider",
    ),
    copyLch: fbs(
      "Copy LCH",
      "Colour picker in the option value slideover: accessible name of the button that copies the colour as LCH",
    ),
    lchLightness: fbs(
      "LCH lightness",
      "Colour picker in the option value slideover: accessible name of the LCH lightness field",
    ),
    lchChroma: fbs(
      "LCH chroma",
      "Colour picker in the option value slideover: accessible name of the LCH chroma field",
    ),
    lchHue: fbs(
      "LCH hue",
      "Colour picker in the option value slideover: accessible name of the LCH hue field",
    ),
    copyHsl: fbs(
      "Copy HSL",
      "Colour picker in the option value slideover: accessible name of the button that copies the colour as HSL",
    ),
    hslHue: fbs(
      "HSL hue",
      "Colour picker in the option value slideover: accessible name of the HSL hue field",
    ),
    hslSaturation: fbs(
      "HSL saturation",
      "Colour picker in the option value slideover: accessible name of the HSL saturation field",
    ),
    hslLightness: fbs(
      "HSL lightness",
      "Colour picker in the option value slideover: accessible name of the HSL lightness field",
    ),
    copyRgb: fbs(
      "Copy RGB",
      "Colour picker in the option value slideover: accessible name of the button that copies the colour as RGB",
    ),
    red: fbs(
      "Red",
      "Colour picker in the option value slideover: accessible name of the RGB red field",
    ),
    green: fbs(
      "Green",
      "Colour picker in the option value slideover: accessible name of the RGB green field",
    ),
    blue: fbs(
      "Blue",
      "Colour picker in the option value slideover: accessible name of the RGB blue field",
    ),
    copyHex: fbs(
      "Copy hex",
      "Colour picker in the option value slideover: accessible name of the button that copies the colour as hex",
    ),
    hex: fbs(
      "Hex",
      "Colour picker in the option value slideover: accessible name of the hex colour field",
    ),
    alphaPercentage: fbs(
      "Alpha percentage",
      "Colour picker in the option value slideover: accessible name of the opacity percentage field",
    ),
  };
}
