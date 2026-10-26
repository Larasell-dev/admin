import { Form } from "@inertiajs/react";
import { Page } from "../../Components/Page";
import ProductForm from "../../Components/ProductForm";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.CreateProductPageData;

export default function Create({ storeUrl, indexUrl, categories, createCategoryUrl }: Props) {
  return (
    <>
      <Page.Header title="New product" backLink={{ href: indexUrl, label: "Back to products" }} />
      <Page.Body>
        <Form action={storeUrl} method="post">
          <ProductForm categories={categories} createCategoryUrl={createCategoryUrl} />
          <UnsavedChangesToast />
        </Form>
      </Page.Body>
    </>
  );
}

Create.layout = [SidebarLayout];
