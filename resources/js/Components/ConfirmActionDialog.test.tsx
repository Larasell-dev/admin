import "../../css/app.css";
import { expect, it } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import type { TableAction } from "../types";
import ConfirmActionDialog from "./ConfirmActionDialog";

const deletion: TableAction = {
  name: "delete",
  label: "Delete",
  method: "post",
  url: "/admin/things/1",
  confirm: {
    title: "Delete thing",
    message: "This can't be undone.",
    confirmButton: "Delete",
    cancelButton: "Cancel",
    confirmationText: null,
  },
  destructive: true,
  disabled: false,
  icon: null,
};

it("keeps its content as it was while animating out after the action finishes", async () => {
  const props = { onConfirm: () => {}, onCancel: () => {} };
  const screen = await render(<ConfirmActionDialog action={deletion} processing {...props} />);
  const confirm = page.getByRole("alertdialog", { name: "Delete thing" });
  await expect
    .element(confirm.getByRole("button", { name: "Delete" }))
    .toHaveAttribute("aria-busy");
  const dialog = confirm.element();

  // The request finished: the action is cleared and processing ends in the same render.
  await screen.rerender(<ConfirmActionDialog action={null} processing={false} {...props} />);

  expect(dialog).toHaveAttribute("data-ending-style");
  expect(dialog.querySelector("button[aria-busy]")?.textContent).toContain("Delete");
  await expect.poll(() => dialog.isConnected).toBe(false);
});
