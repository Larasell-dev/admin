import "../../../../css/app.css";
import { AlertDialog } from "@larasell-dev/ui";
import { expect, it } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import PersistentAlertDialog, { PersistentAlertDialogHost } from "./PersistentAlertDialog";

function Panel({ pageShown }: { pageShown: boolean }) {
  return (
    <>
      {pageShown && (
        <PersistentAlertDialog open>
          <AlertDialog.Popup title="Delete channel?" />
        </PersistentAlertDialog>
      )}
      {/* SidebarLayout renders the host in the panel. */}
      <PersistentAlertDialogHost />
    </>
  );
}

it("keeps the dialog while it animates out after its page is gone", async () => {
  // Arrange
  const screen = await render(<Panel pageShown />);
  const dialog = page.getByRole("alertdialog", { name: "Delete channel?" });
  await expect.element(dialog).toBeVisible();

  // Act
  await screen.rerender(<Panel pageShown={false} />);

  // Assert
  expect(document.querySelector("[role=alertdialog]")).not.toBeNull();
  await expect.element(dialog).not.toBeInTheDocument();
});
