import { Form } from "@inertiajs/react";
import { Section, SectionStack } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import { useState } from "react";
import OptionForm, { type OptionDisplay } from "../../Components/OptionForm";
import OptionValues from "../../Components/OptionValues";
import { Page } from "../../../Platform/Panel/Components/Page";
import UnsavedChangesToast from "../../../Platform/Panel/Components/UnsavedChangesToast";
import SidebarLayout from "../../../Platform/Panel/Layouts/SidebarLayout";

type Props = Larasell.Admin.Product.Data.CreateOptionPageData;

export default function Create({ storeUrl, indexUrl, displays }: Props) {
  const [display, setDisplay] = useState<OptionDisplay>("dropdown");
  return (
    <>
      <Page.Header
        title={fbs("New option", "New option page: title")}
        backLink={{
          href: indexUrl,
          label: fbs(
            "Back to options",
            "New option page: accessible name of the back arrow to the list of options",
          ),
        }}
      />
      <Page.Body>
        <Form action={storeUrl} method="post">
          <SectionStack>
            <Section.Root>
              <Section.Header
                title={fbs("General", "New option page: heading of the option's fields")}
                description={fbs(
                  "E.g. Colour, Size or Material.",
                  "New option page: explains the section with the option's fields",
                )}
              />
              <OptionForm displays={displays} display={display} onDisplayChange={setDisplay} />
            </Section.Root>
            <OptionValues values={[]} swatches={display === "swatch"} />
          </SectionStack>
          <UnsavedChangesToast />
        </Form>
      </Page.Body>
    </>
  );
}

Create.layout = [SidebarLayout];
