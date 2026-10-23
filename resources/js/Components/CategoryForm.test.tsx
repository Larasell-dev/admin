import "../../css/app.css";
import { App, Form } from "@inertiajs/react";
import type { ComponentProps } from "react";
import { describe, expect, it } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import CategoryForm from "./CategoryForm";
import UnsavedChangesToast, { UnsavedChangesToastHost } from "./UnsavedChangesToast";

type Category = ComponentProps<typeof CategoryForm>["category"];

const shoes = { name: "Shoes", slug: "shoes", visible: true };

/** Renders the form for the category, or for a new one without it. */
function renderPage(category?: Category) {
  function EditPage() {
    return (
      <>
        <Form action="/admin/categories/1" method="put" data-testid="form">
          <CategoryForm category={category} />
          <UnsavedChangesToast />
        </Form>
        <UnsavedChangesToastHost />
      </>
    );
  }

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
const nameInput = () => page.getByRole("textbox", { name: "Name" });
const slugInput = () => page.getByRole("textbox", { name: "Slug" });
const formData = () => new FormData(page.getByTestId("form").element() as HTMLFormElement);
const submitted = () => formData().get("status");

it("counts toggling the visibility as an unsaved change", async () => {
  await renderPage(shoes);
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
  await renderPage(shoes);
  await page.getByText("Visible in the store").click();
  await expect.element(visible()).not.toBeChecked();

  await toast().getByRole("button", { name: "Cancel" }).click();

  await expect.element(visible()).toBeChecked();
  expect(submitted()).toBe("visible");
  await expect.element(toast()).not.toBeInTheDocument();
});

describe("slug", () => {
  it("follows the name of a new category as a slug", async () => {
    await renderPage();

    await nameInput().fill("Men's Running Shoes");

    await expect.element(slugInput()).toHaveValue("mens-running-shoes");
    expect(formData().get("slug")).toBe("mens-running-shoes");

    await nameInput().fill("Straße & Café");
    await expect.element(slugInput()).toHaveValue("strasse-cafe");
  });

  it("doesn't count a slug that follows the name back to empty as a change", async () => {
    await renderPage();

    await nameInput().fill("Shoes");
    await expect.element(slugInput()).toHaveValue("shoes");
    await expect.element(toast()).toBeVisible();

    await nameInput().clear();
    await expect.element(slugInput()).toHaveValue("");
    await expect.element(toast()).not.toBeInTheDocument();
  });

  it("cuts the slug to the 255 characters the server allows", async () => {
    await renderPage();

    // Each "ß" becomes "ss", so the cut would end on the dash before "shoes".
    await nameInput().fill(`${"ß".repeat(127)} Shoes`);

    await expect.element(slugInput()).toHaveValue("s".repeat(254));
  });

  it("stops following the name once the slug is edited", async () => {
    await renderPage();
    await nameInput().fill("Shoes");
    await expect.element(slugInput()).toHaveValue("shoes");

    await slugInput().fill("footwear");
    await nameInput().fill("Running Shoes");

    await expect.element(nameInput()).toHaveValue("Running Shoes");
    await expect.element(slugInput()).toHaveValue("footwear");
  });

  it("follows the name again once the edited slug is cleared", async () => {
    await renderPage();
    await nameInput().fill("Shoes");
    await slugInput().fill("footwear");

    await slugInput().clear();
    await expect.element(slugInput()).toHaveValue("");
    await nameInput().fill("Boots");

    await expect.element(slugInput()).toHaveValue("boots");
  });

  it("follows the name again after cancelling the changes", async () => {
    await renderPage();
    await nameInput().fill("Shoes");
    await slugInput().fill("footwear");
    await nameInput().fill("Boots");
    await expect.element(slugInput()).toHaveValue("footwear");

    await toast().getByRole("button", { name: "Cancel" }).click();
    await expect.element(slugInput()).toHaveValue("");

    await nameInput().fill("Sneakers");
    await expect.element(slugInput()).toHaveValue("sneakers");
  });

  it("leaves the slug of an existing category alone", async () => {
    await renderPage(shoes);

    await nameInput().fill("Sneakers");
    await expect.element(nameInput()).toHaveValue("Sneakers");
    await expect.element(slugInput()).toHaveValue("shoes");

    await slugInput().clear();
    await nameInput().fill("Boots");
    await expect.element(nameInput()).toHaveValue("Boots");
    await expect.element(slugInput()).toHaveValue("");
  });
});
