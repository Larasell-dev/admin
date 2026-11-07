import "../../../../css/app.css";
import { App, Form } from "@inertiajs/react";
import { expect, it } from "vitest";
import { page, userEvent } from "vitest/browser";
import { render } from "vitest-browser-react";
import FormSelect from "./FormSelect";
import UnsavedChangesToast, { UnsavedChangesToastHost } from "./UnsavedChangesToast";

const languages = [
  { value: "de-DE", label: "German" },
  { value: "fr-FR", label: "French" },
  { value: "it-IT", label: "Italian" },
];

function EditPage() {
  return (
    <>
      <Form action="/admin/things/1" method="put" aria-label="Thing">
        <FormSelect
          label="Languages"
          name="locales[]"
          multiple
          items={languages}
          defaultValue={["fr-FR"]}
        />
        <FormSelect label="Default language" name="locale" items={languages} defaultValue={[]} />
        <UnsavedChangesToast />
      </Form>
      <UnsavedChangesToastHost />
    </>
  );
}

const renderPage = () =>
  render(
    <App
      initialPage={
        { component: "EditPage", url: "/admin/things/1", version: null, props: {} } as never
      }
      initialComponent={EditPage}
      resolveComponent={() => EditPage}
    />,
  );

const submitted = () =>
  [...new FormData(page.getByRole("form", { name: "Thing" }).element() as HTMLFormElement)].map(
    ([key, value]) => `${key}=${value as string}`,
  );

it("submits the picked values, falling back to the first item for a single value", async () => {
  // Arrange
  await renderPage();

  // Act
  await page.getByRole("combobox", { name: "Languages", exact: true }).click();
  await page.getByRole("option", { name: "Italian" }).click();

  // Assert
  expect(submitted()).toEqual(["locales[]=fr-FR", "locales[]=it-IT", "locale=de-DE"]);
});

it("makes the form dirty when picking and restores the picked values on cancel", async () => {
  // Arrange
  await renderPage();
  const languagesSelect = page.getByRole("combobox", { name: "Languages", exact: true });
  await languagesSelect.click();
  await page.getByRole("option", { name: "French" }).click();
  await page.getByRole("option", { name: "German" }).click();
  await userEvent.keyboard("{Escape}");
  await expect.element(languagesSelect).toHaveTextContent("German");

  // Act
  await page.getByRole("button", { name: "Cancel" }).click();

  // Assert
  await expect.element(languagesSelect).toHaveTextContent("French");
  expect(submitted()).toEqual(["locales[]=fr-FR", "locale=de-DE"]);
});
