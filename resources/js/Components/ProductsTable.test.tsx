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

it("leaves out the actions menu on rows without actions", async () => {
  const remove = {
    name: "remove",
    label: "Remove",
    method: "post",
    url: "/admin/tables/category-products/1/actions/remove",
    confirm: null,
    destructive: true,
    disabled: false,
    icon: null,
  } as const;
  const row = (id: number, name: string, actions: (typeof remove)[]) => ({
    id,
    name,
    _actions: actions,
    _link: null,
    _variants: {},
  });
  const table = {
    ...emptyTable,
    rows: [row(1, "Shoes", [remove]), row(2, "Socks", [])],
    pagination: { page: 1, pageSize: 25, total: 2 },
  };

  await renderTable({ ...editProps, table, createProductUrl: null });

  await expect.element(page.getByRole("button", { name: "Actions for Shoes" })).toBeVisible();
  await expect
    .element(page.getByRole("button", { name: "Actions for Socks" }))
    .not.toBeInTheDocument();
});

it("links each row with a link through its name, keeping its actions menu clickable", async () => {
  const view = {
    name: "view",
    label: "View",
    method: "get",
    url: "/admin/products/1/edit",
    confirm: null,
    destructive: false,
    disabled: false,
    icon: null,
  } as const;
  const row = (id: number, name: string, link: string | null) => ({
    id,
    name,
    _actions: [view],
    _link: link,
    _variants: {},
  });
  const table = {
    ...emptyTable,
    rows: [row(1, "Shoes", "/admin/products/1/edit"), row(2, "Socks", null)],
    pagination: { page: 1, pageSize: 25, total: 2 },
  };

  await renderTable({ ...editProps, table, createProductUrl: null });

  await expect
    .element(page.getByRole("link", { name: "Shoes" }))
    .toHaveAttribute("href", "/admin/products/1/edit");
  await expect.element(page.getByRole("link", { name: "Socks" })).not.toBeInTheDocument();

  // The link stretches over the row, so the menu's trigger must sit above it.
  await page.getByRole("button", { name: "Actions for Shoes" }).click();
  await expect.element(page.getByRole("menuitem", { name: "View" })).toBeVisible();
  expect(window.location.pathname).not.toBe("/admin/products/1/edit");
});
