import { App, http, usePage } from "@inertiajs/react";
import { ToastProvider } from "@larasell-dev/ui";
import { afterEach, expect, it, vi } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import { UnsavedChangesToastHost } from "../../Components/UnsavedChangesToast";
import Edit from "./Edit";

type Props = Larasell.Admin.Data.View.EditProductPageData;
type ContentLocale = Larasell.Admin.Data.ViewModel.ContentLocale.ContentLocaleData;

const english = { value: "en", label: "English" };
const german = { value: "de", label: "German" };

function props(
  name: string | null,
  contentLocale: Partial<ContentLocale> = {},
  fallbackName = "Boots",
): Props {
  return {
    product: {
      id: 1,
      name,
      slug: name === null ? null : name.toLowerCase(),
      fallbackName,
      fallbackSlug: fallbackName.toLowerCase(),
      visible: true,
      weight: null,
      dimensions: null,
      sku: null,
      barcode: null,
      stock: null,
      allowBackorders: false,
      minQuantity: null,
      maxQuantity: null,
      categoryIds: [],
      images: [],
    },
    updateUrl: "/admin/products/1",
    indexUrl: "/admin/products",
    categories: [],
    createCategoryUrl: null,
    uploadImagesUrl: "/admin/products/images",
    contentLocale: {
      current: "en",
      options: [english, german],
      switchUrl: "/admin/content-locale",
      ...contentLocale,
    },
  };
}

// Without the sidebar layout, which needs the whole panel's props; the toast's host stands in.
// It takes the props from the page, since Inertia keeps resolving the first test's component.
function EditPage() {
  return (
    <ToastProvider>
      <Edit {...usePage<Props>().props} />
      <UnsavedChangesToastHost />
    </ToastProvider>
  );
}

function renderEdit(initial: Props) {
  const initialPage = { component: "Product/Edit", url: "/admin/products/1/edit", version: null };

  return render(
    <App
      initialPage={{ ...initialPage, props: { errors: {}, ...initial } } as never}
      initialComponent={EditPage}
      resolveComponent={() => EditPage}
    />,
  );
}

/**
 * Answers every request with the page in `next`, as the server does when the locale switch
 * redirects back, and records each request.
 */
function fakeServer(next: Props) {
  const requests: { method: string; url: string; data: unknown }[] = [];
  vi.spyOn(http, "getClient").mockReturnValue({
    async request({ method, url, data }) {
      requests.push({ method, url, data });
      const page = {
        component: "Product/Edit",
        url: "/admin/products/1/edit",
        version: null,
        props: { errors: {}, ...next },
      };
      return { status: 200, data: JSON.stringify(page), headers: { "x-inertia": "true" } };
    },
  });
  return requests;
}

afterEach(() => vi.restoreAllMocks());

const select = () => page.getByRole("combobox", { name: "Content language" });
const nameField = () => page.getByRole("textbox", { name: "Name" });

it("switches the product's content to the picked language", async () => {
  const requests = fakeServer(props("Stiefel", { current: "de" }));
  await renderEdit(props("Boots"));

  await expect.element(select()).toHaveDisplayValue("English");
  await expect.element(nameField()).toHaveValue("Boots");

  await select().selectOptions("German");

  await expect.element(nameField()).toHaveValue("Stiefel");
  await expect.element(select()).toHaveDisplayValue("German");
  expect(requests).toEqual([
    {
      method: "put",
      url: expect.stringContaining("/admin/content-locale"),
      data: expect.anything(),
    },
  ]);
  expect(JSON.stringify(requests[0]?.data)).toContain('"locale":"de"');
});

it("leaves out the select while the store has only one language", async () => {
  await renderEdit(props("Boots", { options: [english] }));

  await expect.element(nameField()).toHaveValue("Boots");
  await expect.element(select()).not.toBeInTheDocument();
});

it("doesn't switch the language while the product has unsaved changes", async () => {
  await renderEdit(props("Boots"));

  await nameField().fill("Rain Boots");

  await expect.element(select()).toBeDisabled();

  // Without the layout the page is taller than the viewport, which leaves the toast out of view;
  // putting the name back undoes the change as well.
  await nameField().fill("Boots");

  await expect.element(select()).toBeEnabled();
});

it("leaves an untranslated name empty and shows what the store shows instead", async () => {
  await renderEdit(props(null, { current: "de" }));

  await expect.element(nameField()).toHaveValue("");
  await expect.element(nameField()).toHaveAttribute("placeholder", "Boots");
  await expect
    .element(page.getByRole("textbox", { name: "Slug" }))
    .toHaveAttribute("placeholder", "boots");
  await expect.element(page.getByRole("heading", { name: "Boots" })).toBeVisible();
});
