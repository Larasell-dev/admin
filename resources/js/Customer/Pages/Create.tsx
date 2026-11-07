import { Form } from "@inertiajs/react";
import { Section } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import CustomerForm from "../Components/CustomerForm";
import { Page } from "../../Platform/Panel/Components/Page";
import UnsavedChangesToast from "../../Platform/Panel/Components/UnsavedChangesToast";
import SidebarLayout from "../../Platform/Panel/Layouts/SidebarLayout";

type Props = Larasell.Admin.Customer.Data.CreateCustomerPageData;

export default function Create({ storeUrl, indexUrl }: Props) {
  return (
    <>
      <Page.Header
        title={fbs(
          "New customer",
          "New customer page: title of the page with the form that adds a customer",
        )}
        backLink={{
          href: indexUrl,
          label: fbs(
            "Back to customers",
            "New customer page: accessible name of the back arrow that returns to the list of customers",
          ),
        }}
      />
      <Page.Body>
        <Form action={storeUrl} method="post">
          <Section.Root>
            <Section.Header
              title={fbs(
                "General",
                "New customer page: heading of the form section with the customer's name and contact details",
              )}
              description={fbs(
                "Who the customer is.",
                "New customer page: explains the form section with the customer's name and contact details",
              )}
            />
            <CustomerForm />
          </Section.Root>
          <UnsavedChangesToast />
        </Form>
      </Page.Body>
    </>
  );
}

Create.layout = [SidebarLayout];
