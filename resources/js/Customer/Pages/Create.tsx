import { Form } from "@inertiajs/react";
import { Field, SectionStack, Select } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import { useState } from "react";
import { Page } from "../../Platform/Panel/Components/Page";
import UnsavedChangesToast from "../../Platform/Panel/Components/UnsavedChangesToast";
import SidebarLayout from "../../Platform/Panel/Layouts/SidebarLayout";
import ProfileFields from "../Components/ProfileFields";

type Props = Larasell.Admin.Customer.Data.CreateCustomerPageData;
type Type = "individual" | "company";

export default function Create({ storeUrl, indexUrl, locales, currencies, countries }: Props) {
  const [type, setType] = useState<Type>("individual");
  const types: { value: Type; label: string }[] = [
    {
      value: "individual",
      label: fbs("Individual", "New customer page: option for a customer who is one person"),
    },
    {
      value: "company",
      label: fbs("Company", "New customer page: option for a customer that is a company"),
    },
  ];

  return (
    <>
      <Page.Header
        title={fbs("New customer", "New customer page: title")}
        backLink={{
          href: indexUrl,
          label: fbs("Back to customers", "New customer page: accessible name of the back arrow"),
        }}
      />
      <Page.Body>
        <Form action={storeUrl} method="post">
          <SectionStack>
            <Field
              label={fbs(
                "Type",
                "New customer page: label of the select for whether the customer is a person or a company",
              )}
              name="type"
              description={fbs(
                "It can't change later.",
                "New customer page: hint below the type select",
              )}
            >
              <Select
                items={types}
                itemToKey={(option) => option.value}
                itemToLabel={(option) => option.label}
                value={type}
                onValueChange={(value) => value && setType(value as Type)}
              />
            </Field>
            {/* Remounted per type, so the other type's fields aren't sent. */}
            <ProfileFields
              key={type}
              type={type}
              locales={locales}
              currencies={currencies}
              countries={countries}
            />
          </SectionStack>
          <UnsavedChangesToast />
        </Form>
      </Page.Body>
    </>
  );
}

Create.layout = [SidebarLayout];
