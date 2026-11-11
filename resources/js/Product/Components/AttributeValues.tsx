import { useFormContext } from "@inertiajs/react";
import {
  Button,
  EmptyState,
  Field,
  Icon,
  Input,
  Section,
  Slideover,
  Stack,
  Table,
} from "@larasell-dev/ui";
import {
  AddLine,
  ArrowDownLine,
  ArrowGoBackLine,
  ArrowUpLine,
  DeleteBinLine,
  EditLine,
  PriceTag3Line,
} from "@larasell-dev/ui/icons";
import { HotkeysProvider } from "@tanstack/react-hotkeys";
import { fbs } from "fbtee";
import { useEffect, useRef, useState } from "react";
import { overlayExclusive } from "../../Platform/Support/hotkeys";
import { slugify } from "../../Platform/Support/slugify";
import type { AttributeType } from "./AttributeForm";

type Value = Larasell.Admin.Product.Data.AttributeValueRowData;

/** A value as the page holds it until it's saved; new ones have no id yet. */
type Row = {
  /** Only identifies the row on the page; never sent. */
  key: string;
  id: number | null;
  name: string;
  slug: string;
  /** Another language's name, shown while it has none in the content locale; null when none has one. */
  fallbackName: string | null;
  products: number;
  variants: number;
  /** A saved value that is in use, kept in the list until saving deletes it. */
  removed: boolean;
};

const rowsOf = (values: Value[]): Row[] =>
  values.map((value) => ({
    key: String(value.id),
    id: value.id,
    name: value.name ?? "",
    slug: value.slug,
    fallbackName: value.fallbackName,
    products: value.products,
    variants: value.variants,
    removed: false,
  }));

/** A new yes-or-no attribute's two values, named in the content locale. */
const yesAndNo = (): Row[] =>
  [
    ["__boolean_true", fbs("Yes", "Attribute page: name of a yes-or-no attribute's yes value")],
    ["__boolean_false", fbs("No", "Attribute page: name of a yes-or-no attribute's no value")],
  ].map(([slug, name]) => ({
    key: slug!,
    id: null,
    name: name!,
    slug: slug!,
    fallbackName: null,
    products: 0,
    variants: 0,
    removed: false,
  }));

const label = (row: Row) => row.name || row.fallbackName || "";

/**
 * The attribute's values, edited on the page and saved with the surrounding Inertia `<Form>`:
 * it submits all of them, in order, as values[i][id|name|slug]. New ones go without an id, and
 * left-out ones are deleted. Removing a value in use only marks it until saving, with what it
 * costs, so it can be undone.
 */
export default function AttributeValues({
  values,
  type,
}: {
  values: Value[];
  type: AttributeType;
}) {
  // A yes-or-no attribute has exactly its yes and no: they can be renamed, nothing else.
  const fixed = type === "boolean";
  const initial = () => (fixed && values.length === 0 ? yesAndNo() : rowsOf(values));
  const [rows, setRows] = useState(initial);
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
    const reset = () => setRows(initial());
    form?.addEventListener("reset", reset);
    return () => form?.removeEventListener("reset", reset);
    // eslint-disable-next-line react-hooks/exhaustive-deps -- initial() only reads values and type.
  }, [values, type]);

  const move = (index: number, by: -1 | 1) => {
    const next = [...rows];
    [next[index], next[index + by]] = [next[index + by]!, next[index]!];
    change(next);
  };
  const remove = (row: Row) =>
    change(
      row.id !== null && row.products + row.variants > 0
        ? rows.map((other) => (other === row ? { ...row, removed: true } : other))
        : rows.filter((other) => other !== row),
    );
  const restore = (row: Row) =>
    change(rows.map((other) => (other === row ? { ...row, removed: false } : other)));

  // The index each submitted row has, which the server's errors use.
  const submitted = rows.filter((row) => !row.removed);
  const error = (row: Row) => {
    const index = submitted.indexOf(row);
    return errors[`values.${index}.name`] ?? errors[`values.${index}.slug`];
  };

  const add = (primary: boolean) => (
    <Button
      variant={primary ? "primary" : "secondary"}
      size={primary ? undefined : "sm"}
      onClick={() => edit("new")}
    >
      <Icon icon={AddLine} />
      <fbt desc="Attribute page: button that opens the slideover to add a value">Add value</fbt>
    </Button>
  );

  return (
    <Section.Root>
      <Section.Header
        title={fbs("Values", "Attribute page: heading of the attribute's values")}
        description={fbs(
          "What products and variants pick from, in the order they show.",
          "Attribute page: explains the values section",
        )}
        // In the header once there are values; the empty state has its own.
        action={rows.length > 0 && !fixed && add(false)}
      />
      <div ref={root}>
        {submitted.map((row, index) => (
          <span key={row.key} hidden>
            {row.id !== null && (
              <input type="hidden" name={`values[${index}][id]`} value={row.id} />
            )}
            <input type="hidden" name={`values[${index}][name]`} value={row.name} />
            <input type="hidden" name={`values[${index}][slug]`} value={row.slug} />
          </span>
        ))}
        {/* Without values, an empty field still says the list was emptied. */}
        {submitted.length === 0 && <input type="hidden" name="values" value="" />}
      </div>
      {rows.length > 0 ? (
        <Section.Card>
          <Table.Root aria-label={fbs("Values", "Attribute page: name of the values table")}>
            <Table.Content>
              <Table.Header>
                <Table.Column>
                  <fbt desc="Attribute page: values table column with the name">Name</fbt>
                </Table.Column>
                {!fixed && (
                  <Table.Column>
                    <fbt desc="Attribute page: values table column with the slug">Slug</fbt>
                  </Table.Column>
                )}
                <Table.Column type="action">
                  <fbt desc="Attribute page: values table column with each value's buttons">
                    Actions
                  </fbt>
                </Table.Column>
              </Table.Header>
              <Table.Body>
                {rows.map((row, index) => (
                  <Table.Row key={row.key} onClick={row.removed ? undefined : () => edit(row)}>
                    <Table.Cell>
                      <div className="flex min-w-0 flex-col gap-0.5">
                        <span
                          className={
                            row.removed
                              ? "text-base-secondary line-through"
                              : row.name
                                ? undefined
                                : "text-base-secondary"
                          }
                        >
                          {label(row)}
                        </span>
                        {row.removed && (
                          <span className="type-body-sm text-intent-negative">
                            {fbs(
                              [
                                "Deleted on save. ",
                                fbs.plural("product", row.products, {
                                  many: "products",
                                  showCount: "yes",
                                  name: "products",
                                }),
                                " and ",
                                fbs.plural("variant", row.variants, {
                                  many: "variants",
                                  showCount: "yes",
                                  name: "variants",
                                }),
                                " lose it.",
                              ],
                              "Attribute page: below a value in use that saving deletes, e.g. 'Deleted on save. 3 products and 1 variant lose it.'",
                            )}
                          </span>
                        )}
                        {error(row) && (
                          <span role="alert" className="type-body-sm text-intent-negative">
                            {error(row)}
                          </span>
                        )}
                      </div>
                    </Table.Cell>
                    {!fixed && (
                      <Table.Cell>
                        <code className="type-body-sm">{row.slug}</code>
                      </Table.Cell>
                    )}
                    <Table.Cell>
                      {/* Its menu doesn't open the row's slideover. */}
                      <div onClick={(event) => event.stopPropagation()}>
                        <Table.Actions>
                          <Table.ActionsTrigger
                            aria-label={fbs(
                              ["Actions for ", fbs.param("name", label(row))],
                              "Attribute page: accessible name of the button that opens a value's menu, e.g. 'Actions for Cotton'",
                            )}
                          />
                          <Table.ActionsPopup>
                            <HotkeysProvider
                              scope="value-actions-menu"
                              exclusive={overlayExclusive}
                            >
                              {row.removed ? (
                                <Table.ActionsItem
                                  icon={ArrowGoBackLine}
                                  onClick={() => restore(row)}
                                >
                                  <fbt desc="Attribute page: menu item that undoes removing a value before saving">
                                    Keep value
                                  </fbt>
                                </Table.ActionsItem>
                              ) : (
                                <>
                                  <Table.ActionsItem icon={EditLine} onClick={() => edit(row)}>
                                    <fbt desc="Attribute page: menu item that opens the slideover to change a value">
                                      Edit
                                    </fbt>
                                  </Table.ActionsItem>
                                  {!fixed && (
                                    <>
                                      <Table.ActionsItem
                                        icon={ArrowUpLine}
                                        disabled={index === 0}
                                        onClick={() => move(index, -1)}
                                      >
                                        <fbt desc="Attribute page: menu item that moves a value one place up">
                                          Move up
                                        </fbt>
                                      </Table.ActionsItem>
                                      <Table.ActionsItem
                                        icon={ArrowDownLine}
                                        disabled={index === rows.length - 1}
                                        onClick={() => move(index, 1)}
                                      >
                                        <fbt desc="Attribute page: menu item that moves a value one place down">
                                          Move down
                                        </fbt>
                                      </Table.ActionsItem>
                                      <Table.ActionsItem
                                        variant="destructive"
                                        icon={DeleteBinLine}
                                        onClick={() => remove(row)}
                                      >
                                        <fbt desc="Attribute page: menu item that removes a value; saving deletes it">
                                          Remove
                                        </fbt>
                                      </Table.ActionsItem>
                                    </>
                                  )}
                                </>
                              )}
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
              icon={PriceTag3Line}
              title={fbs(
                "No values yet",
                "Attribute page: shown in the values section while the attribute has none",
              )}
              description={fbs(
                "Add the values products and variants pick from, e.g. Cotton or Wool.",
                "Attribute page: explains the empty values section",
              )}
            >
              {add(true)}
            </EmptyState>
          </div>
        </Section.Card>
      )}
      <ValueSlideover
        opened={opened}
        row={editing === "new" ? null : editing}
        type={type}
        open={editing !== null}
        onOpenChange={(open) => !open && setEditing(null)}
        taken={rows.filter((row) => row !== editing).map((row) => row.slug)}
        onApply={(name, slug) => {
          if (editing === "new") {
            const key = crypto.randomUUID();
            change([
              ...rows,
              {
                key,
                id: null,
                name,
                slug,
                fallbackName: null,
                products: 0,
                variants: 0,
                removed: false,
              },
            ]);
          } else if (editing) {
            change(rows.map((row) => (row === editing ? { ...row, name, slug } : row)));
          }
          setEditing(null);
        }}
      />
    </Section.Root>
  );
}

/**
 * Adds a value or changes one on the page; the page's Save stores it. A new value's slug
 * follows its name until the admin edits it.
 */
function ValueSlideover({
  opened,
  open,
  onOpenChange,
  ...props
}: {
  /** Changes with each opening, so the fields start fresh while the slideover stays mounted and animates. */
  opened: number;
  open: boolean;
  onOpenChange: (open: boolean) => void;
} & ValueFormProps) {
  return (
    <Slideover.Root open={open} onOpenChange={onOpenChange}>
      <ValueForm key={opened} {...props} />
    </Slideover.Root>
  );
}

type ValueFormProps = {
  row: Row | null;
  type: AttributeType;
  /** The other values' slugs, which this one can't take. */
  taken: string[];
  onApply: (name: string, slug: string) => void;
};

function ValueForm({ row, type, taken: takenNow, onApply }: ValueFormProps) {
  // As they were when it opened: once applied, the list holds this value too, and checking
  // against that would show an error while the slideover closes.
  const [taken] = useState(takenNow);
  const [name, setName] = useState(row?.name ?? "");
  const [slug, setSlug] = useState(row?.slug ?? "");
  // A new value's slug follows its name until the admin edits it.
  const [coupled, setCoupled] = useState(row === null);
  const [tried, setTried] = useState(false);

  // A yes-or-no value keeps its slug; a number value's name is its number.
  const fixed = type === "boolean";
  const nameError = !tried
    ? undefined
    : name.trim() === "" && !row?.fallbackName
      ? fbs("Enter a name.", "Value slideover: error when the name is empty")
      : type === "number" && name.trim() !== "" && !Number.isFinite(Number(name.trim()))
        ? fbs(
            "Enter a number, e.g. 180 or 2.5.",
            "Value slideover: error when a number attribute's value isn't a number",
          )
        : undefined;
  const slugError =
    !tried || fixed
      ? undefined
      : !/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/.test(slug)
        ? fbs(
            "Use lowercase letters and numbers, joined by - or _.",
            "Value slideover: error when the slug has other characters or is empty",
          )
        : taken.includes(slug)
          ? fbs("Another value has this slug.", "Value slideover: error when the slug is taken")
          : undefined;

  return (
    <form
      onSubmit={(event) => {
        // Not the page's form: this only changes the list.
        event.preventDefault();
        event.stopPropagation();
        setTried(true);
        const named = name.trim() !== "" || !!row?.fallbackName;
        const numeric =
          type !== "number" || name.trim() === "" || Number.isFinite(Number(name.trim()));
        const slugged =
          fixed || (/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/.test(slug) && !taken.includes(slug));
        const valid = named && numeric && slugged;
        if (valid) onApply(name.trim(), slug);
      }}
    >
      <Slideover.Header
        title={
          row === null
            ? fbs("Add value", "Attribute page: title of the slideover that adds a value")
            : fbs("Edit value", "Attribute page: title of the slideover that changes a value")
        }
        description={fbs(
          "Saved with the attribute.",
          "Value slideover: says the change is stored when the attribute page is saved",
        )}
        closeLabel={fbs(
          "Close",
          "Attribute page: accessible name of the button that closes the value slideover",
        )}
      />
      <Slideover.Content>
        <Stack spacing={5}>
          <Field
            label={fbs("Name", "Value slideover: label of the value's name, e.g. Cotton")}
            name="value_name"
            error={nameError}
          >
            <Input
              value={name}
              placeholder={row?.fallbackName ?? (type === "number" ? "180" : undefined)}
              onChange={(event) => {
                setName(event.target.value);
                if (coupled) setSlug(slugify(event.target.value).slice(0, 64).replace(/-+$/, ""));
              }}
              autoComplete="off"
              autoFocus
            />
          </Field>
          {!fixed && (
            <Field
              label={fbs("Slug", "Value slideover: label of the value's slug")}
              name="value_slug"
              error={slugError}
              description={fbs(
                "Storefront filter links use it. Changing it breaks links that use the old one.",
                "Value slideover: hint below the slug",
              )}
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
          )}
        </Stack>
      </Slideover.Content>
      <Slideover.Footer>
        <Slideover.Actions>
          <Slideover.Close>
            {fbs("Cancel", "Value slideover: button that closes it without changes")}
          </Slideover.Close>
          <Button type="submit">
            {row === null
              ? fbs("Add", "Value slideover: button that adds the value to the list")
              : fbs("Apply", "Value slideover: button that applies the changes to the value")}
          </Button>
        </Slideover.Actions>
      </Slideover.Footer>
    </form>
  );
}
