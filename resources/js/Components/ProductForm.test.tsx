import "../../css/app.css";
import { App, Form } from "@inertiajs/react";
import type { ComponentProps } from "react";
import { expect, it } from "vitest";
import { page, userEvent } from "vitest/browser";
import { render } from "vitest-browser-react";
import ProductForm from "./ProductForm";

type Product = ComponentProps<typeof ProductForm>["product"];

/** Renders the form for the product, or for a new one without it. */
function renderForm(product?: Product) {
  function FormPage() {
    return (
      <Form action="/admin/products" method="post" data-testid="form">
        <ProductForm product={product} />
      </Form>
    );
  }

  const initialPage = { component: "FormPage", url: "/admin/products", version: null, props: {} };

  return render(
    <App
      initialPage={initialPage as never}
      initialComponent={FormPage}
      resolveComponent={() => FormPage}
    />,
  );
}

const nameInput = () => page.getByRole("textbox", { name: "Name" });
const slugInput = () => page.getByRole("textbox", { name: "Slug" });
const visible = () => page.getByRole("checkbox", { name: "Visible in the store" });
const submitted = () =>
  Object.fromEntries(new FormData(page.getByTestId("form").element() as HTMLFormElement));

it("starts a new product empty and visible, with a slug that follows the name", async () => {
  await renderForm();
  await expect.element(visible()).toBeChecked();

  await userEvent.type(nameInput(), "Linen Shirt");

  await expect.element(slugInput()).toHaveValue("linen-shirt");
  expect(submitted()).toEqual({ name: "Linen Shirt", slug: "linen-shirt", status: "visible" });
});

it("shows an existing product's values and leaves its slug alone", async () => {
  await renderForm({ name: "Boots", slug: "boots", visible: false });
  await expect.element(nameInput()).toHaveValue("Boots");
  await expect.element(visible()).not.toBeChecked();

  await userEvent.type(nameInput(), " for rain");

  await expect.element(slugInput()).toHaveValue("boots");
  expect(submitted()).toEqual({ name: "Boots for rain", slug: "boots", status: "hidden" });
});
