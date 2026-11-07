import "../../../css/app.css";
import { App, Form } from "@inertiajs/react";
import type { ComponentProps } from "react";
import { describe, expect, it } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import CategoryForm from "./CategoryForm";
import UnsavedChangesToast, { UnsavedChangesToastHost } from "../UnsavedChangesToast";

type Category = ComponentProps<typeof CategoryForm>["category"];

const shoes = {
  name: "Shoes",
  fallbackName: "Shoes",
  visible: true,
};

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
const formData = () => new FormData(page.getByTestId("form").element() as HTMLFormElement);
const submitted = () => formData().get("status");

it("counts toggling the visibility as an unsaved change", async () => {
  // Arrange
  await renderPage(shoes);
  await expect.element(visible()).toBeChecked();
  expect(submitted()).toBe("visible");

  // Act
  await page.getByText("Visible in the store").click();

  // Assert
  await expect.element(visible()).not.toBeChecked();
  expect(submitted()).toBe("hidden");
  await expect.element(toast()).toBeVisible();

  await page.getByText("Visible in the store").click();
  await expect.element(toast()).not.toBeInTheDocument();
});

it("shows the visibility it was reset to on cancel", async () => {
  // Arrange
  await renderPage(shoes);
  await page.getByText("Visible in the store").click();
  await expect.element(visible()).not.toBeChecked();

  // Act
  await toast().getByRole("button", { name: "Cancel" }).click();

  // Assert
  await expect.element(visible()).toBeChecked();
  expect(submitted()).toBe("visible");
  await expect.element(toast()).not.toBeInTheDocument();
});

describe("translation", () => {
  const untranslated = { ...shoes, name: null };

  it("leaves an untranslated name empty and shows what the store shows instead", async () => {
    // Act
    await renderPage(untranslated);

    // Assert
    await expect.element(nameInput()).toHaveValue("");
    await expect.element(nameInput()).toHaveAttribute("placeholder", "Shoes");
  });
});
