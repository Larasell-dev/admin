import "../../../css/app.css";
import { App, Form, router, usePage } from "@inertiajs/react";
import { ToastProvider } from "@larasell-dev/ui";
import type { ComponentProps } from "react";
import { describe, expect, it } from "vitest";
import { page, userEvent } from "vitest/browser";
import { render } from "vitest-browser-react";
import ProductForm from "./ProductForm";
import type { RichText } from "../../Platform/RichText/types";
import UnsavedChangesToast, {
  UnsavedChangesToastHost,
} from "../../Platform/Panel/Components/UnsavedChangesToast";

type Product = ComponentProps<typeof ProductForm>["product"];
type Category = ComponentProps<typeof ProductForm>["categories"][number];

type FormPageProps = {
  product?: Product;
  categories: Category[];
  createCategoryUrl: string | null;
};

/**
 * The page the tests render. It takes the product and categories from the page's props, since
 * Inertia's router keeps resolving the component of the file's first test after a visit.
 */
function FormPage() {
  const { product, categories, createCategoryUrl } = usePage<FormPageProps>().props;

  return (
    <>
      <Form action="/admin/products" method="post" data-testid="form">
        <ProductForm
          product={product}
          categories={categories}
          createCategoryUrl={createCategoryUrl}
        />
        <UnsavedChangesToast />
      </Form>
      {/* SidebarLayout renders the host in the panel. */}
      <UnsavedChangesToastHost />
    </>
  );
}

/** The current test's page, which saving the form answers with again. */
let formPage: { component: string; url: string; version: null; props: FormPageProps };

/**
 * Renders the form for the product, or for a new one without it, with the store's categories, on
 * the page at the URL. A null `createCategoryUrl` renders it as while the categories are turned off.
 */
async function renderForm(
  product?: Product,
  categories: Category[] = [],
  url = "/admin/products",
  createCategoryUrl: string | null = "/admin/categories/create",
) {
  formPage = {
    component: "FormPage",
    url,
    version: null,
    props: { product, categories, createCategoryUrl },
  };

  const screen = await render(
    <ToastProvider>
      <App
        initialPage={formPage as never}
        initialComponent={FormPage}
        resolveComponent={() => FormPage}
      />
    </ToastProvider>,
  );
  // The router only takes the initial page of the file's first test. Without this, visits that
  // stay in the browser would bring back that test's page.
  router.replace({ ...formPage, preserveState: true, preserveScroll: true });
  return screen;
}

const boots = {
  name: "Boots",
  fallbackName: "Boots",
  description: null,
  visible: true,
  weight: null,
  dimensions: null,
  sku: null,
  barcode: null,
  stock: null,
  allowBackorders: true,
  minQuantity: null,
  maxQuantity: null,
  categoryIds: [],
};

const nameInput = () => page.getByRole("textbox", { name: "Name" });
const visible = () => page.getByRole("checkbox", { name: "Visible in the store" });
/** What submitting the form would send. */
const formData = () => new FormData(page.getByTestId("form").element() as HTMLFormElement);
const submitted = () => Object.fromEntries(formData());

it("starts a new product empty and visible", async () => {
  // Arrange
  await renderForm();
  await expect.element(visible()).toBeChecked();

  // Act
  await userEvent.type(nameInput(), "Linen Shirt");

  // Assert
  expect(submitted()).toMatchObject({ name: "Linen Shirt", status: "visible" });
});

it("shows an existing product's values", async () => {
  // Arrange
  await renderForm({ ...boots, visible: false });
  await expect.element(nameInput()).toHaveValue("Boots");
  await expect.element(visible()).not.toBeChecked();

  // Act
  await userEvent.type(nameInput(), " for rain");

  // Assert
  expect(submitted()).toMatchObject({ name: "Boots for rain", status: "hidden" });
});

const description = () => page.getByRole("textbox", { name: "Description" });
const submittedDescription = () => JSON.parse(formData().get("description") as string);

describe("description", () => {
  it("submits an existing product's description as it was saved", async () => {
    // Arrange
    const saved: RichText = {
      version: 1,
      blocks: [
        { type: "heading", level: 2, children: [{ text: "Made to last" }] },
        { type: "paragraph", children: [{ text: "Waxed " }, { text: "leather", marks: ["bold"] }] },
      ],
    };

    // Act
    await renderForm({ ...boots, description: saved });

    // Assert
    await expect.element(description()).toHaveTextContent("Made to lastWaxed leather");
    expect(submittedDescription()).toEqual(saved);
  });

  it("submits what is typed into it", async () => {
    // Arrange
    await renderForm();

    // Act
    await userEvent.click(description());
    await userEvent.keyboard("Warm and dry");

    // Assert
    await expect.poll(submittedDescription).toEqual({
      version: 1,
      blocks: [{ type: "paragraph", children: [{ text: "Warm and dry" }] }],
    });
  });

  it("puts the caret at the end when the empty space below the text is clicked", async () => {
    // Arrange
    await renderForm({
      ...boots,
      description: { version: 1, blocks: [{ type: "paragraph", children: [{ text: "Warm" }] }] },
    });
    const content = page.elementLocator(description().element().parentElement!);
    const { height } = content.element().getBoundingClientRect();

    // Act
    await userEvent.click(content, { position: { x: 20, y: height - 8 } });
    await userEvent.keyboard(" and dry");

    // Assert
    await expect.poll(submittedDescription).toEqual({
      version: 1,
      blocks: [{ type: "paragraph", children: [{ text: "Warm and dry" }] }],
    });
  });

  it("turns a line into a heading when it starts with ##", async () => {
    // Arrange
    await renderForm();
    await userEvent.click(description());

    // Act
    await userEvent.keyboard("## Care");

    // Assert
    await expect.poll(submittedDescription).toEqual({
      version: 1,
      blocks: [{ type: "heading", level: 2, children: [{ text: "Care" }] }],
    });
  });

  it("turns a line into the block picked from the / menu, without the /", async () => {
    // Arrange
    await renderForm();
    await userEvent.click(description());
    await userEvent.keyboard("/num");

    // Act
    await userEvent.click(page.getByRole("option", { name: "Numbered list" }));
    await userEvent.keyboard("Unpack");

    // Assert
    await expect.poll(submittedDescription).toEqual({
      version: 1,
      blocks: [{ type: "list", style: "numbered", items: [[{ text: "Unpack" }]] }],
    });
  });

  it("makes the selected text bold from the toolbar", async () => {
    // Arrange
    await renderForm({
      ...boots,
      description: { version: 1, blocks: [{ type: "paragraph", children: [{ text: "Boots" }] }] },
    });
    await userEvent.click(description());
    await userEvent.keyboard("{Control>}a{/Control}");

    // Act
    await userEvent.click(page.getByRole("button", { name: "Bold" }));

    // Assert
    await expect.poll(submittedDescription).toEqual({
      version: 1,
      blocks: [{ type: "paragraph", children: [{ text: "Boots", marks: ["bold"] }] }],
    });
  });
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
    // Act
    await renderForm();

    // Assert
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
    // Act
    await renderForm({
      ...boots,
      weight: { amount: "12.5", unit: "lb" },
      dimensions: { length: "50", width: "35.5", height: "0", unit: "in" },
    });

    // Assert
    await expect.element(weight()).toHaveValue("12.5");
    await expect.element(weightUnit()).toHaveValue("lb");
    await expect.element(axis("Width")).toHaveValue("35.5");
    await expect.element(axis("Height")).toHaveValue("0");
    await expect.element(dimensionsUnit()).toHaveValue("in");
  });

  it("only relabels the weight when its unit changes", async () => {
    // Arrange
    await renderForm({ ...boots, weight: { amount: "1.5", unit: "kg" } });

    // Act
    await userEvent.selectOptions(weightUnit(), "g");

    // Assert
    await expect.element(weight()).toHaveValue("1.5");
    expect(submitted()).toMatchObject({ "weight[amount]": "1.5", "weight[unit]": "g" });
  });

  it("keeps one unit for all three dimensions, whichever select changes it", async () => {
    // Arrange
    await renderForm({
      ...boots,
      dimensions: { length: "50", width: "35", height: "5", unit: "cm" },
    });

    // Act
    await userEvent.selectOptions(unitOf("Height"), "in");

    // Assert
    await expect.element(unitOf("Length")).toHaveValue("in");
    await expect.element(unitOf("Width")).toHaveValue("in");
    expect(formData().getAll("dimensions[unit]")).toEqual(["in"]);
    await expect.element(axis("Length")).toHaveValue("50");
  });
});

const sku = () => page.getByRole("textbox", { name: "SKU" });
const barcode = () => page.getByRole("textbox", { name: "Barcode" });

describe("SKU and barcode", () => {
  it("starts a new product without them", async () => {
    // Act
    await renderForm();

    // Assert
    await expect.element(sku()).toHaveValue("");
    await expect.element(barcode()).toHaveValue("");
    expect(submitted()).toMatchObject({ sku: "", barcode: "" });
  });

  it("shows an existing product's SKU and barcode", async () => {
    // Act
    await renderForm({ ...boots, sku: "BOOT-42", barcode: "4006381333931" });

    // Assert
    await expect.element(sku()).toHaveValue("BOOT-42");
    await expect.element(barcode()).toHaveValue("4006381333931");
  });
});

const stock = () => page.getByRole("textbox", { name: "Stock" });
const backorders = () => page.getByRole("checkbox", { name: "Allow backorders" });
const minQuantity = () => page.getByRole("textbox", { name: "Minimum quantity" });
const maxQuantity = () => page.getByRole("textbox", { name: "Maximum quantity" });

describe("stock", () => {
  it("starts a new product untracked, without backorders and with no quantity limits", async () => {
    // Act
    await renderForm();

    // Assert
    await expect.element(stock()).toHaveValue("");
    await expect.element(backorders()).not.toBeChecked();
    await expect.element(minQuantity()).toHaveValue("");
    await expect.element(maxQuantity()).toHaveValue("");
    expect(submitted()).toMatchObject({
      stock: "",
      allow_backorders: "0",
      min_quantity: "",
      max_quantity: "",
    });
  });

  it("shows an existing product's stock and limits, and submits backorders turned on", async () => {
    // Act
    await renderForm({
      ...boots,
      stock: -3,
      allowBackorders: false,
      minQuantity: 6,
      maxQuantity: 24,
    });

    // Assert
    await expect.element(stock()).toHaveValue("-3");
    await expect.element(backorders()).not.toBeChecked();
    await expect.element(minQuantity()).toHaveValue("6");
    await expect.element(maxQuantity()).toHaveValue("24");
    expect(submitted()).toMatchObject({ allow_backorders: "0" });

    await page.getByText("Allow backorders").click();

    await expect.element(backorders()).toBeChecked();
    expect(submitted()).toMatchObject({ allow_backorders: "1" });
  });
});

const category = (id: number, name: string, children: Category[] = []): Category => ({
  id,
  name,
  visible: true,
  url: `/admin/categories/${id}`,
  children,
});

// Clothing > Shirts, Clothing > Trousers > Jeans and Chinos, and Shoes.
const categories = [
  category(1, "Clothing", [
    category(2, "Shirts"),
    category(3, "Trousers", [category(4, "Jeans"), category(5, "Chinos")]),
  ]),
  category(6, "Shoes"),
];

const categoryItem = (name: string) => page.getByRole("treeitem", { name, exact: true });
const openButton = (name: string) => page.getByRole("button", { name: `Open ${name}` });
const submittedCategories = () =>
  formData()
    .getAll("categories[]")
    .map(Number)
    .sort((a, b) => a - b);
const toast = () => page.getByRole("region", { name: "Unsaved changes" });

describe("categories", () => {
  it("starts with every category closed and opens and closes them with their chevron", async () => {
    // Act
    await renderForm(undefined, categories);

    // Assert
    await expect.element(page.getByRole("tree", { name: "Categories" })).toBeVisible();
    await expect.element(categoryItem("Clothing")).toHaveAttribute("aria-expanded", "false");
    await expect.element(categoryItem("Shirts")).not.toBeInTheDocument();
    await expect.element(categoryItem("Shoes")).not.toHaveAttribute("aria-expanded");

    await openButton("Clothing").click();

    await expect.element(categoryItem("Shirts")).toBeVisible();
    await expect.element(categoryItem("Trousers")).toHaveAttribute("aria-expanded", "false");
    // The chevron only opens the category; it doesn't select it.
    await expect.element(categoryItem("Clothing")).toHaveAttribute("aria-checked", "false");

    await page.getByRole("button", { name: "Close Clothing" }).click();

    await expect.element(categoryItem("Shirts")).not.toBeInTheDocument();
    expect(submittedCategories()).toEqual([]);
  });

  it("selects and unselects a category with every category inside it, even while closed", async () => {
    // Arrange
    await renderForm(undefined, categories);

    // Act
    await categoryItem("Clothing").click();

    // Assert
    await expect.element(categoryItem("Clothing")).toHaveAttribute("aria-checked", "true");
    expect(submittedCategories()).toEqual([1, 2, 3, 4, 5]);
    await openButton("Clothing").click();
    await expect.element(categoryItem("Shirts")).toHaveAttribute("aria-checked", "true");
    await expect.element(categoryItem("Trousers")).toHaveAttribute("aria-checked", "true");

    await categoryItem("Clothing").click();

    await expect.element(categoryItem("Clothing")).toHaveAttribute("aria-checked", "false");
    await expect.element(categoryItem("Trousers")).toHaveAttribute("aria-checked", "false");
    expect(submittedCategories()).toEqual([]);
  });

  it("marks a category mixed while only some categories inside it are selected", async () => {
    // Arrange
    await renderForm(undefined, categories);
    await openButton("Clothing").click();
    await openButton("Trousers").click();

    // Act
    await categoryItem("Jeans").click();

    // Assert
    await expect.element(categoryItem("Jeans")).toHaveAttribute("aria-checked", "true");
    await expect.element(categoryItem("Trousers")).toHaveAttribute("aria-checked", "mixed");
    await expect.element(categoryItem("Clothing")).toHaveAttribute("aria-checked", "mixed");
    // The box shows it too, as an indeterminate checkbox.
    await expect
      .element(categoryItem("Trousers").getByTestId("category-checkbox"))
      .toHaveAttribute("aria-checked", "mixed");
    expect(submittedCategories()).toEqual([4]);

    await categoryItem("Chinos").click();

    // All of Trousers, which is still only part of Clothing.
    await expect.element(categoryItem("Trousers")).toHaveAttribute("aria-checked", "true");
    await expect.element(categoryItem("Clothing")).toHaveAttribute("aria-checked", "mixed");
    expect(submittedCategories()).toEqual([3, 4, 5]);

    await categoryItem("Shirts").click();

    await expect.element(categoryItem("Clothing")).toHaveAttribute("aria-checked", "true");
    expect(submittedCategories()).toEqual([1, 2, 3, 4, 5]);

    await categoryItem("Jeans").click();
    await categoryItem("Chinos").click();

    await expect.element(categoryItem("Trousers")).toHaveAttribute("aria-checked", "false");
    await expect.element(categoryItem("Clothing")).toHaveAttribute("aria-checked", "mixed");
    expect(submittedCategories()).toEqual([2]);
  });

  it("shows an existing product's categories without unsaved changes", async () => {
    // Act
    await renderForm({ ...boots, categoryIds: [4, 6] }, categories);

    // Assert
    await expect.element(categoryItem("Shoes")).toHaveAttribute("aria-checked", "true");
    await expect.element(categoryItem("Clothing")).toHaveAttribute("aria-checked", "mixed");
    expect(submittedCategories()).toEqual([4, 6]);
    await expect.element(toast()).not.toBeInTheDocument();
  });

  it("selects the focused category with Space", async () => {
    // Arrange
    await renderForm(undefined, categories);
    (categoryItem("Shoes").element() as HTMLElement).focus();

    // Act
    await userEvent.keyboard(" ");

    // Assert
    await expect.element(categoryItem("Shoes")).toHaveAttribute("aria-checked", "true");
    expect(submittedCategories()).toEqual([6]);
  });

  it("shows the unsaved changes toast once a category is clicked, and puts them back on cancel", async () => {
    // Arrange
    await renderForm({ ...boots, categoryIds: [4] }, categories);
    await expect.element(toast()).not.toBeInTheDocument();

    // Act
    await categoryItem("Clothing").click();

    // Assert
    await expect.element(toast()).toBeVisible();
    expect(submittedCategories()).toEqual([1, 2, 3, 4, 5]);

    await toast().getByRole("button", { name: "Cancel" }).click();

    await expect.element(toast()).not.toBeInTheDocument();
    await expect.element(categoryItem("Clothing")).toHaveAttribute("aria-checked", "mixed");
    expect(submittedCategories()).toEqual([4]);
  });

  it("offers to create categories when the store has none", async () => {
    // Act
    await renderForm();

    // Assert
    await expect.element(page.getByRole("tree")).not.toBeInTheDocument();
    await expect.element(page.getByText("No categories yet")).toBeVisible();
    await expect
      .element(page.getByRole("link", { name: "Create categories" }))
      .toHaveAttribute("href", "/admin/categories/create");
  });

  it("links to each category's page from its menu", async () => {
    // Arrange
    await renderForm(undefined, categories);
    await openButton("Clothing").click();

    // Act
    await page.getByRole("button", { name: "Actions for Shirts" }).click();

    // Assert
    await expect
      .element(page.getByRole("menuitem", { name: "View" }))
      .toHaveAttribute("href", "/admin/categories/2");
    // Opening the menu doesn't select the category.
    await expect.element(categoryItem("Shirts")).toHaveAttribute("aria-checked", "false");
  });

  it("lists the categories without links while they're turned off", async () => {
    // Arrange
    const unlinked = (list: Category[]): Category[] =>
      list.map((c) => ({ ...c, url: null, children: unlinked(c.children) }));
    await renderForm(undefined, unlinked(categories), undefined, null);

    // Act
    await categoryItem("Shoes").click();

    // Assert
    await expect.element(categoryItem("Shoes")).toHaveAttribute("aria-checked", "true");
    expect(submittedCategories()).toEqual([6]);
    await expect
      .element(page.getByRole("button", { name: "Actions for Shoes" }))
      .not.toBeInTheDocument();
  });

  it("doesn't offer to create categories while they're turned off", async () => {
    // Act
    await renderForm(undefined, [], undefined, null);

    // Assert
    await expect.element(page.getByText("No categories yet")).toBeVisible();
    await expect
      .element(page.getByRole("link", { name: "Create categories" }))
      .not.toBeInTheDocument();
  });
});
