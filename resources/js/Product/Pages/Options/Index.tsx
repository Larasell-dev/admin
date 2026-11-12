import { router } from "@inertiajs/react";
import { Button, Input, Section, SectionStack } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import { useState } from "react";
import { Page } from "../../../Platform/Panel/Components/Page";
import SidebarLayout from "../../../Platform/Panel/Layouts/SidebarLayout";

type Definition = {
  id: number;
  slug: string;
  name: string;
  display: string;
  products: number;
  values: Array<{ id: number; name: string; swatch: string | null }>;
  addValueUrl: string;
};

/** The catalog's option definitions, e.g. Colour or Size, and their values. */
export default function Index({
  definitions,
  storeUrl,
}: {
  definitions: Definition[];
  storeUrl: string;
}) {
  const [name, setName] = useState("");
  const [display, setDisplay] = useState("dropdown");

  return (
    <>
      <Page.Header
        title={fbs(
          "Options",
          "Options page: title of the page of the catalog's option definitions",
        )}
      />
      <Page.Body>
        <SectionStack>
          {definitions.map((definition) => (
            <DefinitionCard key={definition.id} definition={definition} />
          ))}
          <Section.Root>
            <Section.Header
              title={fbs("New option", "Options page: heading of the form that creates an option")}
              description={fbs(
                "E.g. Colour, Size or Material.",
                "Options page: examples below the heading of the new option form",
              )}
            />
            <div className="flex flex-wrap items-end gap-3">
              <Input
                aria-label={fbs("Name", "Options page: accessible name of the new option's name")}
                placeholder={fbs("Name", "Options page: placeholder of the new option's name")}
                value={name}
                onChange={(event) => setName(event.target.value)}
              />
              <select
                aria-label={fbs(
                  "Display",
                  "Options page: accessible name of how the option shows in the store",
                )}
                className="rounded-md border border-base-secondary bg-transparent px-2 py-1.5 type-body-sm"
                value={display}
                onChange={(event) => setDisplay(event.target.value)}
              >
                <option value="dropdown">
                  {fbs("Dropdown", "Options page: option shown as a dropdown")}
                </option>
                <option value="buttons">
                  {fbs("Buttons", "Options page: option shown as buttons")}
                </option>
                <option value="swatch">
                  {fbs("Swatches", "Options page: option shown as colour swatches")}
                </option>
              </select>
              <Button
                disabled={name.trim() === ""}
                onClick={() =>
                  router.post(
                    storeUrl,
                    {
                      name,
                      display,
                      slug: name
                        .toLowerCase()
                        .trim()
                        .replace(/[^a-z0-9]+/g, "-"),
                    },
                    { preserveScroll: true, onSuccess: () => setName("") },
                  )
                }
              >
                <fbt desc="Options page: button that creates the option">Add option</fbt>
              </Button>
            </div>
          </Section.Root>
        </SectionStack>
      </Page.Body>
    </>
  );
}

function DefinitionCard({ definition }: { definition: Definition }) {
  const [value, setValue] = useState("");
  const [swatch, setSwatch] = useState("#000000");

  return (
    <Section.Root>
      <Section.Header
        title={definition.name}
        description={fbs(
          [
            fbs.param("display", definition.display),
            " · used by ",
            fbs.param("count", String(definition.products)),
            " products",
          ],
          "Options page: how the option shows and how many products use it",
        )}
      />
      <div className="flex flex-wrap items-center gap-2">
        {definition.values.map((v) => (
          <span
            key={v.id}
            className="flex items-center gap-1.5 rounded-full bg-action-neutral-secondary-hover h-6.5 px-2.5 type-body-sm"
          >
            {v.swatch && (
              <span
                className="inline-block size-3 rounded-full border border-base-secondary"
                style={{ background: v.swatch }}
              />
            )}
            {v.name}
          </span>
        ))}
        <Input
          aria-label={fbs(
            "New value",
            "Options page: accessible name of the field for a new value",
          )}
          placeholder={fbs("New value", "Options page: placeholder of the field for a new value")}
          value={value}
          onChange={(event) => setValue(event.target.value)}
        />
        {definition.display === "swatch" && (
          <input
            type="color"
            aria-label={fbs("Swatch", "Options page: colour of the new value")}
            value={swatch}
            onChange={(e) => setSwatch(e.target.value)}
          />
        )}
        <Button
          size="sm"
          variant="secondary"
          disabled={value.trim() === ""}
          onClick={() =>
            router.post(
              definition.addValueUrl,
              { name: value, swatch: definition.display === "swatch" ? swatch : null },
              { preserveScroll: true, onSuccess: () => setValue("") },
            )
          }
        >
          <fbt desc="Options page: button that adds a value to the option">Add value</fbt>
        </Button>
      </div>
    </Section.Root>
  );
}

Index.layout = [SidebarLayout];
