import { App } from "@inertiajs/react";
import { type ComponentType, useEffect, useState } from "react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { page, userEvent } from "vitest/browser";
import { render } from "vitest-browser-react";
import { AppHotkeysProvider } from "../../utils/hotkeys";
import CommandMenu from "./CommandMenu";
import { type CommandDefinition, commands, useCommand } from "./commands";

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
  register(orders, switchTheme);
  await renderMenu();

  await openMenu();

  await expect.element(search()).toHaveFocus();
  expect(options()).toEqual(["Go to orders", "Switch theme"]);

  await search().fill("ord");

  await expect.element(option("Switch theme")).not.toBeInTheDocument();
  await expect.element(option("Go to orders")).toBeVisible();
});

it("moves the highlight with the arrow keys while the search keeps focus", async () => {
  register(orders, switchTheme);
  await renderMenu();
  await openMenu();

  await expect.element(option("Go to orders")).toHaveAttribute("aria-selected", "true");
  await userEvent.keyboard("{ArrowDown}");

  await expect.element(option("Switch theme")).toHaveAttribute("aria-selected", "true");
  await expect.element(option("Go to orders")).toHaveAttribute("aria-selected", "false");
  await expect.element(search()).toHaveFocus();
});

it("opens a nested view on the current item, and Escape clears, goes back, clears and closes", async () => {
  register(orders, switchTheme);
  await renderMenu();
  await openMenu();
  await search().fill("swi");

  await userEvent.keyboard("{Enter}");

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
  register(switchTheme);
  await renderMenu();
  await openMenu();
  await userEvent.keyboard("{Enter}");
  await expect.element(option("Light")).toBeVisible();

  await userEvent.keyboard("{Backspace}");

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
  register(orders, switchTheme);
  await renderMenu();
  await openMenu();
  await search().fill("swi");
  await userEvent.keyboard("{Enter}");
  await choose("System");

  expect(run).toHaveBeenCalledExactlyOnceWith("system");
  await expect.element(menu()).not.toBeInTheDocument();

  await openMenu();

  await expect.element(search()).toHaveValue("");
  expect(options()).toEqual(["Go to orders", "Switch theme"]);
});

it("finds nested items from the root and runs them there", async () => {
  register(orders, switchTheme);
  await renderMenu();
  await openMenu();

  await search().fill("dark");

  await expect.element(option(/Switch theme.*Dark/)).toBeVisible();
  await userEvent.keyboard("{Enter}");

  expect(run).toHaveBeenCalledExactlyOnceWith("dark");
  await expect.element(menu()).not.toBeInTheDocument();
});

it("shows why a command is disabled, and neither opens it nor finds its items", async () => {
  register(orders, { ...switchTheme, disabled: "Not right now" });
  await renderMenu();
  await openMenu();

  await expect.element(option(/Switch theme/)).toHaveAttribute("aria-disabled", "true");
  expect(option(/Switch theme/).element().textContent).toContain("Not right now");

  await search().fill("dark");
  await expect.element(page.getByText("No results")).toBeVisible();
});

describe("registering", () => {
  it("throws for a command id that is already registered", () => {
    register(orders);

    expect(() => commands.register({ ...orders, label: "Orders again" })).toThrow(/orders/);
  });

  it("offers a useCommand command only while its component is mounted, as it was last rendered", async () => {
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
    await page.getByRole("button", { name: "More" }).click();
    await openMenu();
    await choose("Count 1");
    expect(run).toHaveBeenCalledExactlyOnceWith(1);

    await page.getByRole("button", { name: "Hide" }).click();
    await openMenu();

    await expect.element(page.getByText("No results")).toBeVisible();
  });

  it("goes back to the root once the command of the open nested view goes away", async () => {
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

    hide();

    await expect.element(option("Go to orders")).toBeVisible();
    await expect.element(option("Light")).not.toBeInTheDocument();
  });
});
