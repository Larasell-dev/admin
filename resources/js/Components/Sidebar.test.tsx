import "../../css/app.css";
import { App } from "@inertiajs/react";
import { expect, it } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import Sidebar from "./Sidebar";

const item = (label: string, path: string) => ({
  id: label.toLowerCase(),
  label,
  url: `${window.location.origin}${path}`,
  icon: "Home",
});

// Renders through Inertia's App, so the sidebar reads the page from usePage() as in the panel.
function renderAt(url: string) {
  const initialPage = {
    component: "Sidebar",
    url,
    version: null,
    props: {
      navigation: [
        item("Home", "/admin"),
        item("Categories", "/admin/categories"),
        item("Customers", "/admin/customers"),
      ],
      admin: { name: "Ada Lovelace", email: "ada@example.com", avatarUrl: null },
      profileUrl: "/admin/profile",
      logoutUrl: "/admin/logout",
    },
  };

  return render(
    <App
      initialPage={initialPage as never}
      initialComponent={Sidebar}
      resolveComponent={() => Sidebar}
    />,
  );
}

const current = () =>
  page
    .getByRole("navigation")
    .getByRole("link")
    .elements()
    .filter((link) => link.getAttribute("aria-current") === "page")
    .map((link) => link.textContent);

it.each([
  ["/admin", "Home"],
  ["/admin/categories", "Categories"],
  ["/admin/categories/12?tab=products", "Categories"],
  ["/admin/customers", "Customers"],
])("marks the item for %s as the current page", async (url, label) => {
  await renderAt(url);

  expect(current()).toEqual([label]);
});

it.each([
  // Home has every other item under it, so it only matches its own page.
  "/admin/profile",
  // A prefix only counts at a path segment boundary.
  "/admin/categories-archive",
])("marks no item as the current page for %s", async (url) => {
  await renderAt(url);

  expect(current()).toEqual([]);
});
