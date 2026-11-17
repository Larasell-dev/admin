import { Form } from "@inertiajs/react";
import { Section, SectionStack } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import ManufacturerForm from "../Components/ManufacturerForm";
import { Page } from "../../Platform/Panel/Components/Page";
import UnsavedChangesToast from "../../Platform/Panel/Components/UnsavedChangesToast";
import SidebarLayout from "../../Platform/Panel/Layouts/SidebarLayout";

type Props = Larasell.Admin.Manufacturer.Data.CreateManufacturerPageData;

export default function Create({ countries, storeUrl, indexUrl }: Props) {
  return (
    <>
      <Page.Header
        title={fbs("New manufacturer", "New manufacturer page: title")}
        backLink={{
          href: indexUrl,
          label: fbs(
            "Back to manufacturers",
            "New manufacturer page: accessible name of the back arrow to the manufacturers",
          ),
        }}
      />
      <Page.Body>
        <Form action={storeUrl} method="post">
          <SectionStack>
            <ManufacturerSections countries={countries} />
          </SectionStack>
          <UnsavedChangesToast />
        </Form>
      </Page.Body>
    </>
  );
}

Create.layout = [SidebarLayout];

/** The manufacturer's own section and its responsible person's, on both pages. */
export function ManufacturerSections({
  manufacturer,
  countries,
}: {
  manufacturer?: Larasell.Admin.Manufacturer.Data.ManufacturerDetailData;
  countries: Larasell.Admin.Manufacturer.Data.CountryOptionData[];
}) {
  return (
    <>
      <Section.Root>
        <Section.Header
          title={fbs("General", "Manufacturer page: heading of the manufacturer's fields")}
          description={fbs(
            "The legal producer that product pages in the EU name, with its postal and email address (GPSR).",
            "Manufacturer page: explains the section with the manufacturer's fields",
          )}
        />
        <ManufacturerForm manufacturer={manufacturer} countries={countries} part="manufacturer" />
      </Section.Root>
      <Section.Root>
        <Section.Header
          title={fbs(
            "EU responsible person",
            "Manufacturer page: heading of the section with the manufacturer's representative in the EU",
          )}
          description={fbs(
            "Needed when the manufacturer is outside the EU. Leave the name empty for none.",
            "Manufacturer page: explains the EU responsible person section",
          )}
        />
        <ManufacturerForm
          manufacturer={manufacturer}
          countries={countries}
          part="responsible-person"
        />
      </Section.Root>
    </>
  );
}
