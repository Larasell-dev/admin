import { Form } from "@inertiajs/react";
import { Section } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import BrandForm from "../Components/BrandForm";
import { Page } from "../../Platform/Panel/Components/Page";
import UnsavedChangesToast from "../../Platform/Panel/Components/UnsavedChangesToast";
import SidebarLayout from "../../Platform/Panel/Layouts/SidebarLayout";

type Props = Larasell.Admin.Brand.Data.CreateBrandPageData;

export default function Create({ storeUrl, indexUrl }: Props) {
  return (
    <>
      <Page.Header
        title={fbs("New brand", "New brand page: title")}
        backLink={{
          href: indexUrl,
          label: fbs(
            "Back to brands",
            "New brand page: accessible name of the back arrow to the brands",
          ),
        }}
      />
      <Page.Body>
        <Form action={storeUrl} method="post">
          <Section.Root>
            <Section.Header
              title={fbs("General", "New brand page: heading of the brand's fields")}
              description={fbs(
                "The name products are marketed under.",
                "New brand page: explains the section with the brand's fields",
              )}
            />
            <BrandForm />
          </Section.Root>
          <UnsavedChangesToast />
        </Form>
      </Page.Body>
    </>
  );
}

Create.layout = [SidebarLayout];
