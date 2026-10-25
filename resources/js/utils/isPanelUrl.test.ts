import { expect, it } from "vitest";
import { isPanelUrl } from "./isPanelUrl";

const { host, origin, protocol } = window.location;
const otherProtocol = protocol === "https:" ? "http:" : "https:";
const panelUrl = `${origin}/admin`;

it.each([
  "/admin",
  "/admin/",
  "/admin/books/1",
  "/admin?page=2",
  `${origin}/admin/books/1`,
  `//${host}/admin/books/1`,
])("treats %s as a panel page", (url) => {
  expect(isPanelUrl(url, panelUrl)).toBe(true);
});

it.each([
  "/",
  "/administrators",
  "/storage/products/dune.png",
  "https://books.test/admin",
  "//books.test/admin",
  `${otherProtocol}//${host}/admin`,
  `${protocol}//${window.location.hostname}:1/admin`,
  "mailto:admin@example.com",
])("treats %s as outside the panel", (url) => {
  expect(isPanelUrl(url, panelUrl)).toBe(false);
});
