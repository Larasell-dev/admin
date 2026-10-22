import { Form } from "@inertiajs/react";
import { useScrollMaskScrollbar } from "@larasell-dev/ui";
import { useRef } from "react";

import CategoryForm from "../../Components/CategoryForm";
import ProductsTable from "../../Components/ProductsTable";
import { Section, SectionStack } from "../../Components/Section";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import CategoryLayout from "../../Layouts/CategoryLayout";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.CategoryPageData;

export default function Show({
  category,
  updateUrl,
  products,
  availableProductsUrl,
  addProductUrl,
}: Props) {
  const contentScroll = useRef<HTMLDivElement>(null);
  useScrollMaskScrollbar(contentScroll);

  return (
    <>
      <header className="flex h-14 shrink-0 items-center border-b border-border px-6">
        <h2 className="truncate type-body-md text-mauve-950 dark:text-mauve-50">{category.name}</h2>
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
