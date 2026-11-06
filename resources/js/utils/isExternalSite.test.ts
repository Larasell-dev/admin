import { expect, it } from "vitest";
import { isExternalSite } from "./isExternalSite";

it("treats URLs on this site as internal", () => {
  // Act & Assert
  expect(isExternalSite("/admin/books/1")).toBe(false);
  expect(isExternalSite(`${window.location.origin}/admin/books/1`)).toBe(false);
});

it("treats URLs on other sites as external", () => {
  // Act & Assert
  expect(isExternalSite("https://books.test/1")).toBe(true);
  expect(isExternalSite("//books.test/1")).toBe(true);
});
