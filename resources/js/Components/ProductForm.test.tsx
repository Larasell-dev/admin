import "../../css/app.css";
import { App, Form } from "@inertiajs/react";
import type { ComponentProps } from "react";
import { describe, expect, it } from "vitest";
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

const boots = {
  name: "Boots",
  slug: "boots",
  visible: true,
  weight: null,
  dimensions: null,
  sku: null,
  barcode: null,
};

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
  expect(submitted()).toMatchObject({
    name: "Linen Shirt",
    slug: "linen-shirt",
    status: "visible",
  });
});

it("shows an existing product's values and leaves its slug alone", async () => {
  await renderForm({ ...boots, visible: false });
  await expect.element(nameInput()).toHaveValue("Boots");
  await expect.element(visible()).not.toBeChecked();

  await userEvent.type(nameInput(), " for rain");

  await expect.element(slugInput()).toHaveValue("boots");
  expect(submitted()).toMatchObject({ name: "Boots for rain", slug: "boots", status: "hidden" });
});

const weight = () => page.getByRole("textbox", { name: "Weight", exact: true });
const weightUnit = () => page.getByRole("combobox", { name: "Weight unit" });
const axis = (name: "Length" | "Width" | "Height") =>
  page.getByRole("textbox", { name, exact: true });
const unitOf = (name: "Length" | "Width" | "Height") =>
  page.getByRole("combobox", { name: `${name} unit` });
const dimensionsUnit = () => unitOf("Length");

describe("weight and dimensions", () => {
  it("starts a new product without them, in kilograms and centimeters", async () => {
    await renderForm();

    await expect.element(weight()).toHaveValue("");
    await expect.element(weightUnit()).toHaveValue("kg");
    await expect.element(dimensionsUnit()).toHaveValue("cm");
    expect(submitted()).toMatchObject({
      "weight[amount]": "",
      "weight[unit]": "kg",
      "dimensions[length]": "",
      "dimensions[width]": "",
      "dimensions[height]": "",
      "dimensions[unit]": "cm",
    });
  });

  it("shows an existing product's weight and dimensions in the units they were saved in", async () => {
    await renderForm({
      ...boots,
      weight: { amount: "12.5", unit: "lb" },
      dimensions: { length: "50", width: "35.5", height: "0", unit: "in" },
    });

    await expect.element(weight()).toHaveValue("12.5");
    await expect.element(weightUnit()).toHaveValue("lb");
    await expect.element(axis("Width")).toHaveValue("35.5");
    await expect.element(axis("Height")).toHaveValue("0");
    await expect.element(dimensionsUnit()).toHaveValue("in");
  });

  it("only relabels the weight when its unit changes", async () => {
    await renderForm({ ...boots, weight: { amount: "1.5", unit: "kg" } });

    await userEvent.selectOptions(weightUnit(), "g");

    await expect.element(weight()).toHaveValue("1.5");
    expect(submitted()).toMatchObject({ "weight[amount]": "1.5", "weight[unit]": "g" });
  });

  it("keeps one unit for all three dimensions, whichever select changes it", async () => {
    await renderForm({
      ...boots,
      dimensions: { length: "50", width: "35", height: "5", unit: "cm" },
    });

    await userEvent.selectOptions(unitOf("Height"), "in");

    await expect.element(unitOf("Length")).toHaveValue("in");
    await expect.element(unitOf("Width")).toHaveValue("in");
    const form = new FormData(page.getByTestId("form").element() as HTMLFormElement);
    expect(form.getAll("dimensions[unit]")).toEqual(["in"]);
    await expect.element(axis("Length")).toHaveValue("50");
  });
});

const sku = () => page.getByRole("textbox", { name: "SKU" });
const barcode = () => page.getByRole("textbox", { name: "Barcode" });

describe("SKU and barcode", () => {
  it("comes after the weight and dimensions", async () => {
    await renderForm();

    const headings = page
      .getByRole("heading")
      .elements()
      .map((heading) => heading.textContent);
    expect(headings).toEqual(["General", "Weight and dimensions", "SKU and barcode"]);
  });

  it("starts a new product without them", async () => {
    await renderForm();

    await expect.element(sku()).toHaveValue("");
    await expect.element(barcode()).toHaveValue("");
    expect(submitted()).toMatchObject({ sku: "", barcode: "" });
  });

  it("shows an existing product's SKU and barcode", async () => {
    await renderForm({ ...boots, sku: "BOOT-42", barcode: "4006381333931" });

    await expect.element(sku()).toHaveValue("BOOT-42");
    await expect.element(barcode()).toHaveValue("4006381333931");
  });
});
