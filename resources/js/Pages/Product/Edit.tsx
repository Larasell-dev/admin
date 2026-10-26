import { Form } from "@inertiajs/react";
import { Page } from "../../Components/Page";
import ProductForm from "../../Components/ProductForm";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.EditProductPageData;

export default function Edit({
  product,
  updateUrl,
  indexUrl,
  categories,
  createCategoryUrl,
  uploadImagesUrl,
}: Props) {
  return (
    <>
      <Page.Header title={product.name} backLink={{ href: indexUrl, label: "Back to products" }} />
      <Page.Body>
        <Form
          // Inertia keeps this page mounted when moving between products; remounting resets the fields.
          key={updateUrl}
          action={updateUrl}
          method="put"
          // The saved values become the new baseline, so the form is no longer dirty.
          setDefaultsOnSuccess
        >
          <ProductForm
            product={product}
            categories={categories}
            createCategoryUrl={createCategoryUrl}
            uploadImagesUrl={uploadImagesUrl}
          />
          <UnsavedChangesToast />
        </Form>
      </Page.Body>
    </>
  );
}

Edit.layout = [SidebarLayout];
