import { Form, Link } from "@inertiajs/react";
import { Icon, IconButton, Section, SectionStack, useScrollMaskScrollbar } from "@larasell-dev/ui";
import { ArrowLeftLine } from "@larasell-dev/ui/icons";
import { useRef } from "react";

import CategoryForm from "../../Components/CategoryForm";
import ProductsTable from "../../Components/ProductsTable";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import CategoryLayout, { CategoryTreeTrigger } from "../../Layouts/CategoryLayout";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.CreateCategoryPageData;

export default function Create({
  categories,
  storeUrl,
  indexUrl,
  parent,
  createProductUrl,
}: Props) {
  const contentScroll = useRef<HTMLDivElement>(null);
  useScrollMaskScrollbar(contentScroll);

  return (
    <>
      <header className="flex h-14 shrink-0 items-center gap-4 border-b border-border px-6">
        {categories.length === 0 && (
          <IconButton
            variant="tertiary"
            size="sm"
            aria-label="Back to categories"
            nativeButton={false}
            // Base UI marks non-native buttons as buttons; this navigates, so it stays a link.
            role="link"
            render={<Link href={indexUrl} />}
            DANGEROUS_className="lg:hidden"
          >
            <Icon icon={ArrowLeftLine} />
          </IconButton>
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
            <ProductsTable mode="create" createProductUrl={createProductUrl} />
          </SectionStack>
        </div>
      </div>
    </>
  );
}

Create.layout = [SidebarLayout, CategoryLayout];
