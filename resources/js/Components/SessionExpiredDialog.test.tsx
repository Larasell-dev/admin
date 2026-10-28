import "../../css/app.css";
import { App, http, router } from "@inertiajs/react";
import { afterEach, beforeEach, expect, it, vi } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import { type Cookies, CookiesContext, memoryCookies } from "../utils/cookies";
import SessionExpiredDialog from "./SessionExpiredDialog";

const props = {
  errors: {},
  admin: { name: "Ada Lovelace", email: "ada@example.com", avatarUrl: null },
  loginUrl: "/admin/login",
};

function renderPage() {
  const initialPage = { component: "Page", url: "/admin/profile", version: null, props };

  return render(
    <CookiesContext value={cookies}>
      <App
        initialPage={initialPage as never}
        initialComponent={SessionExpiredDialog}
        resolveComponent={() => SessionExpiredDialog}
      />
    </CookiesContext>,
  );
}

type Request = { method: string; url: string; data?: unknown };

/** Answers sign-in attempts with the given responses, in order, and records every request. */
function fakeServer(...responses: { status: number; data: string }[]) {
  const requests: Request[] = [];
  vi.spyOn(http, "getClient").mockReturnValue({
    async request({ method, url, data }: Request) {
      requests.push({ method, url, data });
      return { ...responses.shift()!, headers: {} };
    },
  } as never);
  return requests;
}

let cookies: Cookies;

const expireSession = () => {
  cookies.delete("XSRF-TOKEN");
  document.dispatchEvent(new Event("visibilitychange"));
};

const dialog = () => page.getByRole("alertdialog", { name: "Your session expired" });

beforeEach(() => {
  cookies = memoryCookies({ "XSRF-TOKEN": "token" });
  // Starting a new session hands out a new token.
  vi.spyOn(window, "fetch").mockImplementation(async () => {
    cookies.set("XSRF-TOKEN", "new-token");
    return new Response(null);
  });
});

afterEach(() => vi.restoreAllMocks());

it("asks for the password once the session expired and signs back in on the same page", async () => {
  const requests = fakeServer(
    {
      status: 422,
      data: JSON.stringify({ errors: { email: ["These credentials do not match."] } }),
    },
    { status: 204, data: "" },
  );
  await renderPage();
  await expect.element(dialog()).not.toBeInTheDocument();

  expireSession();
  await expect.element(dialog()).toBeVisible();

  await dialog().getByLabelText("Password").fill("wrong");
  await dialog().getByRole("button", { name: "Continue" }).click();
  await expect.element(dialog().getByText("These credentials do not match.")).toBeVisible();
  // A token for the sign-in, fetched once since the new session keeps it.
  expect(window.fetch).toHaveBeenCalledTimes(1);

  await dialog().getByLabelText("Password").fill("secret");
  await dialog().getByRole("button", { name: "Continue" }).click();
  // Animates out rather than vanishing with the form.
  await expect.element(dialog()).toHaveAttribute("data-ending-style");
  await expect.element(dialog()).not.toBeInTheDocument();

  // The next time it asks, the password is empty again.
  expireSession();
  await expect.element(dialog().getByLabelText("Password")).toHaveValue("");

  expect(requests.map(({ data }) => JSON.parse(data as string))).toEqual([
    { email: "ada@example.com", password: "wrong" },
    { email: "ada@example.com", password: "secret" },
  ]);
  expect(requests.every(({ method, url }) => method === "post" && url === "/admin/login")).toBe(
    true,
  );
});

it("holds back visits while the session is expired, except to the sign-in page", async () => {
  const signInPage = { component: "Page", url: "/admin/login", version: null, props };
  const requests = fakeServer({ status: 200, data: JSON.stringify(signInPage) });
  await renderPage();
  cookies.delete("XSRF-TOKEN");

  router.visit("/admin/categories");

  await expect.element(dialog()).toBeVisible();
  expect(requests).toEqual([]);

  await dialog().getByRole("link", { name: "Use another account" }).click();

  await expect
    .poll(() => requests.map(({ method, url }) => `${method} ${new URL(url).pathname}`))
    .toEqual(["get /admin/login"]);
});
