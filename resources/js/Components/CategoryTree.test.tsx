import "../../css/app.css";
import { router } from "@inertiajs/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { page, userEvent } from "vitest/browser";
import { render } from "vitest-browser-react";
import CategoryTree from "./CategoryTree";

type Category = Larasell.Admin.Data.ViewModel.CategoryData;

const category = (id: number, name: string, visible = true, children: Category[] = []) => ({
  id,
  name,
  visible,
  url: `/admin/categories/${id}`,
  hideUrl: `/admin/categories/${id}/hide`,
  unhideUrl: `/admin/categories/${id}/unhide`,
  deleteUrl: `/admin/categories/${id}`,
  createChildUrl: `/admin/categories/create?parent=${id}`,
  moveUrl: `/admin/categories/${id}/move`,
  children,
});

// Visible order while Clothing is closed: Clothing, Sale, Toys.
const categories = [
  category(1, "Clothing", true, [category(2, "Shirts")]),
  category(3, "Sale", false),
  category(4, "Toys"),
];

beforeEach(() => {
  vi.spyOn(router, "visit").mockImplementation(() => {});
  vi.spyOn(router, "post").mockImplementation(() => {});
  vi.spyOn(router, "prefetch").mockImplementation(() => {});
});

afterEach(() => {
  vi.restoreAllMocks();
});

async function renderTree() {
  return render(
    <>
      <h2 id="heading">Categories</h2>
      <CategoryTree categories={categories} labelledBy="heading" />
    </>,
  );
}

const item = (name: string) => page.getByRole("treeitem", { name, exact: true });
const trigger = (name: string) => page.getByRole("button", { name: `Actions for ${name}` });
// Hidden menu buttons are transparent, which toBeVisible() does not treat as hidden.
const shown = (name: string) =>
  expect.poll(() => getComputedStyle(trigger(name).element()).opacity);

describe("category menu", () => {
  it("does not follow the category's link when its menu is clicked", async () => {
    await renderTree();

    await item("Clothing").hover();
    await trigger("Clothing").click();

    await expect.element(page.getByRole("menu")).toBeVisible();
    expect(router.visit).not.toHaveBeenCalled();
    // The tree's own click handling does not run either: Clothing stays closed.
    await expect.element(item("Clothing")).toHaveAttribute("aria-expanded", "false");
  });

  it("only shows the menu button while its item is hovered or focused", async () => {
    await renderTree();
    // The pointer stays where the previous test left it, possibly over a row.
    await page.getByRole("heading", { name: "Categories" }).hover();

    await shown("Clothing").toBe("0");

    await item("Sale").hover();
    await shown("Sale").toBe("1");
    await shown("Clothing").toBe("0");

    await userEvent.tab();
    await expect.element(item("Clothing")).toHaveFocus();
    await shown("Clothing").toBe("1");
  });

  it("does not show the menu button for an item focused by a click", async () => {
    await renderTree();

    await item("Sale").click();
    await expect.element(item("Sale")).toHaveFocus();
    await page.getByRole("heading", { name: "Categories" }).hover();

    await shown("Sale").toBe("0");
  });

  it("puts only the focused item's menu button in the tab order", async () => {
    render(
      <>
        <h2 id="heading">Categories</h2>
        <CategoryTree categories={categories} labelledBy="heading" />
        <button type="button">After the tree</button>
      </>,
    );

    await userEvent.tab();
    await userEvent.tab();
    await expect.element(trigger("Clothing")).toHaveFocus();

    // Sale's menu button comes next in the page, but Sale is not the focused item.
    await userEvent.tab();
    await expect.element(page.getByRole("button", { name: "After the tree" })).toHaveFocus();
  });

  it("returns focus to the menu button when the menu closes", async () => {
    await renderTree();
    await page.getByRole("heading", { name: "Categories" }).hover();

    await userEvent.tab();
    await userEvent.tab();
    await userEvent.keyboard("{Enter}");
    await expect.element(page.getByRole("menu")).toBeVisible();
    await userEvent.keyboard("{Escape}");

    await expect.element(page.getByRole("menu")).not.toBeInTheDocument();
    await expect.element(trigger("Clothing")).toHaveFocus();
  });

  it("stays visible while open", async () => {
    await renderTree();

    await item("Clothing").hover();
    await trigger("Clothing").click();
    // The open menu covers the page, so move the pointer off the row with force.
    await page.getByRole("heading", { name: "Categories" }).hover({ force: true });

    await shown("Clothing").toBe("1");
  });

  it("hides a visible category", async () => {
    await renderTree();

    await item("Clothing").hover();
    await trigger("Clothing").click();
    await expect
      .element(page.getByRole("menuitem", { name: "Make visible" }))
      .not.toBeInTheDocument();
    await page.getByRole("menuitem", { name: "Hide" }).click();

    expect(router.post).toHaveBeenCalledExactlyOnceWith(
      "/admin/categories/1/hide",
      {},
      expect.objectContaining({ preserveScroll: true }),
    );
    expect(router.visit).not.toHaveBeenCalled();
  });

  it("makes a hidden category visible again", async () => {
    await renderTree();

    await item("Sale").hover();
    await trigger("Sale").click();
    await expect.element(page.getByRole("menuitem", { name: "Hide" })).not.toBeInTheDocument();
    await page.getByRole("menuitem", { name: "Make visible" }).click();

    expect(router.post).toHaveBeenCalledExactlyOnceWith(
      "/admin/categories/3/unhide",
      {},
      expect.objectContaining({ preserveScroll: true }),
    );
  });
});

describe("keyboard navigation with menus", () => {
  it("moves through the items with the arrow keys and never onto a menu button", async () => {
    await renderTree();
    await userEvent.tab();
    await expect.element(item("Clothing")).toHaveFocus();

    await userEvent.keyboard("{ArrowDown}");
    await expect.element(item("Sale")).toHaveFocus();
    await userEvent.keyboard("{ArrowDown}");
    await expect.element(item("Toys")).toHaveFocus();
    await userEvent.keyboard("{ArrowUp}");
    await expect.element(item("Sale")).toHaveFocus();

    await userEvent.keyboard("{Home}{ArrowRight}");
    await expect.element(item("Clothing")).toHaveAttribute("aria-expanded", "true");
    await expect.element(item("Clothing")).toHaveFocus();
    await userEvent.keyboard("{ArrowRight}");
    await expect.element(item("Shirts")).toHaveFocus();
    await userEvent.keyboard("{ArrowLeft}");
    await expect.element(item("Clothing")).toHaveFocus();

    await userEvent.keyboard("{End}");
    await expect.element(item("Toys")).toHaveFocus();
    // Type-ahead wraps around to Shirts, skipping the menu buttons.
    await userEvent.keyboard("s");
    await expect.element(item("Shirts")).toHaveFocus();
  });

  it("moves to an item's menu button with Tab and back with Shift+Tab, then arrows work again", async () => {
    await renderTree();
    await userEvent.tab();
    await userEvent.keyboard("{ArrowDown}");
    await expect.element(item("Sale")).toHaveFocus();

    await userEvent.tab();
    await expect.element(trigger("Sale")).toHaveFocus();

    await userEvent.tab({ shift: true });
    await expect.element(item("Sale")).toHaveFocus();

    await userEvent.keyboard("{ArrowDown}");
    await expect.element(item("Toys")).toHaveFocus();
    await userEvent.keyboard("{ArrowUp}{ArrowUp}");
    await expect.element(item("Clothing")).toHaveFocus();
  });

  it("opens the menu with Arrow Down without moving to the next item", async () => {
    await renderTree();
    await userEvent.tab();
    await userEvent.tab();
    await expect.element(trigger("Clothing")).toHaveFocus();

    await userEvent.keyboard("{ArrowDown}");

    await expect.element(page.getByRole("menu")).toBeVisible();
    await expect.element(page.getByRole("menuitem", { name: "Add subcategory" })).toHaveFocus();
    await expect.element(item("Sale")).not.toHaveFocus();
    // The menu takes focus either way, so also check the tree did not move its tab stop to Sale.
    await expect.element(item("Clothing")).toHaveAttribute("tabindex", "0");
    await expect.element(item("Sale")).toHaveAttribute("tabindex", "-1");
    await expect.element(item("Clothing")).toHaveAttribute("aria-expanded", "false");

    // Closing the menu and going back to the item leaves the tree working as before.
    await userEvent.keyboard("{Escape}");
    await expect.element(trigger("Clothing")).toHaveFocus();
    await userEvent.tab({ shift: true });
    await userEvent.keyboard("{ArrowDown}");
    await expect.element(item("Sale")).toHaveFocus();
  });
});

describe("path to the current category", () => {
  // Built anew for every render, like the props of each page load.
  const nested = () => [
    category(1, "Clothing", true, [category(2, "Men", true, [category(5, "Shirts")])]),
    category(3, "Sale", false, [category(6, "Clearance")]),
  ];
  const tree = (currentId: number) => (
    <>
      <h2 id="heading">Categories</h2>
      <CategoryTree categories={nested()} labelledBy="heading" currentId={currentId} />
    </>
  );

  it("opens every category above the current one, which can still be closed", async () => {
    await render(tree(5));

    await expect.element(item("Clothing")).toHaveAttribute("aria-expanded", "true");
    await expect.element(item("Men")).toHaveAttribute("aria-expanded", "true");
    await expect.element(item("Shirts")).toHaveAttribute("aria-current", "page");
    await expect.element(item("Sale")).toHaveAttribute("aria-expanded", "false");

    await userEvent.tab();
    await userEvent.keyboard("{ArrowLeft}{ArrowLeft}{ArrowLeft}{ArrowLeft}");

    await expect.element(item("Clothing")).toHaveAttribute("aria-expanded", "false");
    await expect.element(item("Shirts")).not.toBeInTheDocument();
  });

  it("opens the path again when the page loads again after it was closed", async () => {
    const screen = await render(tree(5));
    await userEvent.tab();
    await userEvent.keyboard("{ArrowLeft}{ArrowLeft}{ArrowLeft}{ArrowLeft}");
    await expect.element(item("Clothing")).toHaveAttribute("aria-expanded", "false");

    // The layout stays mounted across Inertia visits, such as after saving the category.
    await screen.rerender(tree(5));

    await expect.element(item("Clothing")).toHaveAttribute("aria-expanded", "true");
    await expect.element(item("Men")).toHaveAttribute("aria-expanded", "true");
    await expect.element(item("Shirts")).toBeVisible();
  });

  it("opens the path to a category that becomes current and keeps other open categories open", async () => {
    const screen = await render(tree(5));

    await screen.rerender(tree(6));

    await expect.element(item("Sale")).toHaveAttribute("aria-expanded", "true");
    await expect.element(item("Clearance")).toHaveAttribute("aria-current", "page");
    await expect.element(item("Clothing")).toHaveAttribute("aria-expanded", "true");
  });
});
