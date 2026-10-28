import { Form } from "@inertiajs/react";
import { Section } from "@larasell-dev/ui";
import CustomerForm from "../../Components/Customers/CustomerForm";
import { Page } from "../../Components/Page";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.Customers.CreateCustomerPageData;

export default function Create({ storeUrl, indexUrl }: Props) {
  return (
    <>
      <Page.Header title="New customer" backLink={{ href: indexUrl, label: "Back to customers" }} />
      <Page.Body>
        <Form action={storeUrl} method="post">
          <Section.Root>
            <Section.Header title="General" description="Who the customer is." />
            <CustomerForm />
          </Section.Root>
          <UnsavedChangesToast />
        </Form>
      </Page.Body>
    </>
  );
}

Create.layout = [SidebarLayout];
