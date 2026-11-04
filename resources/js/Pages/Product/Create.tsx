import { Form } from "@inertiajs/react";
import ContentLocaleSelect from "../../Components/ContentLocaleSelect";
import { Page } from "../../Components/Page";
import ProductForm from "../../Components/ProductForm";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.CreateProductPageData;

export default function Create({
  storeUrl,
  indexUrl,
  categories,
  createCategoryUrl,
  uploadImagesUrl,
  contentLocale,
}: Props) {
  return (
    <Form
      // Remounts the select once switching the language reloads the page.
      key={contentLocale.current}
      action={storeUrl}
      method="post"
      // Around the header too, so the language select knows about unsaved changes; `contents`
      // keeps the header and body stacked in the layout as if the form weren't there.
      className="contents"
    >
      <Page.Header
        title="New product"
        backLink={{ href: indexUrl, label: "Back to products" }}
        actions={<ContentLocaleSelect contentLocale={contentLocale} />}
      />
      <Page.Body>
        <ProductForm
          categories={categories}
          createCategoryUrl={createCategoryUrl}
          uploadImagesUrl={uploadImagesUrl}
        />
        <UnsavedChangesToast />
      </Page.Body>
    </Form>
  );
}

Create.layout = [SidebarLayout];
