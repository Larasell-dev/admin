import { Form } from "@inertiajs/react";
import { Section, SectionStack } from "@larasell-dev/ui";
import { fbs } from "fbtee";

import CategoryForm, { CategorySections } from "../Components/CategoryForm";
import ContentLocaleSelect from "../../Platform/ContentLocale/Components/ContentLocaleSelect";
import { Page } from "../../Platform/Panel/Components/Page";
import ProductsTable from "../Components/ProductsTable";
import UnsavedChangesToast from "../../Platform/Panel/Components/UnsavedChangesToast";
import CategoryLayout, { CategoryTreeTrigger } from "../Layouts/CategoryLayout";
import SidebarLayout from "../../Platform/Panel/Layouts/SidebarLayout";
import type { SharedProps } from "../../types";

type Props = Larasell.Admin.Category.Data.CreateCategoryPageData &
  Pick<SharedProps, "contentLocale">;

export default function Create({
  categories,
  storeUrl,
  indexUrl,
  parent,
  createProductUrl,
  contentLocale,
  sections,
}: Props) {
  return (
    <>
      <Page.Header
        title={
          parent
            ? fbs(
                [
                  "New category in ",
                  fbs.param("parentCategoryName", parent.name ?? parent.fallbackName),
                ],
                "New category page: title when the new category goes inside another one, whose name is filled in",
              )
            : fbs(
                "New category",
                "New category page: title of the page with the form that creates a top-level category",
              )
        }
        backLink={
          categories.length === 0
            ? {
                href: indexUrl,
                label: fbs(
                  "Back to categories",
                  "New category page: accessible name of the back arrow that returns to the categories",
                ),
                mobileOnly: true,
              }
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
            key={`${parent?.id ?? "root"}-${contentLocale?.current}`}
            action={storeUrl}
            method="post"
          >
            {parent && <input type="hidden" name="parent_id" value={parent.id} />}
            <CategorySections
              sections={sections}
              general={
                <Section.Root>
                  <Section.Header
                    title={fbs(
                      "General",
                      "New category page: heading of the form section with the category's name and description",
                    )}
                    description={fbs(
                      "How the category is named and found in the store.",
                      "New category page: explains the form section with the category's name and description",
                    )}
                  />
                  <CategoryForm />
                </Section.Root>
              }
              products={<ProductsTable mode="create" createProductUrl={createProductUrl} />}
            />
            <UnsavedChangesToast />
          </Form>
        </SectionStack>
      </Page.Body>
    </>
  );
}

Create.layout = [SidebarLayout, CategoryLayout];
