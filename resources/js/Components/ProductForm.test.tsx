import "../../css/app.css";
import { App, Form, http } from "@inertiajs/react";
import type { ComponentProps } from "react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { page, userEvent } from "vitest/browser";
import { render } from "vitest-browser-react";
import ProductForm from "./ProductForm";
import UnsavedChangesToast, { UnsavedChangesToastHost } from "./UnsavedChangesToast";

type Product = ComponentProps<typeof ProductForm>["product"];
type Category = ComponentProps<typeof ProductForm>["categories"][number];

/** Renders the form for the product, or for a new one without it, with the store's categories. */
function renderForm(product?: Product, categories: Category[] = []) {
  function FormPage() {
    return (
      <>
        <Form action="/admin/products" method="post" data-testid="form">
          <ProductForm
            product={product}
            categories={categories}
            createCategoryUrl="/admin/categories/create"
            uploadImagesUrl="/admin/products/images"
          />
          <UnsavedChangesToast />
        </Form>
        {/* SidebarLayout renders the host in the panel. */}
        <UnsavedChangesToastHost />
      </>
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
  stock: null,
  allowBackorders: true,
  minQuantity: null,
  maxQuantity: null,
  categoryIds: [],
  images: [],
};

const nameInput = () => page.getByRole("textbox", { name: "Name" });
const slugInput = () => page.getByRole("textbox", { name: "Slug" });
const visible = () => page.getByRole("checkbox", { name: "Visible in the store" });
/** What submitting the form would send. */
const formData = () => new FormData(page.getByTestId("form").element() as HTMLFormElement);
const submitted = () => Object.fromEntries(formData());

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
    expect(formData().getAll("dimensions[unit]")).toEqual(["in"]);
    await expect.element(axis("Length")).toHaveValue("50");
  });
});

const sku = () => page.getByRole("textbox", { name: "SKU" });
const barcode = () => page.getByRole("textbox", { name: "Barcode" });

describe("SKU and barcode", () => {
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

const stock = () => page.getByRole("textbox", { name: "Stock" });
const backorders = () => page.getByRole("checkbox", { name: "Allow backorders" });
const minQuantity = () => page.getByRole("textbox", { name: "Minimum quantity" });
const maxQuantity = () => page.getByRole("textbox", { name: "Maximum quantity" });

describe("stock", () => {
  it("starts a new product untracked, with backorders and no quantity limits", async () => {
    await renderForm();

    await expect.element(stock()).toHaveValue("");
    await expect.element(backorders()).toBeChecked();
    await expect.element(minQuantity()).toHaveValue("");
    await expect.element(maxQuantity()).toHaveValue("");
    expect(submitted()).toMatchObject({
      stock: "",
      allow_backorders: "1",
      min_quantity: "",
      max_quantity: "",
    });
  });

  it("shows an existing product's stock and limits, and submits backorders turned on", async () => {
    await renderForm({
      ...boots,
      stock: -3,
      allowBackorders: false,
      minQuantity: 6,
      maxQuantity: 24,
    });

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
  hideUrl: "",
  unhideUrl: "",
  deleteUrl: "",
  createChildUrl: "",
  moveUrl: "",
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
    await renderForm(undefined, categories);

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
    await renderForm(undefined, categories);

    await categoryItem("Clothing").click();

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
    await renderForm(undefined, categories);
    await openButton("Clothing").click();
    await openButton("Trousers").click();

    await categoryItem("Jeans").click();

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
    await renderForm({ ...boots, categoryIds: [4, 6] }, categories);

    await expect.element(categoryItem("Shoes")).toHaveAttribute("aria-checked", "true");
    await expect.element(categoryItem("Clothing")).toHaveAttribute("aria-checked", "mixed");
    expect(submittedCategories()).toEqual([4, 6]);
    await expect.element(toast()).not.toBeInTheDocument();
  });

  it("selects the focused category with Space", async () => {
    await renderForm(undefined, categories);
    (categoryItem("Shoes").element() as HTMLElement).focus();

    await userEvent.keyboard(" ");

    await expect.element(categoryItem("Shoes")).toHaveAttribute("aria-checked", "true");
    expect(submittedCategories()).toEqual([6]);
  });

  it("shows the unsaved changes toast once a category is clicked, and puts them back on cancel", async () => {
    await renderForm({ ...boots, categoryIds: [4] }, categories);
    await expect.element(toast()).not.toBeInTheDocument();

    await categoryItem("Clothing").click();

    await expect.element(toast()).toBeVisible();
    expect(submittedCategories()).toEqual([1, 2, 3, 4, 5]);

    await toast().getByRole("button", { name: "Cancel" }).click();

    await expect.element(toast()).not.toBeInTheDocument();
    await expect.element(categoryItem("Clothing")).toHaveAttribute("aria-checked", "mixed");
    expect(submittedCategories()).toEqual([4]);
  });

  it("offers to create categories when the store has none", async () => {
    await renderForm();

    await expect.element(page.getByRole("tree")).not.toBeInTheDocument();
    await expect.element(page.getByText("No categories yet")).toBeVisible();
    await expect
      .element(page.getByRole("link", { name: "Create categories" }))
      .toHaveAttribute("href", "/admin/categories/create");
  });

  it("links to each category's page from its menu", async () => {
    await renderForm(undefined, categories);
    await openButton("Clothing").click();

    await page.getByRole("button", { name: "Actions for Shirts" }).click();

    await expect
      .element(page.getByRole("menuitem", { name: "View" }))
      .toHaveAttribute("href", "/admin/categories/2");
    // Opening the menu doesn't select the category.
    await expect.element(categoryItem("Shirts")).toHaveAttribute("aria-checked", "false");
  });
});

/** A solid square, so the images render without a server. */
const swatch = (color: string) =>
  `data:image/svg+xml,${encodeURIComponent(`<svg xmlns="http://www.w3.org/2000/svg" width="8" height="8"><rect width="8" height="8" fill="${color}"/></svg>`)}`;

const imageGrid = () => page.getByRole("list", { name: "Images" });
const gridImages = () =>
  imageGrid()
    .getByRole("img")
    .elements()
    .map((image) => image.getAttribute("src"));
const addImages = () => page.getByLabelText("Add images");
/** The cards of images still uploading, named after their files. */
const uploadingCards = () => imageGrid().getByRole("status");
/**
 * Answers every image upload with the status and body instead of sending it to a server, and
 * returns the uploads it answered: where each went and the names of the files it sent. A promised
 * body holds the answer until it resolves.
 */
function fakeUploads(status: number, body: unknown) {
  const uploads: { url: string; files: string[] }[] = [];
  vi.spyOn(http, "getClient").mockReturnValue({
    async request({ url, data }) {
      const files = [...(data as FormData).values()] as File[];
      uploads.push({ url, files: files.map((file) => file.name) });
      return { status, data: JSON.stringify(await body), headers: {} };
    },
  });
  return uploads;
}

const png = (name: string) => new File(["png"], name, { type: "image/png" });

describe("images", () => {
  afterEach(() => vi.restoreAllMocks());

  it("shows a product's images in their order, the first one as its thumbnail", async () => {
    await renderForm({
      ...boots,
      images: [
        { id: 7, url: swatch("red") },
        { id: 3, url: swatch("blue") },
      ],
    });

    await expect.element(imageGrid()).toBeVisible();
    expect(gridImages()).toEqual([swatch("red"), swatch("blue")]);
    const [first, second] = imageGrid().getByRole("listitem").elements();
    expect(first).toHaveTextContent("Thumbnail");
    expect(second).not.toHaveTextContent("Thumbnail");
  });

  it("adds the chosen images after the product's, to be linked when it's saved", async () => {
    const uploads = fakeUploads(201, {
      images: [
        { id: 8, url: swatch("green") },
        { id: 9, url: swatch("yellow") },
      ],
    });
    await renderForm({ ...boots, images: [{ id: 7, url: swatch("red") }] });
    // A file input, so clicking the square opens the system's file picker.
    await expect.element(addImages()).toHaveAttribute("type", "file");
    await expect.element(addImages()).toHaveAttribute("multiple");
    expect(formData().getAll("images[]")).toEqual(["7"]);

    await userEvent.upload(addImages(), [png("side.png"), png("back.png")]);

    await expect.poll(gridImages).toEqual([swatch("red"), swatch("green"), swatch("yellow")]);
    expect(uploads).toEqual([{ url: "/admin/products/images", files: ["side.png", "back.png"] }]);
    expect(formData().getAll("images[]")).toEqual(["7", "8", "9"]);
    await expect.element(toast()).toBeVisible();
  });

  it("shows a loading card per chosen image, and no more can be added until they're uploaded", async () => {
    let respond!: (body: unknown) => void;
    fakeUploads(201, new Promise((resolve) => (respond = resolve)));
    await renderForm({ ...boots, images: [{ id: 7, url: swatch("red") }] });

    await userEvent.upload(addImages(), [png("side.png"), png("back.png")]);

    // After the product's image, where the uploaded ones will appear.
    await expect.poll(() => uploadingCards().elements()).toHaveLength(2);
    const [, first, second, add] = imageGrid().getByRole("listitem").elements();
    expect(first.querySelector("[role=status]")).toHaveAccessibleName("Uploading side.png");
    expect(second.querySelector("[role=status]")).toHaveAccessibleName("Uploading back.png");
    // The add square stays a plus, just disabled.
    expect(add).toContainElement(addImages().element() as HTMLElement);
    expect(add.querySelector("[role=status]")).toBeNull();
    await expect.element(addImages()).toBeDisabled();
    // Nothing to link until the upload answers.
    expect(formData().getAll("images[]")).toEqual(["7"]);

    respond({
      images: [
        { id: 8, url: swatch("green") },
        { id: 9, url: swatch("yellow") },
      ],
    });

    await expect.poll(gridImages).toEqual([swatch("red"), swatch("green"), swatch("yellow")]);
    expect(uploadingCards().elements()).toHaveLength(0);
    await expect.element(addImages()).toBeEnabled();
  });

  it("removes an image from its menu, to be unlinked when saved, and puts it back on cancel", async () => {
    await renderForm({
      ...boots,
      images: [
        { id: 7, url: swatch("red") },
        { id: 3, url: swatch("blue") },
        { id: 5, url: swatch("green") },
      ],
    });

    await page.getByRole("button", { name: "Actions for image 1" }).click();
    await page.getByRole("menuitem", { name: "Remove" }).click();

    // No confirmation: the unsaved changes toast is the way back.
    await expect.poll(gridImages).toEqual([swatch("blue"), swatch("green")]);
    expect(formData().getAll("images[]")).toEqual(["3", "5"]);
    // The next image becomes the thumbnail.
    await expect.element(imageGrid().getByRole("listitem").first()).toHaveTextContent("Thumbnail");
    await expect.element(toast()).toBeVisible();

    // The menus follow the images' new positions.
    await page.getByRole("button", { name: "Actions for image 2" }).click();
    await page.getByRole("menuitem", { name: "Remove" }).click();

    await expect.poll(gridImages).toEqual([swatch("blue")]);
    expect(formData().getAll("images[]")).toEqual(["3"]);

    await toast().getByRole("button", { name: "Cancel" }).click();

    await expect.element(toast()).not.toBeInTheDocument();
    expect(gridImages()).toEqual([swatch("red"), swatch("blue"), swatch("green")]);
    expect(formData().getAll("images[]")).toEqual(["7", "3", "5"]);
  });

  // Cancelling then leaves the remaining images' inputs as they were, which Inertia's reset alone
  // doesn't count as a change.
  it.each([
    ["the last image", [7, 3, 5]],
    ["the only image", [7]],
  ])("puts %s back when the removal is cancelled", async (_, ids) => {
    const colors = ["red", "blue", "green"];
    await renderForm({ ...boots, images: ids.map((id, i) => ({ id, url: swatch(colors[i]) })) });
    await page.getByRole("button", { name: `Actions for image ${ids.length}` }).click();
    await page.getByRole("menuitem", { name: "Remove" }).click();
    await expect.element(toast()).toBeVisible();

    await toast().getByRole("button", { name: "Cancel" }).click();

    await expect.element(toast()).not.toBeInTheDocument();
    expect(formData().getAll("images[]")).toEqual(ids.map(String));
    expect(gridImages()).toHaveLength(ids.length);
  });

  it("drops the uploaded images when the changes are cancelled", async () => {
    fakeUploads(201, { images: [{ id: 8, url: swatch("green") }] });
    await renderForm({ ...boots, images: [{ id: 7, url: swatch("red") }] });
    await userEvent.upload(addImages(), [png("side.png")]);
    await expect.element(toast()).toBeVisible();

    await toast().getByRole("button", { name: "Cancel" }).click();

    await expect.element(toast()).not.toBeInTheDocument();
    expect(gridImages()).toEqual([swatch("red")]);
    expect(formData().getAll("images[]")).toEqual(["7"]);
  });

  it("says why images were rejected and keeps the product's", async () => {
    fakeUploads(422, {
      message: "Invalid",
      errors: { "images.1": ["Each image must be a JPG, PNG, WebP or AVIF file."] },
    });
    await renderForm({ ...boots, images: [{ id: 7, url: swatch("red") }] });

    await userEvent.upload(addImages(), [png("side.png"), png("manual.pdf")]);

    await expect
      .element(page.getByText("Each image must be a JPG, PNG, WebP or AVIF file."))
      .toBeVisible();
    expect(gridImages()).toEqual([swatch("red")]);
    expect(uploadingCards().elements()).toHaveLength(0);
    expect(formData().getAll("images[]")).toEqual(["7"]);
  });

  it("adds images to a new product, the first as its thumbnail", async () => {
    fakeUploads(201, {
      images: [
        { id: 8, url: swatch("green") },
        { id: 9, url: swatch("yellow") },
      ],
    });
    await renderForm();
    await expect.element(addImages()).toBeVisible();
    expect(formData().getAll("images[]")).toEqual([]);

    await userEvent.upload(addImages(), [png("side.png"), png("back.png")]);

    await expect.poll(() => formData().getAll("images[]")).toEqual(["8", "9"]);
    await expect.element(imageGrid().getByRole("listitem").first()).toHaveTextContent("Thumbnail");
  });
});
