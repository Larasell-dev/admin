import { Form } from "@inertiajs/react";
import { useScrollMaskScrollbar } from "@larasell-dev/ui";
import { useRef } from "react";
import ModuleHeader from "../../Components/ModuleHeader";
import ProductForm from "../../Components/ProductForm";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.EditProductPageData;

export default function Edit({ product, updateUrl, indexUrl }: Props) {
  const contentScroll = useRef<HTMLDivElement>(null);
  useScrollMaskScrollbar(contentScroll);

  return (
    <div className="flex h-[calc(100dvh-3.5rem)] flex-col lg:h-dvh">
      <ModuleHeader title={product.name} backLink={{ href: indexUrl, label: "Back to products" }} />
      <div
        ref={contentScroll}
        className="scroll-mask-y-from-95% min-h-0 flex-1 overflow-y-auto px-6 py-6"
      >
        <div className="max-w-2xl">
          <Form
            // Inertia keeps this page mounted when moving between products; remounting resets the fields.
            key={updateUrl}
            action={updateUrl}
            method="put"
            // The saved values become the new baseline, so the form is no longer dirty.
            setDefaultsOnSuccess
          >
            <ProductForm product={product} />
            <UnsavedChangesToast container={contentScroll} />
          </Form>
        </div>
      </div>
    </div>
  );
}

Edit.layout = [SidebarLayout];
