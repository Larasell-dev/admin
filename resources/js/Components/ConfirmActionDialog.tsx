import { AlertDialog, Field, Input } from "@larasell-dev/ui";
import { HotkeysProvider } from "@tanstack/react-hotkeys";
import { useState } from "react";
import type { TableRowAction } from "../types";
import { overlayExclusive } from "../utils/hotkeys";
import Freeze from "./Freeze";

type Props = {
  /** The action waiting for confirmation, or null when the dialog is closed. */
  action: TableRowAction | null;
  processing: boolean;
  error?: string;
  onConfirm: (typed: string) => void;
  onCancel: () => void;
};

/** Asks the admin to confirm a table action, optionally by typing a given text. */
export default function ConfirmActionDialog({ action, ...props }: Props) {
  return (
    <AlertDialog.Root open={action !== null} onOpenChange={(open) => !open && props.onCancel()}>
      {/* Keeps the last action and its state while the dialog animates out, so its copy does not
          go blank once the action is cleared, nor its button stop loading once the request ends. */}
      {/* Enabled by the action rather than mounting, since the frozen content stays mounted. */}
      <HotkeysProvider
        scope="confirm-action-dialog"
        exclusive={overlayExclusive}
        enabled={action !== null}
      >
        <Freeze frozen={action === null}>
          {action?.confirm && (
            // Keyed by the action, so the typed text starts empty for each one.
            <Confirmation key={action.url} action={action} {...props} />
          )}
        </Freeze>
      </HotkeysProvider>
    </AlertDialog.Root>
  );
}

function Confirmation({
  action,
  processing,
  error,
  onConfirm,
}: Omit<Props, "action" | "onCancel"> & { action: TableRowAction }) {
  const [typed, setTyped] = useState("");
  const confirm = action.confirm!;

  return (
    <AlertDialog.Popup title={confirm.title} description={confirm.message}>
      {confirm.confirmationText !== null && (
        <Field
          label={`Type "${confirm.confirmationText}" to confirm`}
          name="confirmation"
          error={error}
        >
          <Input
            autoComplete="off"
            value={typed}
            onChange={(event) => setTyped(event.currentTarget.value)}
          />
        </Field>
      )}
      <AlertDialog.Actions>
        <AlertDialog.Cancel>{confirm.cancelButton}</AlertDialog.Cancel>
        <AlertDialog.Confirm
          variant={action.destructive ? "destructive" : "primary"}
          loading={processing}
          onClick={() => onConfirm(typed)}
        >
          {confirm.confirmButton}
        </AlertDialog.Confirm>
      </AlertDialog.Actions>
    </AlertDialog.Popup>
  );
}
