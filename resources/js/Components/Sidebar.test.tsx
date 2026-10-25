import "../../css/app.css";
import { App, router } from "@inertiajs/react";
import { type MockInstance, afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import Sidebar from "./Sidebar";

type Item = Larasell.Admin.Data.ViewModel.NavigationItemData;

const item = (label: string, path: string, children: Item[] = []): Item => ({
  id: label.toLowerCase(),
  label,
  url: `${window.location.origin}${path}`,
  icon: "Home",
  children,
});

const defaultNavigation = [
  item("Home", "/admin"),
  item("Categories", "/admin/categories"),
  item("Customers", "/admin/customers"),
  item("Settings", "/admin/settings", [
    item("Taxes", "/admin/settings/taxes"),
    item("Shipping", "/admin/settings/shipping"),
  ]),
];

// Renders through Inertia's App, so the sidebar reads the page from usePage() as in the panel.
function renderAt(url: string, navigation: Item[] = defaultNavigation) {
  const initialPage = {
    component: "Sidebar",
    url,
    version: null,
    props: {
      navigation,
      admin: { name: "Ada Lovelace", email: "ada@example.com", avatarUrl: null },
      profileUrl: "/admin/profile",
      logoutUrl: "/admin/logout",
      panelUrl: "/admin",
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

const links = () => page.getByRole("navigation").getByRole("link").elements();

const current = () =>
  links()
    .filter((link) => link.getAttribute("aria-current") === "page")
    .map((link) => link.textContent);

const visible = () => links().map((link) => link.textContent);

it.each([
  ["/admin", "Home"],
  ["/admin/categories", "Categories"],
  ["/admin/categories/12?tab=products", "Categories"],
  ["/admin/customers", "Customers"],
  ["/admin/settings", "Settings"],
  ["/admin/settings/taxes", "Taxes"],
  ["/admin/settings/shipping/eu", "Shipping"],
  // Settings' own children sit under it, but they don't stop it from matching its other pages.
  ["/admin/settings/42", "Settings"],
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

it.each([
  ["/admin", ["Home", "Categories", "Customers", "Settings"]],
  ["/admin/settings", ["Home", "Categories", "Customers", "Settings", "Taxes", "Shipping"]],
  ["/admin/settings/taxes", ["Home", "Categories", "Customers", "Settings", "Taxes", "Shipping"]],
])("shows a group's children only inside that group, at %s", async (url, labels) => {
  await renderAt(url);

  expect(visible()).toEqual(labels);
});

it("lists children in a group named after their parent", async () => {
  await renderAt("/admin/settings/taxes");

  const group = page.getByRole("list", { name: "Settings" });
  expect(
    group
      .getByRole("link")
      .elements()
      .map((link) => link.textContent),
  ).toEqual(["Taxes", "Shipping"]);
});

// Archive is Settings' child, but its URL sits under Categories.
it.each([
  ["/admin/categories/12", "Categories"],
  ["/admin/categories/archive", "Archive"],
  ["/admin/categories/archive/3", "Archive"],
])("only lets items in the same group shadow an item, at %s", async (url, label) => {
  await renderAt(url, [
    item("Home", "/admin"),
    item("Categories", "/admin/categories"),
    item("Settings", "/admin/settings", [item("Archive", "/admin/categories/archive")]),
  ]);

  expect(current()).toEqual([label]);
});

let visit: MockInstance<typeof router.visit>;

describe("expanding a group", () => {
  const toggle = (name: string) => page.getByRole("button", { name, exact: true });

  beforeEach(() => {
    visit = vi.spyOn(router, "visit").mockImplementation(() => {});
    vi.spyOn(router, "prefetch").mockImplementation(() => {});
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("shows a toggle only for items with children", async () => {
    await renderAt("/admin");

    expect(
      page
        .getByRole("navigation")
        .getByRole("button")
        .elements()
        .map((button) => button.getAttribute("aria-label")),
    ).toEqual(["Open Settings"]);
  });

  it("opens and closes a closed group with its toggle", async () => {
    await renderAt("/admin");

    await toggle("Open Settings").click();
    await expect.element(toggle("Close Settings")).toHaveAttribute("aria-expanded", "true");
    expect(visible()).toEqual(["Home", "Categories", "Customers", "Settings", "Taxes", "Shipping"]);

    await toggle("Close Settings").click();
    await expect.element(toggle("Open Settings")).toHaveAttribute("aria-expanded", "false");
    expect(visible()).toEqual(["Home", "Categories", "Customers", "Settings"]);
  });

  it("closes the group of the current page", async () => {
    await renderAt("/admin/settings/taxes");

    await toggle("Close Settings").click();

    await expect.element(toggle("Open Settings")).toHaveAttribute("aria-expanded", "false");
    expect(visible()).toEqual(["Home", "Categories", "Customers", "Settings"]);
  });

  it("points the toggle at the group it controls", async () => {
    await renderAt("/admin/settings");

    const group = page.getByRole("list", { name: "Settings" }).element();
    await expect.element(toggle("Close Settings")).toHaveAttribute("aria-controls", group.id);
  });

  it("keeps the user on the current page when toggling", async () => {
    await renderAt("/admin/customers");

    await toggle("Open Settings").click();
    await toggle("Close Settings").click();

    expect(visit).not.toHaveBeenCalled();
    expect(current()).toEqual(["Customers"]);

    // Control: the spy does see a navigation, so its silence above means something.
    await page.getByRole("link", { name: "Settings", exact: true }).click();
    expect(visit).toHaveBeenCalledOnce();
  });
});
