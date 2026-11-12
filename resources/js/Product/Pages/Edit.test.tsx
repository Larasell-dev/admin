import { App, http, usePage } from "@inertiajs/react";
import { ToastProvider } from "@larasell-dev/ui";
import { afterEach, expect, it, vi } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import { UnsavedChangesToastHost } from "../../Platform/Panel/Components/UnsavedChangesToast";
import { extensions, Larasell, SectionForm, type SectionComponentProps } from "../../extensions";
import Edit from "./Edit";

type Props = Parameters<typeof Edit>[0];
type ContentLocale = Larasell.Admin.Platform.ContentLocale.Data.ContentLocaleData;

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
      fallbackName,
      description: null,
      visible: true,
      weight: null,
      dimensions: null,
      sku: null,
      mpn: null,
      barcode: null,
      countryOfOrigin: null,
      hsCode: null,
      stock: null,
      allowBackorders: false,
      minQuantity: null,
      maxQuantity: null,
      categoryIds: [],
    },
    updateUrl: "/admin/products/1",
    indexUrl: "/admin/products",
    categories: [],
    createCategoryUrl: null,
    sections: [],
    variants: {
      options: [],
      availableOptions: [],
      variants: [],
      page: 1,
      pageSize: 10,
      total: 0,
      missingCombinations: [],
      createCombinationsUrl: "",
      optionDefinitionsUrl: "",
    },
    type: {
      key: "physical",
      label: "Physical product",
      capabilities: ["stock", "shipping", "dimensions"],
    },
    attributes: [],
    countries: [],
    attributesUrl: "",
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
  const initialPage = {
    component: "Product/Pages/Edit",
    url: "/admin/products/1/edit",
    version: null,
  };

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
function fakeServer(next: Props, errors: Record<string, string> = {}) {
  const requests: { method: string; url: string; data: unknown }[] = [];
  vi.spyOn(http, "getClient").mockReturnValue({
    async request({ method, url, data }) {
      requests.push({ method, url, data });
      const page = {
        component: "Product/Pages/Edit",
        url: "/admin/products/1/edit",
        version: null,
        props: { errors, ...next },
      };
      return { status: 200, data: JSON.stringify(page), headers: { "x-inertia": "true" } };
    },
  });
  return requests;
}

afterEach(() => {
  vi.restoreAllMocks();
  vi.unstubAllEnvs();
});

const select = () => page.getByRole("combobox", { name: "Content language" });
const nameField = () => page.getByRole("textbox", { name: "Name" });

it("switches the product's content to the picked language", async () => {
  // Arrange
  const requests = fakeServer(props("Stiefel", { current: "de" }));
  await renderEdit(props("Boots"));

  await expect.element(select()).toHaveTextContent("English");
  await expect.element(nameField()).toHaveValue("Boots");

  // Act
  await select().click();
  await page.getByRole("option", { name: "German" }).click();

  // Assert
  await expect.element(nameField()).toHaveValue("Stiefel");
  await expect.element(select()).toHaveTextContent("German");
  // Moving the pointer to the select can prefetch the back link on hover, which isn't a switch.
  const switches = requests.filter((request) => request.method !== "get");
  expect(switches).toEqual([
    {
      method: "put",
      url: expect.stringContaining("/admin/content-locale"),
      data: expect.anything(),
    },
  ]);
  expect(JSON.stringify(switches[0]?.data)).toContain('"locale":"de"');
});

it("leaves out the select while the store has only one language", async () => {
  // Act
  await renderEdit(props("Boots", { options: [english] }));

  // Assert
  await expect.element(nameField()).toHaveValue("Boots");
  await expect.element(select()).not.toBeInTheDocument();
});

it("doesn't switch the language while the product has unsaved changes", async () => {
  // Arrange
  await renderEdit(props("Boots"));

  // Act
  await nameField().fill("Rain Boots");

  // Assert
  await expect.element(select()).toBeDisabled();

  // Without the layout the page is taller than the viewport, which leaves the toast out of view;
  // putting the name back undoes the change as well.
  await nameField().fill("Boots");

  await expect.element(select()).toBeEnabled();
});

it("leaves an untranslated name empty and shows what the store shows instead", async () => {
  // Act
  await renderEdit(props(null, { current: "de" }));

  // Assert
  await expect.element(nameField()).toHaveValue("");
  await expect.element(nameField()).toHaveAttribute("placeholder", "Boots");
  await expect.element(page.getByRole("heading", { name: "Boots" })).toBeVisible();
});

// A plugin's section, as its admin script would register it.
function NotesSection({ props, name, error }: SectionComponentProps<{ note: string }>) {
  return (
    <section>
      <label>
        Internal note
        <input name={name("note")} defaultValue={props.note} />
      </label>
      <p>{error("note")}</p>
    </section>
  );
}

it("renders the sections extensions add inside the form, so they count as unsaved changes", async () => {
  // Arrange
  Larasell.booting(({ sections }) => {
    sections.register("product", "notes", async () => ({ default: NotesSection }));
  });
  extensions.boot();
  const sections = [
    { form: "product", key: "notes", props: { note: "Ships in March" }, before: null, after: null },
  ];
  // As the panel does before it renders a page.
  await extensions.loadSections(sections);

  const requests = fakeServer(
    { ...props("Boots"), sections },
    { "sections.notes.note": "Too long." },
  );
  await renderEdit({ ...props("Boots"), sections });

  const note = page.getByRole("textbox", { name: "Internal note" });
  await expect.element(note).toHaveValue("Ships in March");

  // Act & Assert
  await note.fill("Ships in April");
  await expect.element(select()).toBeDisabled();

  await note.fill("Ships in March");
  await expect.element(select()).toBeEnabled();

  // Saved with the product, under the section's key; the error comes back on its field.
  await note.fill("Ships in April, maybe May");
  document.querySelector("form")?.requestSubmit();

  await expect.element(page.getByText("Too long.")).toBeVisible();
  expect(requests.at(-1)).toMatchObject({
    method: "put",
    data: { name: "Boots", sections: { notes: { note: "Ships in April, maybe May" } } },
  });
});

function TitleSection({ props }: SectionComponentProps<{ title: string }>) {
  return <h3>{props.title}</h3>;
}

it("places sections before or after the product's own sections, or after all of them", async () => {
  // Arrange
  Larasell.booting(({ sections }) => {
    for (const key of ["first", "middle", "last"]) {
      sections.register(SectionForm.Product, key, async () => ({ default: TitleSection }));
    }
  });
  extensions.boot();
  const section = (key: string, title: string, before: string | null, after: string | null) => ({
    form: "product",
    key,
    props: { title },
    before,
    after,
  });
  // In the server's order, which sorted them by position and key.
  const sections = [
    section("last", "Last", null, null),
    section("middle", "After stock", null, "stock"),
    section("first", "Before general", "general", null),
  ];
  await extensions.loadSections(sections);

  // Act
  await renderEdit({ ...props("Boots"), sections });

  // Assert
  await expect.element(page.getByRole("heading", { name: "Last" })).toBeVisible();
  expect([...document.querySelectorAll("h3")].map((heading) => heading.textContent)).toEqual([
    "Before general",
    "General",
    "Categories",
    "Stock",
    "After stock",
    "Weight and dimensions",
    "Identifiers",
    "Customs",
    "Last",
    "Options",
  ]);
});

it("leaves out a section that can't show in production, and says why, while the form keeps working", async () => {
  // Arrange
  // In development, the same sections throw while rendering.
  vi.stubEnv("DEV", false);
  const logged = vi.spyOn(console, "error").mockImplementation(() => {});
  const section = (key: string, after: string | null) => ({
    form: "product",
    key,
    props: {},
    before: null,
    after,
  });

  // Act
  await renderEdit({
    ...props("Boots"),
    sections: [section("unregistered", null), section("misplaced", "pricing")],
  });

  // Assert
  await expect.element(page.getByRole("heading", { name: "Identifiers" })).toBeVisible();
  expect(logged).toHaveBeenCalledWith(
    expect.stringContaining(
      "No extension registered a component for the section [product/unregistered]",
    ),
  );
  expect(logged).toHaveBeenCalledWith(
    expect.stringContaining("The section [product/misplaced] is placed at [pricing]"),
  );
});
