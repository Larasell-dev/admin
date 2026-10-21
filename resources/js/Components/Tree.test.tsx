import { describe, expect, it, vi } from "vitest";
import { page, userEvent } from "vitest/browser";
import { render } from "vitest-browser-react";
import Tree, { TreeItem } from "./Tree";

type Node = { id: string; name: string; children: Node[] };

const node = (name: string, children: Node[] = []): Node => ({
  id: name.toLowerCase(),
  name,
  children,
});

// Fruits starts open, Vegetables closed, so visible order is:
// Fruits, Apple, Banana, Vegetables, Grains, Cherries
const items = [
  node("Fruits", [node("Apple"), node("Banana")]),
  node("Vegetables", [node("Carrot", [node("Baby carrot")]), node("Celery")]),
  node("Grains"),
  node("Cherries"),
];

function renderTree(props: { selectedId?: string | null; onSelect?: (id: string) => void } = {}) {
  const { selectedId, onSelect } = props;

  return render(
    <Tree
      aria-label="Food"
      items={items}
      getId={(item) => item.id}
      getChildren={(item) => item.children}
      getLabel={(item) => item.name}
      defaultExpandedIds={["fruits"]}
      selectedId={selectedId}
      onSelectedIdChange={onSelect}
      renderItem={(item) => <TreeItem>{item.name}</TreeItem>}
    />,
  );
}

const item = (name: string) => page.getByRole("treeitem", { name, exact: true });

async function focusTree() {
  await userEvent.tab();
}

describe("roles, states and properties", () => {
  it("labels the tree and nests child nodes in groups", async () => {
    await renderTree();

    const tree = page.getByRole("tree", { name: "Food" });
    await expect.element(tree).toBeInTheDocument();
    await expect.element(tree).not.toHaveAttribute("aria-multiselectable", "true");

    // The group is the treeitem's sibling, owned through aria-owns.
    const groupId = item("Fruits").element().getAttribute("aria-owns");
    expect(groupId).toBeTruthy();
    const group = page.getByRole("group").filter({ has: item("Apple") });
    await expect.element(group).toHaveAttribute("id", groupId);
    await expect.element(item("Vegetables")).not.toHaveAttribute("aria-owns");
  });

  it("sets aria-expanded on parent nodes only", async () => {
    await renderTree();

    await expect.element(item("Fruits")).toHaveAttribute("aria-expanded", "true");
    await expect.element(item("Vegetables")).toHaveAttribute("aria-expanded", "false");
    await expect.element(item("Apple")).not.toHaveAttribute("aria-expanded");
    await expect.element(item("Grains")).not.toHaveAttribute("aria-expanded");
  });

  it("does not render the children of closed nodes", async () => {
    await renderTree();

    await expect.element(item("Carrot")).not.toBeInTheDocument();
  });

  it("leaves aria-selected off when nodes are not selectable", async () => {
    await renderTree();

    await expect.element(item("Fruits")).not.toHaveAttribute("aria-selected");
  });

  it("marks exactly the selected node with aria-selected when selectable", async () => {
    await renderTree({ selectedId: "banana", onSelect: () => {} });

    await expect.element(item("Banana")).toHaveAttribute("aria-selected", "true");
    await expect.element(item("Apple")).toHaveAttribute("aria-selected", "false");
    await expect.element(item("Fruits")).toHaveAttribute("aria-selected", "false");
  });
});

describe("focus", () => {
  it("is a single tab stop that starts on the first node", async () => {
    await renderTree();

    await expect.element(item("Fruits")).toHaveAttribute("tabindex", "0");
    await expect.element(item("Apple")).toHaveAttribute("tabindex", "-1");
    await expect.element(item("Grains")).toHaveAttribute("tabindex", "-1");

    await focusTree();
    await expect.element(item("Fruits")).toHaveFocus();
  });

  it("starts on the selected node when one is selected", async () => {
    await renderTree({ selectedId: "grains", onSelect: () => {} });

    await focusTree();
    await expect.element(item("Grains")).toHaveFocus();
  });

  it("returns to the last focused node when tabbing back in", async () => {
    await renderTree();

    await focusTree();
    await userEvent.keyboard("{ArrowDown}{ArrowDown}");
    await userEvent.tab();
    await userEvent.tab({ shift: true });

    await expect.element(item("Banana")).toHaveFocus();
  });
});

describe("keyboard", () => {
  it("moves down and up through visible nodes, skipping closed ones", async () => {
    await renderTree();
    await focusTree();

    await userEvent.keyboard("{ArrowDown}{ArrowDown}{ArrowDown}");
    await expect.element(item("Vegetables")).toHaveFocus();

    await userEvent.keyboard("{ArrowDown}");
    await expect.element(item("Grains")).toHaveFocus();

    await userEvent.keyboard("{ArrowUp}{ArrowUp}");
    await expect.element(item("Banana")).toHaveFocus();
  });

  it("does not move past the first or last node", async () => {
    await renderTree();
    await focusTree();

    await userEvent.keyboard("{ArrowUp}");
    await expect.element(item("Fruits")).toHaveFocus();

    await userEvent.keyboard("{End}{ArrowDown}");
    await expect.element(item("Cherries")).toHaveFocus();
  });

  it("opens a closed node with Right Arrow without moving focus, then moves to its first child", async () => {
    await renderTree();
    await focusTree();
    await userEvent.keyboard("{ArrowDown}{ArrowDown}{ArrowDown}");

    await userEvent.keyboard("{ArrowRight}");
    await expect.element(item("Vegetables")).toHaveAttribute("aria-expanded", "true");
    await expect.element(item("Vegetables")).toHaveFocus();

    await userEvent.keyboard("{ArrowRight}");
    await expect.element(item("Carrot")).toHaveFocus();
  });

  it("does nothing on Right Arrow at an end node", async () => {
    await renderTree();
    await focusTree();
    await userEvent.keyboard("{ArrowDown}");

    await userEvent.keyboard("{ArrowRight}");
    await expect.element(item("Apple")).toHaveFocus();
  });

  it("closes an open node with Left Arrow without moving focus", async () => {
    await renderTree();
    await focusTree();

    await userEvent.keyboard("{ArrowLeft}");
    await expect.element(item("Fruits")).toHaveAttribute("aria-expanded", "false");
    await expect.element(item("Fruits")).toHaveFocus();
    await expect.element(item("Apple")).not.toBeInTheDocument();
  });

  it("moves from a child node to its parent with Left Arrow", async () => {
    await renderTree();
    await focusTree();
    await userEvent.keyboard("{ArrowDown}{ArrowDown}");

    await userEvent.keyboard("{ArrowLeft}");
    await expect.element(item("Fruits")).toHaveFocus();
    await expect.element(item("Fruits")).toHaveAttribute("aria-expanded", "true");
  });

  it("does nothing on Left Arrow at a root end node", async () => {
    await renderTree();
    await focusTree();
    await userEvent.keyboard("{End}");

    await userEvent.keyboard("{ArrowLeft}");
    await expect.element(item("Cherries")).toHaveFocus();
  });

  it("moves to the first node with Home and the last visible node with End", async () => {
    await renderTree();
    await focusTree();
    await userEvent.keyboard("{ArrowDown}{ArrowDown}{ArrowDown}{ArrowRight}");

    // Vegetables is open now, but its children sit above the last node.
    await userEvent.keyboard("{End}");
    await expect.element(item("Cherries")).toHaveFocus();

    await userEvent.keyboard("{Home}");
    await expect.element(item("Fruits")).toHaveFocus();
  });

  it("opens every sibling of the focused node with *", async () => {
    await renderTree();
    await focusTree();

    await userEvent.keyboard("*");
    await expect.element(item("Vegetables")).toHaveAttribute("aria-expanded", "true");
    await expect.element(item("Fruits")).toHaveAttribute("aria-expanded", "true");
    // Only siblings open, not their descendants.
    await expect.element(item("Carrot")).toHaveAttribute("aria-expanded", "false");
    await expect.element(item("Fruits")).toHaveFocus();
  });

  it("moves to the next visible node starting with a typed character, wrapping around", async () => {
    await renderTree();
    await focusTree();

    // Carrot and Celery start with "c" but are hidden inside the closed Vegetables.
    await userEvent.keyboard("c");
    await expect.element(item("Cherries")).toHaveFocus();

    await userEvent.keyboard("b");
    await expect.element(item("Banana")).toHaveFocus();
  });
});

describe("controls inside a row", () => {
  it("tells each row whether it is the tree's tab stop", async () => {
    await render(
      <Tree
        aria-label="Food"
        items={items}
        getId={(item) => item.id}
        getChildren={(item) => item.children}
        getLabel={(item) => item.name}
        renderItem={(item, { tabbable }) => (
          <TreeItem data-tabbable={String(tabbable)}>{item.name}</TreeItem>
        )}
      />,
    );

    await expect.element(item("Fruits")).toHaveAttribute("data-tabbable", "true");
    await expect.element(item("Grains")).toHaveAttribute("data-tabbable", "false");

    await focusTree();
    await userEvent.keyboard("{End}");
    await expect.element(item("Cherries")).toHaveAttribute("data-tabbable", "true");
    await expect.element(item("Fruits")).toHaveAttribute("data-tabbable", "false");
  });

  it("leaves keys pressed in a control to that control", async () => {
    await render(
      <Tree
        aria-label="Food"
        items={items}
        getId={(item) => item.id}
        getChildren={(item) => item.children}
        getLabel={(item) => item.name}
        renderItem={(item) => (
          <div>
            <TreeItem>{item.name}</TreeItem>
            <button type="button">Edit {item.name}</button>
          </div>
        )}
      />,
    );

    const edit = page.getByRole("button", { name: "Edit Fruits" });
    await edit.element().focus();
    await userEvent.keyboard("{ArrowDown}{End}g");

    await expect.element(edit).toHaveFocus();
  });
});

describe("selection", () => {
  it("selects the focused node with Enter and Space", async () => {
    const onSelect = vi.fn();
    await renderTree({ onSelect });
    await focusTree();

    await userEvent.keyboard("{ArrowDown}{Enter}");
    expect(onSelect).toHaveBeenLastCalledWith("apple");

    await userEvent.keyboard("{ArrowDown} ");
    expect(onSelect).toHaveBeenLastCalledWith("banana");
  });

  it("does not select when moving focus", async () => {
    const onSelect = vi.fn();
    await renderTree({ onSelect });
    await focusTree();

    await userEvent.keyboard("{ArrowDown}{ArrowDown}{End}{Home}");
    expect(onSelect).not.toHaveBeenCalled();
  });
});

describe("pointer", () => {
  it("focuses and selects a clicked node", async () => {
    const onSelect = vi.fn();
    await renderTree({ onSelect });

    await item("Banana").getByText("Banana").click();
    await expect.element(item("Banana")).toHaveFocus();
    expect(onSelect).toHaveBeenCalledExactlyOnceWith("banana");
  });

  it("toggles a clicked parent node", async () => {
    await renderTree();

    await item("Vegetables").getByText("Vegetables").click();
    await expect.element(item("Vegetables")).toHaveAttribute("aria-expanded", "true");
    await expect.element(item("Vegetables")).toHaveFocus();

    await item("Vegetables").getByText("Vegetables").click();
    await expect.element(item("Vegetables")).toHaveAttribute("aria-expanded", "false");
  });

  it("selects only the clicked child, not its ancestors", async () => {
    const onSelect = vi.fn();
    await renderTree({ onSelect });

    await item("Apple").getByText("Apple").click();
    expect(onSelect).toHaveBeenCalledExactlyOnceWith("apple");
    await expect.element(item("Fruits")).toHaveAttribute("aria-expanded", "true");
  });
});

describe("render prop", () => {
  function renderLinkTree(onLinkClick: (href: string) => void) {
    return render(
      <Tree
        aria-label="Food"
        items={items}
        getId={(item) => item.id}
        getChildren={(item) => item.children}
        getLabel={(item) => item.name}
        renderItem={(item) => (
          <TreeItem
            render={<a href={`#${item.id}`} />}
            className="row"
            onClick={(event) => {
              event.preventDefault();
              onLinkClick(event.currentTarget.getAttribute("href") ?? "");
            }}
          >
            {item.name}
          </TreeItem>
        )}
      />,
    );
  }

  it("renders each tree item as the given element, keeping its tree semantics", async () => {
    await renderLinkTree(() => {});

    const fruits = item("Fruits");
    expect(fruits.element().tagName).toBe("A");
    await expect.element(fruits).toHaveAttribute("href", "#fruits");
    await expect.element(fruits).toHaveAttribute("aria-expanded", "false");
    await expect.element(fruits).toHaveAttribute("tabindex", "0");
    await expect.element(fruits).toHaveClass("row");
  });

  it("activates the focused item with Enter, running the tree's and the consumer's click handlers", async () => {
    const onLinkClick = vi.fn();
    await renderLinkTree(onLinkClick);
    await focusTree();

    await userEvent.keyboard("{End}{Enter}");
    expect(onLinkClick).toHaveBeenCalledExactlyOnceWith("#cherries");

    await userEvent.keyboard("{Home}{Enter}");
    expect(onLinkClick).toHaveBeenLastCalledWith("#fruits");
    // The tree's own click handling still opens the parent.
    await expect.element(item("Fruits")).toHaveAttribute("aria-expanded", "true");
    await expect.element(item("Fruits")).toHaveFocus();
  });

  it("passes the item state to a render function", async () => {
    await render(
      <Tree
        aria-label="Food"
        items={items}
        getId={(item) => item.id}
        getChildren={(item) => item.children}
        getLabel={(item) => item.name}
        defaultExpandedIds={["fruits"]}
        renderItem={(item) => (
          <TreeItem
            render={(props, state) => (
              <div {...props} data-testid={`${item.id}-${state.level}-${String(state.expanded)}`} />
            )}
          >
            {item.name}
          </TreeItem>
        )}
      />,
    );

    await expect.element(page.getByTestId("fruits-0-true")).toBeInTheDocument();
    await expect.element(page.getByTestId("apple-1-undefined")).toBeInTheDocument();
    await expect.element(page.getByTestId("vegetables-0-false")).toBeInTheDocument();
  });
});
