import "../../css/app.css";
import { App } from "@inertiajs/react";
import { afterEach, beforeEach, expect, it } from "vitest";
import { page, userEvent } from "vitest/browser";
import { render } from "vitest-browser-react";
import CategoryLayout, { CategoryTreeTrigger } from "./CategoryLayout";

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
    <CategoryLayout>
      <CategoryTreeTrigger />
    </CategoryLayout>
  );
}

// Renders through Inertia's App, so the layout reads the page from usePage() as in the panel.
function renderIndex() {
  const initialPage = {
    component: "CategoryPage",
    url: "/admin/categories",
    version: null,
    props: {
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
