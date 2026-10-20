import "../../css/app.css";
import { router } from "@inertiajs/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { page, userEvent } from "vitest/browser";
import { render } from "vitest-browser-react";
import type { TableAction, TableResource, TableRow } from "../types";
import ResourceTable from "./ResourceTable";

const archive: TableAction = {
  name: "archive",
  label: "Archive",
  method: "post",
  url: "/admin/tables/books/1/actions/archive",
  confirm: null,
  destructive: false,
  disabled: false,
};

const row = (id: number, title: string, actions: TableAction[] = []): TableRow => ({
  id,
  title,
  author: `Author ${id}`,
  _actions: actions,
});

const resource = (rows: TableRow[]): TableResource => ({
  title: "Books",
  emptyState: null,
  columns: [
    { name: "title", label: "Title", type: "text" },
    { name: "author", label: "Author", type: "text" },
  ],
  rows,
});

beforeEach(() => {
  vi.spyOn(router, "post").mockImplementation(() => {});
});

afterEach(() => {
  vi.restoreAllMocks();
});

// The data list shows below `lg`, the table from `lg` up; the other one is hidden.
describe.each([
  { layout: "data list", width: 400 },
  { layout: "table", width: 1280 },
])("in the $layout", ({ width }) => {
  beforeEach(async () => {
    await page.viewport(width, 800);
  });

  it("runs a row action from its menu", async () => {
    const screen = await render(<ResourceTable resource={resource([row(1, "Dune", [archive])])} />);

    await screen.getByRole("button", { name: "Actions for Dune" }).click();
    await page.getByRole("menuitem", { name: "Archive" }).click();

    expect(router.post).toHaveBeenCalledWith(archive.url, {}, expect.anything());
  });

  it("opens a row's actions from the keyboard", async () => {
    const screen = await render(<ResourceTable resource={resource([row(1, "Dune", [archive])])} />);

    await userEvent.tab();
    await expect.element(screen.getByRole("button", { name: "Actions for Dune" })).toHaveFocus();
    await userEvent.keyboard("{Enter}");
    await expect.element(page.getByRole("menuitem", { name: "Archive" })).toHaveFocus();
  });
});
