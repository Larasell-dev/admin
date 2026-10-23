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

  if (props.category === null) {
    const { categories, createUrl } = props;

    return (
      <>
        <header className="flex h-14 shrink-0 items-center justify-between gap-4 border-b border-border px-6">
          <h2 className="truncate type-body-md text-mauve-950 dark:text-mauve-50">
            Category not found
          </h2>
          {categories.length > 0 && <CategoryTreeTrigger />}
        </header>
        <div className="flex flex-1 items-center justify-center px-6 py-12">
          <EmptyState
            icon={FolderUnknowLine}
            title="This category doesn't exist anymore"
            description={
              categories.length > 0
                ? "It may have been deleted, or the link you followed is out of date. Pick another category from the list or add a new one."
                : "It may have been deleted, or the link you followed is out of date. Add a category to start organizing your products."
            }
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
      </>
    );
  }

  const { category, updateUrl, products, availableProductsUrl, addProductUrl } = props;

  return (
    <>
      <header className="flex h-14 shrink-0 items-center justify-between gap-4 border-b border-border px-6">
        <h2 className="truncate type-body-md text-mauve-950 dark:text-mauve-50">{category.name}</h2>
        <CategoryTreeTrigger />
      </header>
      <div
        ref={contentScroll}
        className="scroll-mask-y-from-95% min-h-0 flex-1 overflow-y-auto px-6 py-6"
      >
        <div className="max-w-2xl">
          <SectionStack>
            <Form
              // Inertia keeps this page mounted when moving between categories, and the tree's
              // menu can hide or unhide this one; remounting resets the fields to the new values.
              key={`${category.id}-${category.visible}`}
              action={updateUrl}
              method="put"
              // The saved values become the new baseline, so the form is no longer dirty.
              setDefaultsOnSuccess
            >
              <Section.Root>
                <Section.Header
                  title="General"
                  description="How the category is named and found in the store."
                />
                <CategoryForm category={category} />
              </Section.Root>
              <UnsavedChangesToast container={contentScroll} />
            </Form>
            <ProductsTable
              mode="edit"
              table={products}
              availableProductsUrl={availableProductsUrl}
              addProductUrl={addProductUrl}
            />
          </SectionStack>
        </div>
      </div>
    </>
  );
}

Show.layout = [SidebarLayout, CategoryLayout];
