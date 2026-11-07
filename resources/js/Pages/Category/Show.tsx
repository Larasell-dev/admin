import { Form, Link } from "@inertiajs/react";
import { Button, EmptyState, Icon, Section, SectionStack } from "@larasell-dev/ui";
import { AddLine, FolderUnknowLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";

import CategoryForm, { CategorySections } from "../../Components/Category/CategoryForm";
import ContentLocaleSelect from "../../Components/ContentLocale/ContentLocaleSelect";
import { Page } from "../../Components/Page";
import ProductsTable from "../../Components/Category/ProductsTable";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import CategoryLayout, { CategoryTreeTrigger } from "../../Layouts/Category/CategoryLayout";
import SidebarLayout from "../../Layouts/SidebarLayout";
import type { SharedProps } from "../../types";

// The server sends a null category, and none of its urls, when the address names one that is gone.
type Props = (
  | Larasell.Admin.Category.Data.CategoryPageData
  | Larasell.Admin.Category.Data.MissingCategoryPageData
) &
  Pick<SharedProps, "contentLocale">;

export default function Show(props: Props) {
  return (
    <>
      <Page.Header
        title={
          props.category === null
            ? fbs(
                "Category not found",
                "Category page: title when the address names a category that no longer exists",
              )
            : (props.category.name ?? props.category.fallbackName)
        }
        actions={
          <>
            {props.category !== null && <ContentLocaleSelect contentLocale={props.contentLocale} />}
            {props.categories.length > 0 && <CategoryTreeTrigger />}
          </>
        }
      />
      {props.category === null ? (
        <CategoryNotFound {...props} />
      ) : (
        <Page.Body>
          <SectionStack>
            <Form
              // Inertia keeps this page mounted when moving between categories or languages, and the
              // tree's menu can hide or unhide this one; remounting resets the fields to the new values.
              key={`${props.category.id}-${props.category.visible}-${props.contentLocale?.current}`}
              action={props.updateUrl}
              method="put"
              // The saved values become the new baseline, so the form is no longer dirty.
              setDefaultsOnSuccess
            >
              <CategorySections
                sections={props.sections}
                general={
                  <Section.Root>
                    <Section.Header
                      title={fbs(
                        "General",
                        "Category page: heading of the form section with the category's name and description",
                      )}
                      description={fbs(
                        "How the category is named and found in the store.",
                        "Category page: explains the form section with the category's name and description",
                      )}
                    />
                    <CategoryForm category={props.category} />
                  </Section.Root>
                }
              />
              <UnsavedChangesToast />
            </Form>
            <ProductsTable
              mode="edit"
              table={props.products}
              availableProductsUrl={props.availableProductsUrl}
              addProductUrl={props.addProductUrl}
              createProductUrl={props.createProductUrl}
            />
          </SectionStack>
        </Page.Body>
      )}
    </>
  );
}

function CategoryNotFound({
  categories,
  createUrl,
}: Larasell.Admin.Category.Data.MissingCategoryPageData) {
  return (
    <div className="flex flex-1 items-center justify-center px-6 py-12">
      <EmptyState
        icon={FolderUnknowLine}
        title={fbs(
          "This category doesn't exist anymore",
          "Category page: shown when the address names a category that was deleted or never existed",
        )}
        description={
          categories.length > 0
            ? fbs(
                "It may have been deleted, or the link you followed is out of date. Pick another category from the list or add a new one.",
                "Category page: explains why a category is missing, when other categories exist to pick from the tree",
              )
            : fbs(
                "It may have been deleted, or the link you followed is out of date. Add a category to start organizing your products.",
                "Category page: explains why a category is missing, when the store has no categories left",
              )
        }
      >
        <Button render={<Link href={createUrl} prefetch />}>
          <Icon icon={AddLine} />
          <fbt desc="Category page, when the category is missing: button that opens the form to create a new category">
            Add category
          </fbt>
        </Button>
      </EmptyState>
    </div>
  );
}

Show.layout = [SidebarLayout, CategoryLayout];
