import { Form } from "@inertiajs/react";
import ContentLocaleSelect from "../../Components/ContentLocaleSelect";
import { Page } from "../../Components/Page";
import ProductForm from "../../Components/ProductForm";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import SidebarLayout from "../../Layouts/SidebarLayout";
import type { SharedProps } from "../../types";

type Props = Larasell.Admin.Data.View.EditProductPageData & Pick<SharedProps, "contentLocale">;

export default function Edit({
  product,
  updateUrl,
  indexUrl,
  categories,
  createCategoryUrl,
  uploadImagesUrl,
  contentLocale,
}: Props) {
  return (
    <Form
      // Inertia keeps this page mounted when moving between products or languages; remounting
      // resets the fields to the new values.
      key={`${updateUrl}-${contentLocale?.current}`}
      action={updateUrl}
      method="put"
      // The saved values become the new baseline, so the form is no longer dirty.
      setDefaultsOnSuccess
      // Around the header too, so the language select knows about unsaved changes; `contents`
      // keeps the header and body stacked in the layout as if the form weren't there.
      className="contents"
    >
      <Page.Header
        title={product.name ?? product.fallbackName}
        backLink={{ href: indexUrl, label: "Back to products" }}
        actions={<ContentLocaleSelect contentLocale={contentLocale} />}
      />
      <Page.Body>
        <ProductForm
          product={product}
          categories={categories}
          createCategoryUrl={createCategoryUrl}
          uploadImagesUrl={uploadImagesUrl}
        />
        <UnsavedChangesToast />
      </Page.Body>
    </Form>
  );
}

Edit.layout = [SidebarLayout];
