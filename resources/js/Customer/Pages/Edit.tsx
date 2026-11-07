import { Form } from "@inertiajs/react";
import { EmptyState, Section, SectionStack } from "@larasell-dev/ui";
import { ReceiptLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import CustomerForm from "../Components/CustomerForm";
import { Page } from "../../Platform/Panel/Components/Page";
import ResourceTable from "../../Platform/Tables/Components/ResourceTable";
import UnsavedChangesToast from "../../Platform/Panel/Components/UnsavedChangesToast";
import SidebarLayout from "../../Platform/Panel/Layouts/SidebarLayout";

type Props = Larasell.Admin.Customer.Data.EditCustomerPageData;

export default function Edit({ customer, updateUrl, indexUrl, orders }: Props) {
  return (
    <>
      <Page.Header
        title={customer.name}
        backLink={{
          href: indexUrl,
          label: fbs(
            "Back to customers",
            "Customer page: accessible name of the back arrow that returns to the list of customers",
          ),
        }}
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
              <Section.Header
                title={fbs(
                  "General",
                  "Customer page: heading of the form section with the customer's name and contact details",
                )}
                description={fbs(
                  "Who the customer is.",
                  "Customer page: explains the form section with the customer's name and contact details",
                )}
              />
              <CustomerForm customer={customer} />
            </Section.Root>
            <UnsavedChangesToast />
          </Form>
          <Section.Root>
            <Section.Header
              title={orders.title}
              description={fbs(
                "The orders this customer placed.",
                "Customer page: explains the section that lists the customer's orders",
              )}
            />
            <Section.Card>
              {orders.pagination.total > 0 ? (
                <ResourceTable resource={orders} />
              ) : (
                <div className="flex justify-center px-(--inset) py-12">
                  <EmptyState
                    icon={ReceiptLine}
                    title={fbs(
                      "No orders yet",
                      "Customer page: shown instead of the list of the customer's orders when they placed none",
                    )}
                    description={fbs(
                      "Orders this customer places show up here.",
                      "Customer page: below 'No orders yet' in the section for the customer's orders",
                    )}
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
