import "../../css/app.css";
import { router } from "@inertiajs/react";
import { type MockInstance, afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { page, userEvent } from "vitest/browser";
import { render } from "vitest-browser-react";
import type { TableRowAction, TablePagination, TableResource, TableRow } from "../types";
import ResourceTable from "./ResourceTable";

const archive: TableRowAction = {
  name: "archive",
  label: "Archive",
  method: "post",
  url: "/admin/tables/books/1/actions/archive",
  confirm: null,
  destructive: false,
  disabled: false,
  icon: null,
};

const row = (
  id: number,
  title: string,
  link: string | null,
  actions: TableRowAction[] = [],
): TableRow => ({
  id,
  title,
  author: `Author ${id}`,
  _actions: actions,
  _link: link,
  _variants: {},
});

const resource = (
  rows: TableRow[],
  pagination: TablePagination = { page: 1, pageSize: 25, total: rows.length },
): TableResource => ({
  title: "Books",
  emptyState: null,
  headerActions: [],
  columns: [
    { name: "title", label: "Title", type: "text" },
    { name: "author", label: "Author", type: "text" },
  ],
  rows,
  pagination,
});

let visit: MockInstance<typeof router.visit>;
let post: MockInstance<typeof router.post>;
let get: MockInstance<typeof router.get>;

beforeEach(() => {
  visit = vi.spyOn(router, "visit").mockImplementation(() => {});
  post = vi.spyOn(router, "post").mockImplementation(() => {});
  get = vi.spyOn(router, "get").mockImplementation(() => {});
});

afterEach(() => {
  vi.restoreAllMocks();
});

// The data list shows below `lg`, the table from `lg` up; the other one is hidden.
describe.each([
  { layout: "data list", role: "list", width: 400 },
  { layout: "table", role: "table", width: 1280 },
] as const)("in the $layout", ({ role, width }) => {
  beforeEach(async () => {
    await page.viewport(width, 800);
  });

  it("links a row through its primary value, named after it", async () => {
    const screen = await render(
      <ResourceTable resource={resource([row(1, "Dune", "/admin/books/1")])} />,
    );

    const link = screen.getByRole("link", { name: "Dune" });
    await expect.element(link).toHaveAttribute("href", "/admin/books/1");
    expect(screen.getByRole("link").elements()).toHaveLength(1);
  });

  it("renders no link for rows whose link is null", async () => {
    const screen = await render(
      <ResourceTable
        resource={resource([row(1, "Dune", "/admin/books/1"), row(2, "Beowulf", null)])}
      />,
    );

    await expect.element(screen.getByRole(role).getByText("Beowulf")).toBeVisible();
    expect(
      screen
        .getByRole("link")
        .elements()
        .map((link) => link.textContent),
    ).toEqual(["Dune"]);
  });

  it("shows a badge column's value as its text, not its variant", async () => {
    const screen = await render(
      <ResourceTable
        resource={{
          ...resource([
            { ...row(1, "Dune", null), status: "Paid", _variants: { status: "success" } },
            { ...row(2, "Emma", null), status: null, _variants: { status: "neutral" } },
          ]),
          columns: [
            { name: "title", label: "Title", type: "text" },
            { name: "status", label: "Status", type: "badge" },
          ],
        }}
      />,
    );

    const list = screen.getByRole(role);
    await expect.element(list.getByText("Paid", { exact: true })).toBeVisible();
    // The variant only colours the badge; it is never shown as text.
    expect(list.getByText(/success|neutral/).elements()).toHaveLength(0);
  });

  it("visits same-site links with Inertia when clicking anywhere in the row", async () => {
    const screen = await render(
      <ResourceTable resource={resource([row(1, "Dune", "/admin/books/1")])} />,
    );

    // The link stretches over the row, so it receives the click on another field.
    await screen.getByRole(role).getByText("Author 1").click({ force: true });

    expect(visit).toHaveBeenCalledOnce();
    expect(visit.mock.calls[0]?.[0]).toContain("/admin/books/1");
  });

  it("follows links to other sites as plain anchors", async () => {
    const screen = await render(
      <ResourceTable resource={resource([row(1, "Dune", "https://books.test/1")])} />,
    );
    // Runs after React's handlers, so Inertia would already have intercepted the click.
    const stayOnPage = (event: Event) => event.preventDefault();
    window.addEventListener("click", stayOnPage);

    try {
      await screen.getByRole("link", { name: "Dune" }).click();
    } finally {
      window.removeEventListener("click", stayOnPage);
    }

    await expect
      .element(screen.getByRole("link", { name: "Dune" }))
      .toHaveAttribute("href", "https://books.test/1");
    expect(visit).not.toHaveBeenCalled();
  });

  it("keeps the row's actions outside the link, after it in the tab order", async () => {
    const screen = await render(
      <ResourceTable resource={resource([row(1, "Dune", "/admin/books/1", [archive])])} />,
    );
    const link = screen.getByRole("link", { name: "Dune" });
    const trigger = screen.getByRole("button", { name: "Actions for Dune" });

    expect(trigger.element().closest("a")).toBeNull();

    await userEvent.tab();
    await expect.element(link).toHaveFocus();
    await userEvent.tab();
    await expect.element(trigger).toHaveFocus();
  });

  it("leaves out the actions menu on rows without actions", async () => {
    const screen = await render(
      <ResourceTable
        resource={resource([row(1, "Dune", null, [archive]), row(2, "Emma", null)])}
      />,
    );

    await expect.element(screen.getByRole("button", { name: "Actions for Dune" })).toBeVisible();
    await expect
      .element(screen.getByRole("button", { name: "Actions for Emma" }))
      .not.toBeInTheDocument();
  });

  it("opens a row's actions from the keyboard", async () => {
    const screen = await render(
      <ResourceTable resource={resource([row(1, "Dune", null, [archive])])} />,
    );

    await userEvent.tab();
    await expect.element(screen.getByRole("button", { name: "Actions for Dune" })).toHaveFocus();
    await userEvent.keyboard("{Enter}");
    await expect.element(page.getByRole("menuitem", { name: "Archive" })).toHaveFocus();
  });

  it("shows an action's icon before its label, hidden from assistive tech", async () => {
    const remove = { ...archive, name: "delete", label: "Delete", icon: "DeleteBin" };
    const screen = await render(
      <ResourceTable resource={resource([row(1, "Dune", null, [remove, archive])])} />,
    );

    await screen.getByRole("button", { name: "Actions for Dune" }).click();
    const withIcon = page.getByRole("menuitem", { name: "Delete" });
    const withoutIcon = page.getByRole("menuitem", { name: "Archive" });

    await expect.element(withIcon).toBeVisible();
    const icon = withIcon.element().querySelector("svg");
    expect(icon).not.toBeNull();
    expect(icon?.getAttribute("aria-hidden")).toBe("true");
    expect(withIcon.element().firstElementChild).toBe(icon);
    expect(withoutIcon.element().querySelector("svg")).toBeNull();
  });

  it("runs a row action from its menu without following the row link", async () => {
    const screen = await render(
      <ResourceTable resource={resource([row(1, "Dune", "/admin/books/1", [archive])])} />,
    );

    await screen.getByRole("button", { name: "Actions for Dune" }).click();
    await page.getByRole("menuitem", { name: "Archive" }).click();

    expect(post).toHaveBeenCalledWith(archive.url, {}, expect.anything());
    expect(visit).not.toHaveBeenCalled();
  });

  it("shows which page of how many is on screen", async () => {
    const screen = await render(
      <ResourceTable
        resource={resource([row(26, "Dune", null)], { page: 2, pageSize: 25, total: 60 })}
      />,
    );

    const pagination = screen.getByRole("navigation", { name: "Pagination" });
    await expect.element(pagination.getByText("Page 2 of 3")).toBeVisible();
  });

  it("requests the next page, keeping the page size and scroll position", async () => {
    const screen = await render(
      <ResourceTable
        resource={resource([row(51, "Dune", null)], { page: 2, pageSize: 50, total: 120 })}
      />,
    );

    await screen.getByRole("button", { name: "Next page" }).click();

    expect(get).toHaveBeenCalledOnce();
    expect(get.mock.calls[0]?.[0]).toBe(window.location.pathname);
    expect(get.mock.calls[0]?.[1]).toEqual({ page: 3, per_page: 50 });
    expect(get.mock.calls[0]?.[2]).toMatchObject({ preserveScroll: true });
  });
});
