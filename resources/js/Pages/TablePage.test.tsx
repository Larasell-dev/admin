import "../../css/app.css";
import { expect, it } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import type { TableAction, TableResource } from "../types";
import TablePage from "./TablePage";

const addBook: TableAction = {
  label: "Add book",
  method: "get",
  url: "/admin/books/create",
  icon: "Add",
};

const importBooks: TableAction = {
  label: "Import books",
  method: "get",
  url: "https://example.com/import",
  icon: null,
};

const table = (total: number, overrides: Partial<TableResource> = {}): TableResource => ({
  title: "Books",
  emptyState: null,
  headerActions: [],
  columns: [{ name: "title", label: "Title", type: "text" }],
  rows: Array.from({ length: total }, (_, index) => ({
    id: index + 1,
    title: `Book ${index + 1}`,
    _actions: [],
    _link: null,
    _variants: {},
  })),
  pagination: { page: 1, pageSize: 25, total },
  ...overrides,
});

it("links each header action, in order, next to the title", async () => {
  await render(<TablePage table={table(1, { headerActions: [importBooks, addBook] })} />);

  const header = page.getByRole("banner");
  await expect.element(header.getByRole("heading", { name: "Books" })).toBeVisible();
  await expect
    .element(header.getByRole("link", { name: "Add book" }))
    .toHaveAttribute("href", "/admin/books/create");
  await expect
    .element(header.getByRole("link", { name: "Import books" }))
    .toHaveAttribute("href", "https://example.com/import");
  expect(
    header
      .getByRole("link")
      .elements()
      .map((link) => link.textContent),
  ).toEqual(["Import books", "Add book"]);
});

it("shows only the empty state, with its own actions, when the table has no rows", async () => {
  await render(
    <TablePage
      table={table(0, {
        headerActions: [importBooks],
        emptyState: { title: "No books yet", description: null, icon: null, actions: [addBook] },
      })}
    />,
  );

  await expect.element(page.getByText("No books yet")).toBeVisible();
  await expect
    .element(page.getByRole("link", { name: "Add book" }))
    .toHaveAttribute("href", "/admin/books/create");
  expect(page.getByRole("banner").elements()).toHaveLength(0);
  expect(page.getByRole("heading", { name: "Books" }).elements()).toHaveLength(0);
  expect(page.getByRole("link", { name: "Import books" }).elements()).toHaveLength(0);
});
