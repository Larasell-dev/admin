import { App, http, router } from "@inertiajs/react";
import { type ComponentType, useEffect, useState } from "react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { page, userEvent } from "vitest/browser";
import { render } from "vitest-browser-react";
import { AppHotkeysProvider } from "../../../Support/hotkeys";
import { setTheme, storedTheme, Theme } from "../../../Support/theme";
import CommandMenu from "./CommandMenu";
import { type CommandDefinition, commands, useCommand } from "./commands";
import { contentLocaleCommand } from "./contentLocaleCommand";
import { interfaceLocaleCommand } from "./interfaceLocaleCommand";
import { Highlight, matchRanges } from "./Highlight";
import { goToCommand } from "./goToCommand";
import { SearchCommands } from "./searchCommands";
import { themeCommand } from "./themeCommand";

const cleanups: Array<() => void> = [];

afterEach(() => {
  cleanups.splice(0).forEach((cleanup) => cleanup());
  vi.restoreAllMocks();
});

function register(...definitions: CommandDefinition[]) {
  cleanups.push(commands.register(...definitions));
}

/** Renders the menu next to the page component, on an Inertia page with the props. */
function renderMenu(Page: ComponentType = () => null, props: Record<string, unknown> = {}) {
  function Root() {
    return (
      <>
        <Page />
        <CommandMenu />
      </>
    );
  }

  const initialPage = {
    component: "Test",
    url: "/admin",
    version: null,
    props: { errors: {}, ...props },
  };

  return render(
    <AppHotkeysProvider>
      <App
        initialPage={initialPage as never}
        initialComponent={Root}
        resolveComponent={() => Root}
      />
    </AppHotkeysProvider>,
  );
}

const openMenu = () => userEvent.keyboard("{Control>}k{/Control}");
const menu = () => page.getByRole("dialog", { name: "Command menu" });
const search = () => page.getByRole("combobox");
const option = (name: string | RegExp) => page.getByRole("option", { name });
const options = () =>
  page
    .getByRole("option")
    .elements()
    .map((element) => element.textContent);

const run = vi.fn<(value: unknown) => void>();
const orders = { id: "orders", label: "Go to orders", run: () => run("orders") };
const switchTheme = {
  id: "fake-theme",
  label: "Switch theme",
  items: [
    { id: "light", label: "Light", run: () => run("light") },
    { id: "dark", label: "Dark", checked: true, run: () => run("dark") },
    { id: "system", label: "System", run: () => run("system") },
  ],
};

afterEach(() => run.mockReset());

/** Moves the highlight to the option, up or down, and picks it, as the menu is meant to be used. */
async function choose(name: string | RegExp) {
  const all = () => page.getByRole("option").elements();
  const target = option(name).element();

  for (let step = 0; target.getAttribute("aria-selected") !== "true"; step++) {
    if (step > 10) throw new Error(`Couldn't highlight the option ${String(name)}.`);
    const highlighted = all().findIndex(
      (element) => element.getAttribute("aria-selected") === "true",
    );
    await userEvent.keyboard(all().indexOf(target) > highlighted ? "{ArrowDown}" : "{ArrowUp}");
  }
  await userEvent.keyboard("{Enter}");
}

it("opens with Mod+K and filters the commands as you type", async () => {
  // Arrange
  register(orders, switchTheme);
  await renderMenu();

  // Act
  await openMenu();

  // Assert
  await expect.element(search()).toHaveFocus();
  expect(options()).toEqual(["Go to orders", "Switch theme"]);

  await search().fill("ord");

  await expect.element(option("Switch theme")).not.toBeInTheDocument();
  await expect.element(option("Go to orders")).toBeVisible();
});

it("moves the highlight with the arrow keys while the search keeps focus", async () => {
  // Arrange
  register(orders, switchTheme);
  await renderMenu();
  await openMenu();

  await expect.element(option("Go to orders")).toHaveAttribute("aria-selected", "true");

  // Act
  await userEvent.keyboard("{ArrowDown}");

  // Assert
  await expect.element(option("Switch theme")).toHaveAttribute("aria-selected", "true");
  await expect.element(option("Go to orders")).toHaveAttribute("aria-selected", "false");
  await expect.element(search()).toHaveFocus();
});

it("opens a nested view on the current item, and Escape clears, goes back, clears and closes", async () => {
  // Arrange
  register(orders, switchTheme);
  await renderMenu();
  await openMenu();
  await search().fill("swi");

  // Act
  await userEvent.keyboard("{Enter}");

  // Assert
  // The nested view starts empty, on the checked item.
  await expect.element(search()).toHaveValue("");
  expect(options()).toEqual(["Light", "Dark, current", "System"]);
  await expect.element(option(/Dark/)).toHaveAttribute("aria-selected", "true");

  await search().fill("li");
  await userEvent.keyboard("{Escape}");
  await expect.element(search()).toHaveValue("");
  await expect.element(option("System")).toBeVisible();

  await userEvent.keyboard("{Escape}");
  // Back at the root, with its search as it was.
  await expect.element(search()).toHaveValue("swi");
  await expect.element(option("Switch theme")).toHaveAttribute("aria-selected", "true");

  await userEvent.keyboard("{Escape}");
  await expect.element(search()).toHaveValue("");
  await expect.element(menu()).toBeVisible();

  await userEvent.keyboard("{Escape}");
  await expect.element(menu()).not.toBeInTheDocument();
});

it("goes back with Backspace in an empty nested search, or with the back button", async () => {
  // Arrange
  register(switchTheme);
  await renderMenu();
  await openMenu();
  await userEvent.keyboard("{Enter}");
  await expect.element(option("Light")).toBeVisible();

  // Act
  await userEvent.keyboard("{Backspace}");

  // Assert
  await expect.element(option("Switch theme")).toBeVisible();
  await expect.element(option("Light")).not.toBeInTheDocument();
  await expect.element(page.getByRole("button", { name: /^Back/ })).not.toBeInTheDocument();

  await userEvent.keyboard("{Enter}");
  // Clicks don't reach the menu in tests, so the button is pressed from the keyboard.
  await userEvent.keyboard("{Shift>}{Tab}{/Shift}");
  await expect.element(page.getByRole("button", { name: "Back from Switch theme" })).toHaveFocus();
  await userEvent.keyboard("{Enter}");

  await expect.element(option("Switch theme")).toBeVisible();
  await expect.element(option("Light")).not.toBeInTheDocument();
  // The search has focus again, so the arrow keys keep working.
  await expect.element(search()).toHaveFocus();
});

it("runs an item and closes, and opens again empty at the root", async () => {
  // Arrange & Act
  register(orders, switchTheme);
  await renderMenu();
  await openMenu();
  await search().fill("swi");
  await userEvent.keyboard("{Enter}");
  await choose("System");

  // Assert
  expect(run).toHaveBeenCalledExactlyOnceWith("system");
  await expect.element(menu()).not.toBeInTheDocument();

  await openMenu();

  await expect.element(search()).toHaveValue("");
  expect(options()).toEqual(["Go to orders", "Switch theme"]);
});

it("finds nested items from the root and runs them there", async () => {
  // Arrange
  register(orders, switchTheme);
  await renderMenu();
  await openMenu();

  // Act
  await search().fill("dark");

  // Assert
  await expect.element(option(/Switch theme.*Dark/)).toBeVisible();
  await userEvent.keyboard("{Enter}");

  expect(run).toHaveBeenCalledExactlyOnceWith("dark");
  await expect.element(menu()).not.toBeInTheDocument();
});

it("marks what the search matched in the crumbs and the label", async () => {
  // Arrange
  register(orders, switchTheme);
  await renderMenu();
  await openMenu();

  const marked = (name: RegExp) =>
    [...option(name).element().querySelectorAll("mark")].map((mark) => mark.textContent);

  // Act & Assert
  await search().fill("themedark");
  await expect.element(option(/Switch theme.*Dark/)).toBeVisible();
  expect(marked(/Switch theme.*Dark/)).toEqual(["theme", "Dark"]);

  await search().fill("ord");
  await expect.element(option("Go to orders")).toBeVisible();
  expect(marked(/Go to orders/)).toEqual(["ord"]);
});

it("finds at most 20 of a command's nested items from the root, the best matches", async () => {
  // Arrange
  register({
    id: "languages",
    label: "Change language",
    items: Array.from({ length: 30 }, (_, index) => ({
      id: `language-${index + 1}`,
      label: `Language ${index + 1}`,
      run: () => run(index + 1),
    })),
  });
  await renderMenu();
  await openMenu();

  // Act & Assert
  await search().fill("language");
  await expect
    .element(page.getByRole("option", { name: "Change language", exact: true }))
    .toBeVisible();
  expect(options()).toHaveLength(1 + 20);

  await search().fill("language 30");
  await expect.element(option(/Language 30$/)).toBeVisible();
});

it("leaves the items of a command searched only in its own view out of the root search", async () => {
  // Arrange
  register(switchTheme, {
    id: "products",
    label: "Search products",
    searchItemsFromRoot: false,
    items: [{ id: "boots", label: "Boots", run: () => run("boots") }],
  });
  await renderMenu();
  await openMenu();

  // Act & Assert
  await search().fill("s");
  expect(options()).not.toContain("Search productsBoots");
  await expect.element(option("Search products")).toBeVisible();

  await search().fill("boots");
  await expect.element(page.getByText("No results")).toBeVisible();

  await search().fill("");
  await choose("Search products");
  await search().fill("boots");
  await expect.element(option("Boots")).toBeVisible();
});

it("shows why a command is disabled, and neither opens it nor finds its items", async () => {
  // Arrange & Act
  register(orders, { ...switchTheme, disabled: "Not right now" });
  await renderMenu();
  await openMenu();

  // Assert
  await expect.element(option(/Switch theme/)).toHaveAttribute("aria-disabled", "true");
  expect(option(/Switch theme/).element().textContent).toContain("Not right now");

  await search().fill("dark");
  await expect.element(page.getByText("No results")).toBeVisible();
});

describe("rendered labels", () => {
  const boots = {
    id: "boots",
    label: ({ search, selected }: { search: string; selected: boolean }) => (
      <>
        <span>Boots</span>
        <span>{`${selected ? "highlighted" : "not highlighted"} for "${search}"`}</span>
      </>
    ),
    textValue: "Boots, SKU BT-100",
    run: () => run("boots"),
  };

  it("renders the label with the search and whether it is highlighted, and finds and names it by its text value", async () => {
    // Arrange
    register(orders, boots);
    await renderMenu();
    await openMenu();

    await expect.element(page.getByText('not highlighted for ""')).toBeVisible();
    await expect.element(option(/^Boots, SKU BT-100$/)).toBeVisible();

    // Act
    await search().fill("BT-100");

    // Assert
    await expect.element(page.getByText('highlighted for "BT-100"')).toBeVisible();
    expect(options()).toHaveLength(1);
    await userEvent.keyboard("{Enter}");

    expect(run).toHaveBeenCalledExactlyOnceWith("boots");
  });

  it("names a nested command with a rendered label by its text value in crumbs and the back button", async () => {
    // Arrange
    register({
      id: "density",
      label: () => <strong>Density</strong>,
      textValue: "Change density",
      items: [{ id: "compact", label: "Compact", run: () => run("compact") }],
    });
    await renderMenu();
    await openMenu();

    // Act & Assert
    await search().fill("compact");
    await expect.element(option(/Change density.*Compact/)).toBeVisible();

    await search().fill("");
    await userEvent.keyboard("{Enter}");

    await expect
      .element(page.getByRole("button", { name: "Back from Change density" }))
      .toBeInTheDocument();
  });

  it("needs a text value for a rendered label", () => {
    // Act
    // @ts-expect-error Search, crumbs and screen readers need the text of a rendered label.
    const definition: CommandDefinition = { id: "x", label: () => null, run: () => {} };

    // Assert
    expect(definition.id).toBe("x");
  });

  it("finds the ranges a search matched the way the menu finds items, gaps and case included", () => {
    // Act & Assert
    expect(matchRanges("Oxford Shirt", "oxfordshirt")).toEqual([
      [0, 6],
      [7, 12],
    ]);
    expect(matchRanges("Rain Boots", "boot")).toEqual([[5, 9]]);
    // Letters next to each other win over the first letters found, like a substring search.
    expect(matchRanges("Big orange boots", "boot")).toEqual([[11, 15]]);
    expect(matchRanges("Boots", "tb")).toEqual([]);
    expect(matchRanges("Boots", "")).toEqual([]);
  });

  it("marks the ranges of a text, in any order", async () => {
    // Act
    const screen = await render(
      <Highlight
        text="BT-100-RED"
        ranges={[
          [7, 10],
          [0, 2],
        ]}
      />,
    );

    // Assert
    expect([...screen.container.querySelectorAll("mark")].map((mark) => mark.textContent)).toEqual([
      "BT",
      "RED",
    ]);
    expect(screen.container.textContent).toBe("BT-100-RED");
  });
});

describe("registering", () => {
  it("throws for a command id that is already registered", () => {
    // Arrange
    register(orders);

    // Act & Assert
    expect(() => commands.register({ ...orders, label: "Orders again" })).toThrow(/orders/);
  });

  it("offers a useCommand command only while its component is mounted, as it was last rendered", async () => {
    // Arrange
    function Page() {
      const [count, setCount] = useState(0);
      const [shown, setShown] = useState(true);

      return (
        <>
          <button type="button" onClick={() => setCount(count + 1)}>
            More
          </button>
          <button type="button" onClick={() => setShown(false)}>
            Hide
          </button>
          {shown && <Counter count={count} />}
        </>
      );
    }

    function Counter({ count }: { count: number }) {
      useCommand({ id: "count", label: `Count ${count}`, run: () => run(count) });
      return null;
    }

    await renderMenu(Page);

    // Act & Assert
    await page.getByRole("button", { name: "More" }).click();
    await openMenu();
    await choose("Count 1");
    expect(run).toHaveBeenCalledExactlyOnceWith(1);

    await page.getByRole("button", { name: "Hide" }).click();
    await openMenu();

    await expect.element(page.getByText("No results")).toBeVisible();
  });

  it("goes back to the root once the command of the open nested view goes away", async () => {
    // Arrange
    let hide!: () => void;

    function Page() {
      const [shown, setShown] = useState(true);
      useEffect(() => {
        hide = () => setShown(false);
      }, []);
      return shown && <Nested />;
    }

    function Nested() {
      useCommand(switchTheme);
      return null;
    }

    register(orders);
    await renderMenu(Page);
    await openMenu();
    await choose("Switch theme");
    await expect.element(option("Light")).toBeVisible();

    // Act
    hide();

    // Assert
    await expect.element(option("Go to orders")).toBeVisible();
    await expect.element(option("Light")).not.toBeInTheDocument();
  });
});

describe("theme", () => {
  afterEach(() => setTheme(Theme.System));

  it("switches the theme from a nested list that marks the current one", async () => {
    // Arrange
    register(themeCommand);
    await renderMenu();
    await openMenu();
    await choose("Switch theme");

    expect(options()).toEqual(["Light", "Dark", "System, current"]);

    // Act
    await choose("Dark");

    // Assert
    expect(storedTheme()).toBe(Theme.Dark);
    expect(document.documentElement.classList.contains("dark")).toBe(true);
    await expect.element(menu()).not.toBeInTheDocument();
  });
});

describe("views that search on their own", () => {
  it("gives a command's hook the search of its open view, and null while it isn't open", async () => {
    // Arrange
    const searches: Array<string | null> = [];
    register({
      id: "lookup",
      use: ({ search }) => {
        searches.push(search);
        return { label: "Look up", items: [{ id: "a", label: "Anything", run: () => {} }] };
      },
    });
    await renderMenu();
    await openMenu();
    await search().fill("look");
    expect(searches.at(-1)).toBeNull();

    // Act
    await userEvent.keyboard("{Enter}");
    await search().fill("abc");

    // Assert
    await expect.poll(() => searches.at(-1)).toBe("abc");
  });

  it("shows the items of a view that filters them itself as they are, in their order", async () => {
    // Arrange
    register({
      id: "lookup",
      label: "Look up",
      filterItems: false,
      items: [
        { id: "z", label: "Zebra", run: () => {} },
        { id: "a", label: "Aardvark", run: () => {} },
      ],
    });
    await renderMenu();
    await openMenu();
    await choose("Look up");

    // Act
    await search().fill("xyz");

    // Assert
    expect(options()).toEqual(["Zebra", "Aardvark"]);
  });

  it("says it's searching while a view's items load, and nothing before a search", async () => {
    // Arrange
    let loading = true;
    register({
      id: "lookup",
      use: () => ({ label: "Look up", filterItems: false, loading, items: [] }),
    });
    await renderMenu();
    await openMenu();
    await choose("Look up");

    // Act & Assert
    // Nothing below the search until there is one.
    await expect.element(page.getByRole("listbox")).not.toBeInTheDocument();
    await search().fill("a");
    await expect.element(page.getByRole("listbox")).toBeInTheDocument();
    await expect.element(page.getByText("Searching…")).toBeVisible();

    loading = false;
    await search().fill("ab");

    await expect.element(page.getByText("No results")).toBeVisible();
    await expect.element(page.getByText("Searching…")).not.toBeInTheDocument();
  });
});

describe("searching on the server", () => {
  const search_ = (id: string, label: string, icon: string) => ({
    id,
    label,
    icon,
    placeholder: `Search ${id}`,
    url: `/admin/search/${id}`,
  });
  const searches = [
    search_("products", "Search products", "ShoppingBag"),
    search_("categories", "Search categories", "Folders"),
    search_("gift-cards", "Search gift cards", "Gift"),
  ];

  function respondWith(results: Larasell.Admin.Platform.Search.Data.SearchResultData[]) {
    return vi.spyOn(window, "fetch").mockImplementation(async () => Response.json({ results }));
  }

  it("searches products once typing pauses, says what matched, and opens the one picked", async () => {
    // Arrange
    const fetch = respondWith([
      {
        id: 1,
        label: "Chelsea Boots",
        url: "/admin/products/1/edit",
        match: { label: "SKU", value: "BT-100" },
      },
      { id: 2, label: "Boots", url: "/admin/products/2/edit", match: null },
    ]);
    const visit = vi.spyOn(router, "visit").mockImplementation(() => {});
    await renderMenu(SearchCommands, { commandMenu: { searches } });
    await openMenu();

    // Act & Assert
    // Searching the root doesn't search the server.
    await search().fill("products");
    await expect.element(option("Search products")).toBeVisible();
    await search().fill("");
    await choose("Search products");
    await userEvent.keyboard("bt-1");

    await expect.element(option("Chelsea Boots, SKU: BT-100")).toBeVisible();
    expect(fetch.mock.calls.map(([url]) => url)).toEqual(["/admin/search/products?q=bt-1"]);
    // In the server's order.
    await expect
      .element(page.getByRole("option").nth(0))
      .toHaveAccessibleName("Chelsea Boots, SKU: BT-100");
    await expect.element(page.getByRole("option").nth(1)).toHaveAccessibleName("Boots");

    await userEvent.keyboard("{Enter}");

    expect(visit).toHaveBeenCalledWith("/admin/products/1/edit");
    await expect.element(menu()).not.toBeInTheDocument();
  });

  it("offers a command for each search the server shares, at its own url", async () => {
    // Arrange
    const fetch = respondWith([]);
    await renderMenu(SearchCommands, { commandMenu: { searches } });
    await openMenu();

    expect(options()).toEqual(["Search products", "Search categories", "Search gift cards"]);

    // Act & Assert
    await choose("Search gift cards");
    await expect.element(search()).toHaveAttribute("placeholder", "Search gift-cards");
    await search().fill("ada");

    await expect.poll(() => fetch.mock.calls.at(-1)?.[0]).toBe("/admin/search/gift-cards?q=ada");
  });

  it("offers no searches to guests", async () => {
    // Arrange & Act
    await renderMenu(SearchCommands, { commandMenu: null });
    await openMenu();

    // Assert
    await expect.element(page.getByText("No results")).toBeVisible();
  });
});

describe("navigating", () => {
  const navigationItem = (
    label: string,
    url: string,
    children: Larasell.Admin.Platform.Navigation.Data.NavigationItemData[] = [],
  ) => ({ id: `larasell.admin.${label}`, label, url, icon: "Home", children });
  const props = {
    navigation: [
      { ...navigationItem("Home", "/admin"), id: "larasell.admin.home" },
      navigationItem("Products", "/admin/products"),
      navigationItem("Customers", "/admin/customers", [
        navigationItem("Customer groups", "/admin/customer-groups"),
      ]),
      navigationItem("Settings", "/admin/settings"),
    ],
    settingsPages: [
      { id: "team", label: "Team", description: null, icon: "Team", url: "/admin/settings/team" },
      {
        id: "audit-log",
        label: "Audit log",
        description: null,
        icon: "FileShield",
        url: "/admin/settings/audit-log",
      },
    ],
    profileUrl: "/admin/profile",
    logoutUrl: "/admin/logout",
  };

  it("goes to the pages of the navigation and each settings page", async () => {
    // Arrange
    const visit = vi.spyOn(router, "visit").mockImplementation(() => {});
    register(goToCommand);
    await renderMenu(undefined, props);
    await openMenu();
    await choose("Go to");

    expect(options()).toEqual([
      "Home",
      "Products",
      "Customers",
      "Customer groups",
      "Settings",
      "Settings › Team",
      "Settings › Audit log",
      "Profile",
    ]);

    // Act
    await choose("Settings › Audit log");

    // Assert
    expect(visit).toHaveBeenCalledWith("/admin/settings/audit-log");
    await expect.element(menu()).not.toBeInTheDocument();
  });

  it("finds the pages from the root, and the home page as the dashboard", async () => {
    // Arrange
    register(goToCommand);
    await renderMenu(undefined, props);
    await openMenu();

    // Act & Assert
    await search().fill("audit");
    await expect.element(option(/Go to.*Audit log/)).toBeVisible();

    await search().fill("settings team");
    await expect.element(option(/Go to.*Team/)).toBeVisible();

    await search().fill("dashboard");
    await expect.element(option(/Go to.*Home/)).toBeVisible();
  });

  it("goes to the profile", async () => {
    // Arrange
    const visit = vi.spyOn(router, "visit").mockImplementation(() => {});
    register(goToCommand);
    await renderMenu(undefined, props);
    await openMenu();
    await choose("Go to");

    // Act
    await choose("Profile");

    // Assert
    expect(visit).toHaveBeenCalledWith("/admin/profile");
  });
});

describe("content language", () => {
  const contentLocale = {
    current: "en",
    options: [
      { value: "en", label: "English" },
      { value: "de", label: "German" },
    ],
    switchUrl: "/admin/content-locale",
  };

  it("switches the content language of the page", async () => {
    // Arrange
    const requests: { method: string; url: string; data: unknown }[] = [];
    vi.spyOn(http, "getClient").mockReturnValue({
      async request({ method, url, data }) {
        requests.push({ method, url, data });
        const next = { component: "Test", url: "/admin", version: null, props: { errors: {} } };
        return { status: 200, data: JSON.stringify(next), headers: { "x-inertia": "true" } };
      },
    });
    register(contentLocaleCommand);
    await renderMenu(() => null, { contentLocale });
    await openMenu();
    await choose("Change content language");

    expect(options()).toEqual(["English, current", "German"]);

    // Act
    await choose("German");

    // Assert
    await expect.poll(() => requests).toHaveLength(1);
    expect(requests[0]).toMatchObject({
      method: "put",
      url: expect.stringContaining("/admin/content-locale"),
    });
    expect(JSON.stringify(requests[0]?.data)).toContain('"locale":"de"');
    await expect.element(menu()).not.toBeInTheDocument();
  });

  it("is left out on pages without a content language, or with only one", async () => {
    // Arrange & Act
    register(orders, contentLocaleCommand);
    await renderMenu(() => null, {
      contentLocale: { ...contentLocale, options: [contentLocale.options[0]] },
    });
    await openMenu();

    // Assert
    expect(options()).toEqual(["Go to orders"]);
  });
});

describe("interface language", () => {
  const interfaceLocale = {
    current: "en-US",
    options: [
      { value: "en-US", label: "English" },
      { value: "de-DE", label: "Deutsch" },
    ],
    switchUrl: "/admin/interface-locale",
  };

  it("switches the interface language on the server", async () => {
    // Arrange
    const requests: { method: string; url: string; data: unknown }[] = [];
    vi.spyOn(http, "getClient").mockReturnValue({
      async request({ method, url, data }) {
        requests.push({ method, url, data });
        const next = { component: "Test", url: "/admin", version: null, props: { errors: {} } };
        return { status: 200, data: JSON.stringify(next), headers: { "x-inertia": "true" } };
      },
    });
    register(interfaceLocaleCommand);
    await renderMenu(() => null, { interfaceLocale });
    await openMenu();
    await choose("Change interface language");

    expect(options()).toEqual(["English, current", "Deutsch"]);

    // Act
    await choose("Deutsch");

    // Assert
    await expect.poll(() => requests).toHaveLength(1);
    expect(requests[0]).toMatchObject({
      method: "put",
      url: expect.stringContaining("/admin/interface-locale"),
    });
    expect(JSON.stringify(requests[0]?.data)).toContain('"locale":"de-DE"');
  });

  it("finds a language by its name in the interface language too", async () => {
    // Arrange
    register(interfaceLocaleCommand);
    await renderMenu(() => null, { interfaceLocale });
    await openMenu();

    // Act
    await search().fill("german");

    // Assert
    await expect.element(option(/Deutsch/)).toBeVisible();
  });

  it("is left out for guests", async () => {
    // Arrange & Act
    register(orders, interfaceLocaleCommand);
    await renderMenu(() => null, { interfaceLocale: null });
    await openMenu();

    // Assert
    expect(options()).toEqual(["Go to orders"]);
  });
});
