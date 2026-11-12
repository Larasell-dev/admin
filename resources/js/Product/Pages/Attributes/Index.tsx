import { router } from "@inertiajs/react";
import { Button, Input, Section, SectionStack } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import { useState } from "react";
import { Page } from "../../../Platform/Panel/Components/Page";
import SidebarLayout from "../../../Platform/Panel/Layouts/SidebarLayout";

type Attribute = {
  id: number;
  slug: string;
  name: string;
  type: string;
  scope: string;
  cardinality: string;
  values: Array<{ id: number; name: string }>;
  updateUrl: string;
  addValueUrl: string;
};

function scopes(): Array<[string, string]> {
  return [
    ["product", fbs("Product only", "Attributes page: scope of an attribute only the product has")],
    [
      "inherited",
      fbs(
        "Product, inherited by variants",
        "Attributes page: scope of an attribute variants inherit, override or clear",
      ),
    ],
    ["variant", fbs("Variants only", "Attributes page: scope of an attribute only variants have")],
  ];
}

function ScopeSelect({ value, onChange }: { value: string; onChange: (scope: string) => void }) {
  return (
    <select
      aria-label={fbs(
        "Describes",
        "Attributes page: accessible name of what the attribute describes",
      )}
      className="rounded-md border border-base-secondary bg-transparent px-2 py-1.5 type-body-sm"
      value={value}
      onChange={(event) => onChange(event.target.value)}
    >
      {scopes().map(([key, label]) => (
        <option key={key} value={key}>
          {label}
        </option>
      ))}
    </select>
  );
}

/** The catalog's attributes, which describe products and variants without creating variants. */
export default function Index({
  attributes,
  storeUrl,
}: {
  attributes: Attribute[];
  storeUrl: string;
}) {
  const [name, setName] = useState("");
  const [scope, setScope] = useState("product");

  return (
    <>
      <Page.Header
        title={fbs("Attributes", "Attributes page: title of the page of the catalog's attributes")}
      />
      <Page.Body>
        <SectionStack>
          {attributes.map((attribute) => (
            <AttributeCard key={attribute.id} attribute={attribute} />
          ))}
          <Section.Root>
            <Section.Header
              title={fbs(
                "New attribute",
                "Attributes page: heading of the form that creates an attribute",
              )}
              description={fbs(
                "E.g. Material, Care or Voltage.",
                "Attributes page: examples below the heading of the new attribute form",
              )}
            />
            <div className="flex flex-wrap items-end gap-3">
              <Input
                aria-label={fbs(
                  "Name",
                  "Attributes page: accessible name of the new attribute's name",
                )}
                placeholder={fbs(
                  "Name",
                  "Attributes page: placeholder of the new attribute's name",
                )}
                value={name}
                onChange={(event) => setName(event.target.value)}
              />
              <ScopeSelect value={scope} onChange={setScope} />
              <Button
                disabled={name.trim() === ""}
                onClick={() =>
                  router.post(
                    storeUrl,
                    {
                      name,
                      scope,
                      slug: name
                        .toLowerCase()
                        .trim()
                        .replace(/[^a-z0-9]+/g, "-"),
                    },
                    { preserveScroll: true, onSuccess: () => setName("") },
                  )
                }
              >
                <fbt desc="Attributes page: button that creates the attribute">Add attribute</fbt>
              </Button>
            </div>
          </Section.Root>
        </SectionStack>
      </Page.Body>
    </>
  );
}

function AttributeCard({ attribute }: { attribute: Attribute }) {
  const [value, setValue] = useState("");

  return (
    <Section.Root>
      <Section.Header title={attribute.name} description={attribute.type} />
      <div className="flex flex-col gap-3">
        <ScopeSelect
          value={attribute.scope}
          onChange={(scope) => router.put(attribute.updateUrl, { scope }, { preserveScroll: true })}
        />
        <label className="flex items-center gap-2 type-body-sm">
          <input
            type="checkbox"
            checked={attribute.cardinality === "multiple"}
            onChange={(event) =>
              router.put(
                attribute.updateUrl,
                { cardinality: event.target.checked ? "multiple" : "single" },
                { preserveScroll: true },
              )
            }
          />
          <fbt desc="Attributes page: checkbox that lets products and variants have several values of the attribute, e.g. a blend of materials">
            Several values per product or variant
          </fbt>
        </label>
        <div className="flex flex-wrap items-center gap-2">
          {attribute.values.map((v) => (
            <span
              key={v.id}
              className="inline-flex h-6.5 items-center rounded-full bg-action-neutral-secondary-hover px-2.5 type-body-sm"
            >
              {v.name}
            </span>
          ))}
          <Input
            aria-label={fbs(
              "New value",
              "Attributes page: accessible name of the field for a new value",
            )}
            placeholder={fbs(
              "New value",
              "Attributes page: placeholder of the field for a new value",
            )}
            value={value}
            onChange={(event) => setValue(event.target.value)}
          />
          <Button
            size="sm"
            variant="secondary"
            disabled={value.trim() === ""}
            onClick={() =>
              router.post(
                attribute.addValueUrl,
                { name: value },
                { preserveScroll: true, onSuccess: () => setValue("") },
              )
            }
          >
            <fbt desc="Attributes page: button that adds a value to the attribute">Add value</fbt>
          </Button>
        </div>
      </div>
    </Section.Root>
  );
}

Index.layout = [SidebarLayout];
