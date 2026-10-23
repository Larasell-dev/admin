import { Form, Link } from "@inertiajs/react";
import {
  Button,
  EmptyState,
  Icon,
  Section,
  SectionStack,
  useScrollMaskScrollbar,
} from "@larasell-dev/ui";
import { AddLine, FolderUnknowLine } from "@larasell-dev/ui/icons";
import { useRef } from "react";

import CategoryForm from "../../Components/CategoryForm";
import ProductsTable from "../../Components/ProductsTable";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import CategoryLayout, { CategoryTreeTrigger } from "../../Layouts/CategoryLayout";
import SidebarLayout from "../../Layouts/SidebarLayout";

// The server sends a null category, and none of its urls, when the address names one that is gone.
type Props =
  | Larasell.Admin.Data.View.CategoryPageData
  | Larasell.Admin.Data.View.MissingCategoryPageData;

export default function Show(props: Props) {
  const contentScroll = useRef<HTMLDivElement>(null);
  useScrollMaskScrollbar(contentScroll);

  return (
    <>
      <header className="flex h-14 shrink-0 items-center justify-between gap-4 border-b border-border px-6">
        <h2 className="truncate type-body-md text-mauve-950 dark:text-mauve-50">
          {props.category?.name ?? "Category not found"}
        </h2>
        {props.categories.length > 0 && <CategoryTreeTrigger />}
      </header>
      {props.category === null ? (
        <CategoryNotFound {...props} />
      ) : (
        <div
          ref={contentScroll}
          className="scroll-mask-y-from-95% min-h-0 flex-1 overflow-y-auto px-6 py-6"
        >
          <div className="max-w-2xl">
            <SectionStack>
              <Form
                // Inertia keeps this page mounted when moving between categories, and the tree's
                // menu can hide or unhide this one; remounting resets the fields to the new values.
                key={`${props.category.id}-${props.category.visible}`}
                action={props.updateUrl}
                method="put"
                // The saved values become the new baseline, so the form is no longer dirty.
                setDefaultsOnSuccess
              >
                <Section.Root>
                  <Section.Header
                    title="General"
                    description="How the category is named and found in the store."
                  />
                  <CategoryForm category={props.category} />
                </Section.Root>
                <UnsavedChangesToast container={contentScroll} />
              </Form>
              <ProductsTable
                mode="edit"
                table={props.products}
                availableProductsUrl={props.availableProductsUrl}
                addProductUrl={props.addProductUrl}
              />
            </SectionStack>
          </div>
        </div>
      )}
    </>
  );
}

function CategoryNotFound({
  categories,
  createUrl,
}: Larasell.Admin.Data.View.MissingCategoryPageData) {
  return (
    <div className="flex flex-1 items-center justify-center px-6 py-12">
      <EmptyState
        icon={FolderUnknowLine}
        title="This category doesn't exist anymore"
        description={`It may have been deleted, or the link you followed is out of date. ${
          categories.length > 0
            ? "Pick another category from the list or add a new one."
            : "Add a category to start organizing your products."
        }`}
      >
        <Button
          nativeButton={false}
          // Base UI marks non-native buttons as buttons; this navigates, so it stays a link.
          role="link"
          render={<Link href={createUrl} prefetch />}
        >
          <Icon icon={AddLine} />
          Add category
        </Button>
      </EmptyState>
    </div>
  );
}

Show.layout = [SidebarLayout, CategoryLayout];
