import { Form } from "@inertiajs/react";
import { fbs } from "fbtee";
import ContentLocaleSelect from "../../Components/ContentLocale/ContentLocaleSelect";
import ExtensionSections from "../../Components/ExtensionSections";
import { Page } from "../../Components/Page";
import ProductForm from "../../Components/Product/ProductForm";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import SidebarLayout from "../../Layouts/SidebarLayout";
import type { SharedProps } from "../../types";

type Props = Larasell.Admin.Product.Data.CreateProductPageData & Pick<SharedProps, "contentLocale">;

export default function Create({
  storeUrl,
  indexUrl,
  categories,
  createCategoryUrl,
  uploadImagesUrl,
  sections,
  contentLocale,
}: Props) {
  return (
    <Form
      // Remounts the select once switching the language reloads the page.
      key={contentLocale?.current}
      action={storeUrl}
      method="post"
      // Around the header too, so the language select knows about unsaved changes; `contents`
      // keeps the header and body stacked in the layout as if the form weren't there.
      className="contents"
    >
      <Page.Header
        title={fbs(
          "New product",
          "New product page: title of the page with the form that creates a product",
        )}
        backLink={{
          href: indexUrl,
          label: fbs(
            "Back to products",
            "New product page: accessible name of the back arrow that returns to the list of products",
          ),
        }}
        actions={<ContentLocaleSelect contentLocale={contentLocale} />}
      />
      <Page.Body>
        <ProductForm
          categories={categories}
          createCategoryUrl={createCategoryUrl}
          uploadImagesUrl={uploadImagesUrl}
        >
          <ExtensionSections sections={sections} />
        </ProductForm>
        <UnsavedChangesToast />
      </Page.Body>
    </Form>
  );
}

Create.layout = [SidebarLayout];
