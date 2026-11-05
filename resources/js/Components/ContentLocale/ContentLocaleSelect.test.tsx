import { App, Form } from "@inertiajs/react";
import { expect, it } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import ContentLocaleSelect from "./ContentLocaleSelect";
import UnsavedChangesToast, { UnsavedChangesToastHost } from "../UnsavedChangesToast";

const contentLocale = {
  current: "en",
  options: [
    { value: "en", label: "English" },
    { value: "de", label: "German" },
  ],
  switchUrl: "/admin/content-locale",
};

// Like the category page: the select sits in the header, outside the form below it.
function CategoryLikePage() {
  return (
    <>
      <ContentLocaleSelect contentLocale={contentLocale} />
      <Form action="/admin/categories/1" method="put">
        <input aria-label="Name" name="name" defaultValue="Shoes" />
        <UnsavedChangesToast />
      </Form>
      <UnsavedChangesToastHost />
    </>
  );
}

function renderPage() {
  const initialPage = { component: "Page", url: "/admin/categories/1", version: null, props: {} };

  return render(
    <App
      initialPage={initialPage as never}
      initialComponent={CategoryLikePage}
      resolveComponent={() => CategoryLikePage}
    />,
  );
}

const select = () => page.getByRole("combobox", { name: "Content language" });
const name = () => page.getByRole("textbox", { name: "Name" });

it("doesn't switch the language while a form on the page has unsaved changes, even outside it", async () => {
  await renderPage();

  await expect.element(select()).toBeEnabled();

  await name().fill("Footwear");

  await expect.element(select()).toBeDisabled();

  await name().fill("Shoes");

  await expect.element(select()).toBeEnabled();
});
