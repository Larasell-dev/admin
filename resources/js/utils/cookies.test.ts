import { afterEach, expect, it } from "vitest";
import { documentCookies } from "./cookies";

afterEach(() => {
  for (const name of ["XSRF-TOKEN", "XSRF-TOKEN-OLD", "token"]) documentCookies.delete(name);
});

it("reads a cookie by its exact name, not one that starts with it", () => {
  document.cookie = "XSRF-TOKEN-OLD=old; path=/";
  expect(documentCookies.get("XSRF-TOKEN")).toBeUndefined();

  document.cookie = "XSRF-TOKEN=new; path=/";
  expect(documentCookies.get("XSRF-TOKEN")).toBe("new");
  expect(documentCookies.get("XSRF-TOKEN-OLD")).toBe("old");
});

it("decodes values the server encoded, including ones with = in them", () => {
  // Laravel's XSRF-TOKEN is URL-encoded base64, which ends in =.
  document.cookie = "XSRF-TOKEN=eyJpdiI6IjEifQ%3D%3D; path=/";
  expect(documentCookies.get("XSRF-TOKEN")).toBe("eyJpdiI6IjEifQ==");
});

it("writes values that read back the same, and deletes them", () => {
  documentCookies.set("token", "a=b; c");
  expect(documentCookies.get("token")).toBe("a=b; c");

  documentCookies.delete("token");
  expect(documentCookies.get("token")).toBeUndefined();
  expect(document.cookie).not.toContain("token=");
});
