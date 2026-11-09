import type { TableRowAction } from "../../../types";
import ConfirmDialog from "./ConfirmDialog";
import Freeze from "./Freeze";

type Props = {
  /** The action waiting for confirmation, or null when the dialog is closed. */
  action: TableRowAction | null;
  onClose: () => void;
};

/**
 * Asks the admin to confirm a table action. It lives beside the table rather than in the row, so
 * it still animates out once the row it acts on is gone.
 */
export default function ConfirmActionDialog({ action, onClose }: Props) {
  return (
    <ConfirmDialog.Root open={action !== null} onOpenChange={(open) => !open && onClose()}>
      {/* Keeps the last action while the dialog animates out, so its copy does not go blank. */}
      <Freeze frozen={action === null}>
        {action?.confirm && (
          <ConfirmDialog.Popup
            title={action.confirm.title}
            description={action.confirm.message}
            action={action.url}
            method={action.method}
            confirmationText={action.confirm.confirmationText}
            confirmButton={action.confirm.confirmButton}
          />
        )}
      </Freeze>
    </ConfirmDialog.Root>
  );
}
