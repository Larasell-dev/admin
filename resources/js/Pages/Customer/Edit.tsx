import { Form } from "@inertiajs/react";
import { Section } from "@larasell-dev/ui";
import CustomerForm from "../../Components/CustomerForm";
import { Page } from "../../Components/Page";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.EditCustomerPageData;

export default function Edit({ customer, updateUrl, indexUrl }: Props) {
  return (
    <>
      <Page.Header
        title={customer.name}
        backLink={{ href: indexUrl, label: "Back to customers" }}
      />
      <Page.Body>
        <Form
          // Inertia keeps this page mounted when moving between customers; remounting resets the fields.
          key={updateUrl}
          action={updateUrl}
          method="put"
          // The saved values become the new baseline, so the form is no longer dirty.
          setDefaultsOnSuccess
        >
          <Section.Root>
            <Section.Header title="General" description="Who the customer is." />
            <CustomerForm customer={customer} />
          </Section.Root>
          <UnsavedChangesToast />
        </Form>
      </Page.Body>
    </>
  );
}

Edit.layout = [SidebarLayout];
