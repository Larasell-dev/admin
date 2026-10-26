import "../../css/app.css";
import { App } from "@inertiajs/react";
import type { ComponentProps } from "react";
import { expect, it } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import type { TableResource } from "../types";
import ProductsTable from "./ProductsTable";

// Renders through Inertia's App, so its links know the router like in the panel.
function renderTable(props: ComponentProps<typeof ProductsTable>) {
  const Page = () => <ProductsTable {...props} />;
  const initialPage = { component: "Page", url: "/admin/categories/1", version: null, props: {} };

  return render(
    <App
      initialPage={initialPage as never}
      initialComponent={Page}
      resolveComponent={() => Page}
    />,
  );
}

const emptyTable: TableResource = {
  title: "Products",
  emptyState: {
    title: "No products yet",
    description: "Products in this category show up here.",
    icon: null,
    actions: [],
  },
  headerActions: [],
  columns: [{ name: "name", label: "Name", type: "text" }],
  rows: [],
  pagination: { page: 1, pageSize: 25, total: 0 },
};

const createLink = () => page.getByRole("link", { name: "Create product" });

it("links to creating a product while a new category has none", async () => {
  await renderTable({ mode: "create", createProductUrl: "/admin/products/create" });

  await expect.element(page.getByText("No products yet")).toBeVisible();
  await expect.element(createLink()).toHaveAttribute("href", "/admin/products/create");
});

const editProps = {
  mode: "edit",
  table: emptyTable,
  availableProductsUrl: "/admin/categories/1/available-products",
  addProductUrl: "/admin/categories/1/products",
} as const;
const addButton = () => page.getByRole("button", { name: "Add product" });

it("only offers to create a product while the store has none to add", async () => {
  await renderTable({ ...editProps, createProductUrl: "/admin/products/create" });

  await expect.element(createLink()).toHaveAttribute("href", "/admin/products/create");
  await expect.element(addButton()).not.toBeInTheDocument();
});

it("only offers to add a product once the store has some", async () => {
  await renderTable({ ...editProps, createProductUrl: null });

  await expect.element(addButton()).toBeVisible();
  await expect.element(createLink()).not.toBeInTheDocument();
});

it("leaves out the link when the products are turned off", async () => {
  await renderTable({ mode: "create", createProductUrl: null });

  await expect.element(page.getByText("No products yet")).toBeVisible();
  await expect.element(createLink()).not.toBeInTheDocument();
});
