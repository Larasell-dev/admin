import { expect, it } from "vitest";
import { isSameSite } from "./isSameSite";

const { host, origin, protocol } = window.location;
const otherProtocol = protocol === "https:" ? "http:" : "https:";

it.each([
  "/admin/books/1",
  "admin/books/1",
  "?page=2",
  "#reviews",
  `${origin}/admin/books/1`,
  `//${host}/admin/books/1`,
])("treats %s as the same site", (url) => {
  expect(isSameSite(url)).toBe(true);
});

it.each([
  "https://books.test/1",
  "//books.test/1",
  `${otherProtocol}//${host}/admin`,
  `${protocol}//${window.location.hostname}:1/admin`,
  "mailto:admin@example.com",
])("treats %s as another site", (url) => {
  expect(isSameSite(url)).toBe(false);
});
