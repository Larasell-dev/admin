import { Form } from "@inertiajs/react";
import { EmptyState, Section, SectionStack } from "@larasell-dev/ui";
import { ReceiptLine } from "@larasell-dev/ui/icons";
import CustomerForm from "../../Components/Customers/CustomerForm";
import { Page } from "../../Components/Page";
import ResourceTable from "../../Components/ResourceTable";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.Customers.EditCustomerPageData;

export default function Edit({ customer, updateUrl, indexUrl, orders }: Props) {
  return (
    <>
      <Page.Header
        title={customer.name}
        backLink={{ href: indexUrl, label: "Back to customers" }}
      />
      <Page.Body>
        <SectionStack>
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
          <Section.Root>
            <Section.Header title={orders.title} description="The orders this customer placed." />
            <Section.Card>
              {orders.pagination.total > 0 ? (
                <ResourceTable resource={orders} />
              ) : (
                <div className="flex justify-center px-(--inset) py-12">
                  <EmptyState
                    icon={ReceiptLine}
                    title="No orders yet"
                    description="Orders this customer places show up here."
                  />
                </div>
              )}
            </Section.Card>
          </Section.Root>
        </SectionStack>
      </Page.Body>
    </>
  );
}

Edit.layout = [SidebarLayout];
