import { expect, it } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import { ErrorBoundary } from "./ErrorBoundary";

function Content({ crash }: { crash: boolean }) {
  if (crash) {
    throw new Error("Crashed");
  }

  return <p>Content</p>;
}

function renderBoundary(resetKey: string, crash: boolean) {
  return (
    <ErrorBoundary resetKey={resetKey} frame={(crashed) => <div>{crashed}</div>}>
      <Content crash={crash} />
    </ErrorBoundary>
  );
}

it("shows the crashed state until the reset key changes", async () => {
  // Arrange
  const screen = await render(renderBoundary("/admin/a", true));
  await expect.element(page.getByRole("button", { name: "Reload page" })).toBeVisible();

  // Act
  await screen.rerender(renderBoundary("/admin/a", false));
  await expect.element(page.getByRole("button", { name: "Reload page" })).toBeVisible();
  await screen.rerender(renderBoundary("/admin/b", false));

  // Assert
  await expect.element(page.getByText("Content")).toBeVisible();
});
