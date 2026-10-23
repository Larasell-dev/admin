import { Form, Link } from "@inertiajs/react";
import { Icon, Section, SectionStack, useScrollMaskScrollbar } from "@larasell-dev/ui";
import { ArrowLeftLine } from "@larasell-dev/ui/icons";
import { useRef } from "react";

import CategoryForm from "../../Components/CategoryForm";
import ProductsTable from "../../Components/ProductsTable";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import CategoryLayout, { CategoryTreeTrigger } from "../../Layouts/CategoryLayout";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.CreateCategoryPageData;

export default function Create({ categories, storeUrl, indexUrl, parent }: Props) {
  const contentScroll = useRef<HTMLDivElement>(null);
  useScrollMaskScrollbar(contentScroll);

  return (
    <>
      <header className="flex h-14 shrink-0 items-center gap-4 border-b border-border px-6">
        {categories.length === 0 && (
          <Link
            href={indexUrl}
            aria-label="Back to categories"
            className="-ml-1.5 -mr-2 inline-flex size-8 shrink-0 cursor-pointer items-center justify-center text-mauve-950 outline-none hover:bg-mauve-100 focus-visible:focus-ring lg:hidden dark:text-mauve-100 dark:hover:bg-mauve-800 [&_svg]:size-5"
          >
            <Icon icon={ArrowLeftLine} />
          </Link>
        )}
        <h2 className="flex-1 truncate type-body-md text-mauve-950 dark:text-mauve-50">
          {parent ? `New category in ${parent.name}` : "New category"}
        </h2>
        {categories.length > 0 && <CategoryTreeTrigger />}
      </header>
      <div
        ref={contentScroll}
        className="scroll-mask-y-from-95% min-h-0 flex-1 overflow-y-auto px-6 py-6"
      >
        <div className="max-w-2xl">
          <SectionStack>
            <Form
              // Inertia keeps this page mounted when only the parent changes; remounting empties the fields.
              key={parent?.id ?? "root"}
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
              <UnsavedChangesToast container={contentScroll} />
            </Form>
            <ProductsTable mode="create" />
          </SectionStack>
        </div>
      </div>
    </>
  );
}

Create.layout = [SidebarLayout, CategoryLayout];
