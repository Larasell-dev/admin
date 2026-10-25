import { Form, Link } from "@inertiajs/react";
import { Icon, IconButton, useScrollMaskScrollbar } from "@larasell-dev/ui";
import { ArrowLeftLine } from "@larasell-dev/ui/icons";
import { useRef } from "react";
import ProductForm from "../../Components/ProductForm";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.CreateProductPageData;

export default function Create({ storeUrl, indexUrl }: Props) {
  const contentScroll = useRef<HTMLDivElement>(null);
  useScrollMaskScrollbar(contentScroll);

  return (
    <div className="flex h-[calc(100dvh-3.5rem)] flex-col lg:h-dvh">
      <header className="flex h-14 shrink-0 items-center gap-2 border-b border-border px-6">
        <IconButton
          variant="tertiary"
          size="sm"
          aria-label="Back to products"
          nativeButton={false}
          // Base UI marks non-native buttons as buttons; this navigates, so it stays a link.
          role="link"
          render={<Link href={indexUrl} prefetch="hover" />}
        >
          <Icon icon={ArrowLeftLine} />
        </IconButton>
        <h2 className="flex-1 truncate type-body-md text-mauve-950 dark:text-mauve-50">
          New product
        </h2>
      </header>
      <div
        ref={contentScroll}
        className="scroll-mask-y-from-95% min-h-0 flex-1 overflow-y-auto px-6 py-6"
      >
        <div className="max-w-2xl">
          <Form action={storeUrl} method="post">
            <ProductForm />
            <UnsavedChangesToast container={contentScroll} />
          </Form>
        </div>
      </div>
    </div>
  );
}

Create.layout = [SidebarLayout];
