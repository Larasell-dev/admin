import { AlertDialog, Field, Input } from "@larasell-dev/ui";
import { useRef, useState } from "react";
import type { TableAction } from "../types";

type Props = {
  /** The action waiting for confirmation, or null when the dialog is closed. */
  action: TableAction | null;
  processing: boolean;
  error?: string;
  onConfirm: (typed: string) => void;
  onCancel: () => void;
};

/** Asks the admin to confirm a table action, optionally by typing a given text. */
export default function ConfirmActionDialog({ action, ...props }: Props) {
  // Keep showing the last action while the dialog animates out, so its copy
  // does not go blank as soon as the action is cleared.
  const shown = useRef(action);
  if (action) shown.current = action;

  return (
    <AlertDialog.Root open={action !== null} onOpenChange={(open) => !open && props.onCancel()}>
      {shown.current?.confirm && (
        // Keyed by the action, so the typed text starts empty for each one.
        <Confirmation key={shown.current.url} action={shown.current} {...props} />
      )}
    </AlertDialog.Root>
  );
}

function Confirmation({
  action,
  processing,
  error,
  onConfirm,
}: Omit<Props, "action" | "onCancel"> & { action: TableAction }) {
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
