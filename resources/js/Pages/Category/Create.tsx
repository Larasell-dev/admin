import { Form } from "@inertiajs/react";
import { Section, SectionStack } from "@larasell-dev/ui";

import CategoryForm from "../../Components/Categories/CategoryForm";
import ContentLocaleSelect from "../../Components/ContentLocaleSelect";
import { Page } from "../../Components/Page";
import ProductsTable from "../../Components/ProductsTable";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import CategoryLayout, { CategoryTreeTrigger } from "../../Layouts/Categories/CategoryLayout";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.Categories.CreateCategoryPageData;

export default function Create({
  categories,
  storeUrl,
  indexUrl,
  parent,
  createProductUrl,
  contentLocale,
}: Props) {
  return (
    <>
      <Page.Header
        title={parent ? `New category in ${parent.name ?? parent.fallbackName}` : "New category"}
        backLink={
          categories.length === 0
            ? { href: indexUrl, label: "Back to categories", mobileOnly: true }
            : undefined
        }
        actions={
          <>
            <ContentLocaleSelect contentLocale={contentLocale} />
            {categories.length > 0 && <CategoryTreeTrigger />}
          </>
        }
      />
      <Page.Body>
        <SectionStack>
          <Form
            // Inertia keeps this page mounted when only the parent or the language changes;
            // remounting empties the fields.
            key={`${parent?.id ?? "root"}-${contentLocale.current}`}
            action={storeUrl}
            method="post"
          >
            {parent && <input type="hidden" name="parent_id" value={parent.id} />}
            <Section.Root>
              <Section.Header
                title="General"
                description="How the category is named and found in the store."
              />
              <CategoryForm />
            </Section.Root>
            <UnsavedChangesToast />
          </Form>
          <ProductsTable mode="create" createProductUrl={createProductUrl} />
        </SectionStack>
      </Page.Body>
    </>
  );
}

Create.layout = [SidebarLayout, CategoryLayout];
