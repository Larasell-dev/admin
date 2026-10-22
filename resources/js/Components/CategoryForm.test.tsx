import "../../css/app.css";
import { App, Form } from "@inertiajs/react";
import { expect, it } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import CategoryForm from "./CategoryForm";
import UnsavedChangesToast, { UnsavedChangesToastHost } from "./UnsavedChangesToast";

function EditPage() {
  return (
    <>
      <Form action="/admin/categories/1" method="put" data-testid="form">
        <CategoryForm category={{ name: "Shoes", slug: "shoes", visible: true }} />
        <UnsavedChangesToast />
      </Form>
      <UnsavedChangesToastHost />
    </>
  );
}

function renderPage() {
  const initialPage = {
    component: "EditPage",
    url: "/admin/categories/1",
    version: null,
    props: {},
  };

  return render(
    <App
      initialPage={initialPage as never}
      initialComponent={EditPage}
      resolveComponent={() => EditPage}
    />,
  );
}

const toast = () => page.getByRole("region", { name: "Unsaved changes" });
const visible = () => page.getByRole("checkbox", { name: "Visible in the store" });
const submitted = () =>
  new FormData(page.getByTestId("form").element() as HTMLFormElement).get("status");

it("counts toggling the visibility as an unsaved change", async () => {
  await renderPage();
  await expect.element(visible()).toBeChecked();
  expect(submitted()).toBe("visible");

  await page.getByText("Visible in the store").click();
  await expect.element(visible()).not.toBeChecked();
  expect(submitted()).toBe("hidden");
  await expect.element(toast()).toBeVisible();

  await page.getByText("Visible in the store").click();
  await expect.element(toast()).not.toBeInTheDocument();
});

it("shows the visibility it was reset to on cancel", async () => {
  await renderPage();
  await page.getByText("Visible in the store").click();
  await expect.element(visible()).not.toBeChecked();

  await toast().getByRole("button", { name: "Cancel" }).click();

  await expect.element(visible()).toBeChecked();
  expect(submitted()).toBe("visible");
  await expect.element(toast()).not.toBeInTheDocument();
});
