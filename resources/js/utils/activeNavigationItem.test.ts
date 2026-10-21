import { expect, it } from "vitest";
import type { NavigationItem } from "../types";
import { activeNavigationItem } from "./activeNavigationItem";

const item = (id: string, path: string): NavigationItem => ({
  id,
  label: id,
  url: `${window.location.origin}${path}`,
  icon: "Home",
});

const items = [
  item("home", "/admin"),
  item("categories", "/admin/categories"),
  item("customers", "/admin/customers"),
];

it.each([
  ["/admin", "home"],
  ["/admin/categories", "categories"],
  ["/admin/categories/12", "categories"],
  ["/admin/customers", "customers"],
])("marks the item for %s active", (path, id) => {
  expect(activeNavigationItem(items, path)?.id).toBe(id);
});

it.each([
  // Home has every other item under it, so it only matches its own page.
  "/admin/profile",
  // A prefix only counts at a path segment boundary.
  "/admin/categories-archive",
])("marks no item active for %s", (path) => {
  expect(activeNavigationItem(items, path)).toBeNull();
});
