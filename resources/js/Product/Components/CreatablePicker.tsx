import { router, useFormContext } from "@inertiajs/react";
import { Button, Field, Select, Slideover } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import { documentCookies } from "../../Platform/Support/cookies";
import { type FormEvent, type ReactNode, useEffect, useRef, useState } from "react";

type Option = {
  id: number;
  name: string;
  /** Its saved fields as its form names them, to change it in the slide-over. */
  fields: PendingFields;
  /** How many products it's on, all of which a change applies to. */
  products: number;
};
type Errors = Record<string, string | undefined>;
/** A record's fields as its form names them, e.g. `address[street]`. */
export type PendingFields = Record<string, string>;

const NEW = "new";
const CREATE = "create";

/**
 * A select of existing records, such as the product's brand, that also adds a new one and changes
 * the picked one. Both are filled in a slide-over, checked by the server at `checkUrl` (which
 * saves nothing) and sent with the surrounding Inertia `<Form>`: a new one as `{newPrefix}[…]` with
 * `{name}` set to "new", changes to the picked one as `{changesPrefix}[…]`. The server saves them
 * with the record, so nothing is stored until then. Both are kept, to pick again, until the form
 * saves or its changes are discarded, and only the picked one's are sent. The server's errors on
 * them show at the select, with a button that opens them in the slide-over.
 */
export default function CreatablePicker({
  name,
  newPrefix,
  changesPrefix,
  checkUrl,
  label,
  description,
  options,
  defaultValue,
  texts,
  existing,
  fields,
}: {
  /** The id's field, e.g. brand_id. */
  name: string;
  /** Where the new record's fields go, e.g. new_brand. */
  newPrefix: string;
  /** Where changes to the picked record go, e.g. brand_changes. */
  changesPrefix: string;
  /** Checks the slide-over's fields, with the changed record's id in `{name}` without `_id`. */
  checkUrl: string;
  label: string;
  /** A hint below the select for the value picked, "" for none. */
  description?: (value: string) => string | undefined;
  options: Option[];
  defaultValue: number | null;
  texts: {
    create: string;
    title: string;
    editTitle: string;
    explanation: string;
    /** Tells what changing a record used by this many products does. */
    used: (products: number) => string;
    add: string;
    apply: string;
    /** The text of the button in each row that opens its record. */
    edit: string;
    /** That button's accessible name, with the row's. */
    editItem: (name: string) => string;
    invalid: string;
    fix: string;
    use: (name: string) => string;
  };
  /** An existing record the new one duplicates, offered instead. */
  existing: (fields: PendingFields) => Pick<Option, "id" | "name"> | undefined;
  /** The slide-over's fields, filled with the record's and showing their errors. */
  fields: (filled: PendingFields | null, errors: Errors) => ReactNode;
}) {
  const saved = String(defaultValue ?? "");
  const [value, setValue] = useState(saved);
  const [pending, setPending] = useState<PendingFields | null>(null);
  /** Changes to existing records, by id. */
  const [changes, setChanges] = useState<Record<string, PendingFields>>({});
  /** What the slide-over shows: CREATE to add a record, NEW for the added one, or a record's id. */
  // Kept once it closes, so its title and fields don't change while it animates out.
  const [editing, setEditing] = useState<string | null>(null);
  const [isOpen, setIsOpen] = useState(false);
  const [checkErrors, setCheckErrors] = useState<Errors | null>(null);
  const [checking, setChecking] = useState(false);
  const hidden = useRef<HTMLDivElement>(null);
  const formErrors: Errors = useFormContext()?.errors ?? {};
  const isNew = value === NEW && pending !== null;
  const changed = value !== NEW ? changes[value] : undefined;
  const errorsUnder = (prefix: string) =>
    Object.fromEntries(
      Object.entries(formErrors)
        .filter(([key]) => key.startsWith(`${prefix}.`))
        .map(([key, message]) => [key.slice(prefix.length + 1), message]),
    );
  // The server's errors on what's picked: the added record, or the changes to an existing one.
  const fieldErrors = isNew ? errorsUnder(newPrefix) : changed ? errorsUnder(changesPrefix) : {};
  const hasFieldErrors = Object.keys(fieldErrors).length > 0;
  const duplicate = isNew ? existing(pending) : undefined;
  const option = (id: string) => options.find((each) => String(each.id) === id);
  const editedOption =
    editing !== null && editing !== CREATE && editing !== NEW ? option(editing) : undefined;

  const drop = () => {
    setValue(saved);
    setPending(null);
    setChanges({});
  };

  // Once saved, the product has the new record, or another: start again from what it has.
  const [lastSaved, setLastSaved] = useState(saved);
  if (saved !== lastSaved) {
    setLastSaved(saved);
    setValue(saved);
    setPending(null);
    setChanges({});
  }

  // Discarding the form's changes drops the new record and the changes too.
  useEffect(() => {
    const form = hidden.current?.closest("form");
    form?.addEventListener("reset", drop);
    // Saved: the records have them now, or weren't picked, so they're gone either way.
    const stop = router.on("success", () => {
      setPending(null);
      setChanges({});
    });
    return () => {
      form?.removeEventListener("reset", drop);
      stop();
    };
  });

  // Hidden inputs fire no input event, so tell the form its data changed.
  useEffect(() => {
    hidden.current?.dispatchEvent(new Event("input", { bubbles: true }));
  }, [value, pending, changes]);

  const open = (what: string) => {
    setCheckErrors(null);
    setEditing(what);
    setIsOpen(true);
  };

  const pick = (next: string) => {
    if (next === CREATE) {
      open(CREATE);
      return;
    }
    setValue(next);
  };

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    // The slide-over renders in a portal, but React bubbles its submit to the product's form.
    event.stopPropagation();
    const what = editing;
    const filled = Object.fromEntries(
      [...new FormData(event.currentTarget).entries()].filter(
        (entry): entry is [string, string] => typeof entry[1] === "string",
      ),
    );
    const body = new FormData(event.currentTarget);
    if (editedOption) {
      body.set(name.replace(/_id$/, ""), String(editedOption.id));
    }

    setChecking(true);
    const errors = await check(checkUrl, body).finally(() => setChecking(false));
    if (errors) {
      setCheckErrors(errors);
      return;
    }

    if (what === CREATE || what === NEW) {
      setPending(filled);
      setValue(NEW);
    } else if (what !== null) {
      setChanges((current) => ({ ...current, [what]: filled }));
    }
    setIsOpen(false);
  };

  const filled =
    editing === CREATE || editing === NEW
      ? pending
      : editedOption
        ? (changes[editing!] ?? editedOption.fields)
        : null;
  // The check's errors, else the server's from saving, for what's picked.
  const showsPicked = editing === CREATE || editing === NEW ? isNew : editing === value;
  const slideErrors = checkErrors ?? (showsPicked ? fieldErrors : {});
  const displayName = (each: Option) => changes[String(each.id)]?.name || each.name;

  return (
    <div ref={hidden}>
      <Field
        label={label}
        name={name}
        error={formErrors[name] ?? (hasFieldErrors ? texts.invalid : undefined)}
        description={description?.(value)}
      >
        <Select
          items={[
            { id: "", name: "—" },
            ...options.map((each) => ({ id: String(each.id), name: displayName(each) })),
            ...(pending ? [{ id: NEW, name: pending.name ?? "" }] : []),
            { id: CREATE, name: texts.create },
          ]}
          itemToKey={(item) => item.id}
          itemToLabel={(item) => item.name}
          value={value}
          onValueChange={(next) => pick(next ?? "")}
          renderItem={(item) =>
            item.id === "" || item.id === CREATE ? (
              item.name
            ) : (
              <span className="flex items-center justify-between gap-2">
                <span className="min-w-0 truncate">{item.name}</span>
                {/* Opens the row's record without picking it; shows on hover or while highlighted. */}
                <button
                  type="button"
                  aria-label={texts.editItem(item.name)}
                  className="type-input-md text-base-secondary hover:text-base-primary -my-2 cursor-pointer py-2 opacity-0 group-hover:opacity-100 group-data-highlighted:opacity-100 focus-visible:opacity-100"
                  onPointerDown={(event) => event.stopPropagation()}
                  onClick={(event) => {
                    event.stopPropagation();
                    event.preventDefault();
                    open(item.id);
                  }}
                >
                  {texts.edit}
                </button>
              </span>
            )
          }
        />
      </Field>
      {(hasFieldErrors || duplicate) && (
        <div className="flex flex-wrap gap-2 pt-2">
          {hasFieldErrors && (
            <Button variant="tertiary" size="sm" type="button" onClick={() => open(value)}>
              {texts.fix}
            </Button>
          )}
          {duplicate && (
            <Button
              variant="tertiary"
              size="sm"
              type="button"
              onClick={() => pick(String(duplicate.id))}
            >
              {texts.use(duplicate.name)}
            </Button>
          )}
        </div>
      )}
      {isNew &&
        Object.entries(pending).map(([key, entry]) => (
          <input key={key} type="hidden" name={nested(newPrefix, key)} value={entry} />
        ))}
      {changed &&
        Object.entries(changed).map(([key, entry]) => (
          <input key={key} type="hidden" name={nested(changesPrefix, key)} value={entry} />
        ))}
      <Slideover.Root open={isOpen} onOpenChange={setIsOpen}>
        <form onSubmit={submit} noValidate>
          <Slideover.Header
            title={editing === CREATE ? texts.title : texts.editTitle}
            description={editedOption ? texts.used(editedOption.products) : texts.explanation}
            closeLabel={fbs(
              "Close",
              "Product form: accessible name of the button that closes the slide-over adding a brand or manufacturer",
            )}
          />
          <Slideover.Content>
            {/* Remounts with the record's fields, so they start from them. */}
            <div key={`${editing}:${JSON.stringify(filled)}`}>{fields(filled, slideErrors)}</div>
          </Slideover.Content>
          <Slideover.Footer>
            <Slideover.Actions>
              <Slideover.Close>
                {fbs(
                  "Cancel",
                  "Product form: button that closes the slide-over adding a brand or manufacturer without adding it",
                )}
              </Slideover.Close>
              <Button type="submit" {...(checking ? { loading: true } : {})}>
                {editing === CREATE ? texts.add : texts.apply}
              </Button>
            </Slideover.Actions>
          </Slideover.Footer>
        </form>
      </Slideover.Root>
    </div>
  );
}

/**
 * Asks the server whether the fields are valid; its errors by field, e.g. `address.city`, or null.
 * Anything but a 422 lets them through: saving the product checks them again.
 */
async function check(url: string, body: FormData): Promise<Errors | null> {
  const response = await fetch(url, {
    method: "POST",
    body,
    headers: {
      Accept: "application/json",
      "X-XSRF-TOKEN": documentCookies.get("XSRF-TOKEN") ?? "",
    },
  }).catch(() => null);
  if (response?.status !== 422) {
    return null;
  }
  const { errors } = (await response.json()) as { errors: Record<string, string[]> };
  return Object.fromEntries(Object.entries(errors).map(([key, messages]) => [key, messages[0]]));
}

/** `address[street]` under new_manufacturer → `new_manufacturer[address][street]`. */
function nested(prefix: string, key: string) {
  const bracket = key.indexOf("[");
  return bracket < 0
    ? `${prefix}[${key}]`
    : `${prefix}[${key.slice(0, bracket)}]${key.slice(bracket)}`;
}
