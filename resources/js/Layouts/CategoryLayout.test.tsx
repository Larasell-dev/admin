import "../../css/app.css";
import { App } from "@inertiajs/react";
import { afterEach, beforeEach, expect, it } from "vitest";
import { page, userEvent } from "vitest/browser";
import { render } from "vitest-browser-react";
import CategoryLayout, { CategoryTreeTrigger } from "./CategoryLayout";
import SidebarLayout from "./SidebarLayout";

type Category = Larasell.Admin.Data.ViewModel.CategoryData;

const category = (id: number, name: string, children: Category[] = []) => ({
  id,
  name,
  visible: true,
  url: `/admin/categories/${id}`,
  hideUrl: `/admin/categories/${id}/hide`,
  unhideUrl: `/admin/categories/${id}/unhide`,
  deleteUrl: `/admin/categories/${id}`,
  createChildUrl: `/admin/categories/create?parent=${id}`,
  moveUrl: `/admin/categories/${id}/move`,
  children,
});

function CategoryPage() {
  return (
    <SidebarLayout>
      <CategoryLayout>
        <CategoryTreeTrigger />
      </CategoryLayout>
    </SidebarLayout>
  );
}

// Renders through Inertia's App, so the layout reads the page from usePage() as in the panel.
function renderIndex() {
  const initialPage = {
    component: "CategoryPage",
    url: "/admin/categories",
    version: null,
    props: {
      navigation: [
        { id: "home", label: "Home", url: "/admin", icon: "Home" },
        { id: "categories", label: "Categories", url: "/admin/categories", icon: "Folders" },
      ],
      admin: { name: "Ada Admin", email: "ada@example.com", avatarUrl: null },
      profileUrl: "/admin/profile",
      logoutUrl: "/admin/logout",
      categories: [category(1, "Clothing", [category(2, "Shirts")]), category(3, "Toys")],
      createUrl: "/admin/categories/create",
    },
  };

  return render(
    <App
      initialPage={initialPage as never}
      initialComponent={CategoryPage}
      resolveComponent={() => CategoryPage}
    />,
  );
}

// Below lg the tree lives in a dialog.
beforeEach(() => page.viewport(390, 800));
afterEach(() => page.viewport(1280, 720));

it("keeps the mobile tree's open categories when the dialog closes and opens again", async () => {
  await renderIndex();
  const dialog = page.getByRole("dialog", { name: "Categories" });
  const item = (name: string) => dialog.getByRole("treeitem", { name, exact: true });

  await page.getByRole("button", { name: "Open categories" }).click();
  await dialog.getByRole("button", { name: "Open Clothing", exact: true }).click();
  await expect.element(item("Shirts")).toBeVisible();

  await userEvent.keyboard("{Escape}");
  // Closed, whether it is removed or only hidden.
  await expect.poll(() => dialog.query()?.checkVisibility() ?? false).toBe(false);
  await page.getByRole("button", { name: "Open categories" }).click();

  await expect.element(item("Clothing")).toHaveAttribute("aria-expanded", "true");
  await expect.element(item("Shirts")).toBeVisible();
});

// Whether a click at the middle of the element would reach it, rather than something on top.
function isClickable(target: Element) {
  const box = target.getBoundingClientRect();
  const hit = document.elementFromPoint(box.x + box.width / 2, box.y + box.height / 2);
  return hit !== null && target.contains(hit);
}

// A modal dialog hides the rest of the page from assistive technology but not from the
// pointer, so the elements are taken before anything else opens.
async function openCategories() {
  await renderIndex();
  await page.getByRole("button", { name: "Open categories" }).click();
  const categories = page.getByRole("dialog", { name: "Categories" });
  // At the panel's right edge, which the narrower navigation drawer leaves uncovered.
  const actions = categories.getByRole("button", { name: "Actions for Toys" });
  await expect.poll(() => isClickable(actions.element())).toBe(true);

  return { actions: actions.element() };
}

it("puts the navigation drawer above the open category dialog", async () => {
  const { actions } = await openCategories();
  const openMenu = page.getByLabelText("Open menu").element();

  await userEvent.click(openMenu);
  await expect.element(page.getByRole("dialog", { name: "Navigation" })).toBeVisible();

  await expect.poll(() => isClickable(actions)).toBe(false);
});

it("puts the delete dialog above the category dialog it was opened from", async () => {
  const { actions } = await openCategories();
  const openMenu = page.getByLabelText("Open menu").element();

  await userEvent.click(actions);
  await page.getByRole("menuitem", { name: "Delete" }).click();
  const confirm = page.getByRole("alertdialog", { name: "Delete category" });
  await expect.element(confirm).toBeVisible();

  await expect.poll(() => isClickable(actions)).toBe(false);
  await expect.poll(() => isClickable(openMenu)).toBe(false);
  await expect
    .poll(() => isClickable(confirm.getByRole("button", { name: "Cancel" }).element()))
    .toBe(true);
});
