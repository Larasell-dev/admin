import "../../css/app.css";
import { App } from "@inertiajs/react";
import { expect, it } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import Login from "./Login";

function renderLogin(devLoginUrl: string | null) {
  const initialPage = {
    component: "Login",
    url: "/admin/login",
    version: null,
    props: { errors: {}, loginUrl: "/admin/login", devLoginUrl },
  };

  return render(
    <App
      initialPage={initialPage as never}
      initialComponent={Login}
      resolveComponent={() => Login}
    />,
  );
}

it("hides the dev login button without a dev login URL", async () => {
  await renderLogin(null);

  await expect.element(page.getByRole("button", { name: "Sign in" })).toBeVisible();
  expect(page.getByRole("button", { name: /log in as/i }).elements()).toHaveLength(0);
});

it("links the dev login button to the dev login URL", async () => {
  await renderLogin("/admin/__dev/login");

  await expect
    .element(page.getByRole("button", { name: /log in as/i }))
    .toHaveAttribute("href", "/admin/__dev/login");
});
