import { Form } from "@inertiajs/react";
import { Section, SectionStack } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import AttributeForm, { type AttributeType } from "../../Components/AttributeForm";
import { useState } from "react";
import AttributeValues from "../../Components/AttributeValues";
import { Page } from "../../../Platform/Panel/Components/Page";
import UnsavedChangesToast from "../../../Platform/Panel/Components/UnsavedChangesToast";
import SidebarLayout from "../../../Platform/Panel/Layouts/SidebarLayout";

type Props = Larasell.Admin.Product.Data.CreateAttributePageData;

export default function Create({ storeUrl, indexUrl, scopes }: Props) {
  const [type, setType] = useState<AttributeType>("text");
  return (
    <>
      <Page.Header
        title={fbs("New attribute", "New attribute page: title")}
        backLink={{
          href: indexUrl,
          label: fbs(
            "Back to attributes",
            "New attribute page: accessible name of the back arrow to the list of attributes",
          ),
        }}
      />
      <Page.Body>
        <Form action={storeUrl} method="post">
          <SectionStack>
            <Section.Root>
              <Section.Header
                title={fbs("General", "New attribute page: heading of the attribute's fields")}
                description={fbs(
                  "E.g. Material, Care or Fit.",
                  "New attribute page: explains the section with the attribute's fields",
                )}
              />
              <AttributeForm scopes={scopes} type={type} onTypeChange={setType} />
            </Section.Root>
            {/* Starts over with the type: a yes-or-no attribute comes with its yes and no. */}
            <AttributeValues key={type} type={type} values={[]} />
          </SectionStack>
          <UnsavedChangesToast />
        </Form>
      </Page.Body>
    </>
  );
}

Create.layout = [SidebarLayout];
