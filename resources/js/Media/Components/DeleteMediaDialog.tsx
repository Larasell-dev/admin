import { router } from "@inertiajs/react";
import { AlertDialog, Field, Input } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import { useState } from "react";

/** What the admin types to confirm a delete; the server checks the same text. */
const DELETE_CONFIRMATION = "Delete media";

/**
 * Asks to type "Delete media" before deleting a media item. The button stays enabled; the server
 * checks the text and says what to type. Rendered once per page, outside any list, so it keeps
 * its item while it animates out.
 */
export function DeleteMediaDialog({
  destroyUrl,
  open,
  onOpenChange,
}: {
  destroyUrl: string;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const [typed, setTyped] = useState("");
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState<string>();

  return (
    <AlertDialog.Root
      open={open}
      onOpenChange={(next) => {
        onOpenChange(next);
        setTyped("");
        setError(undefined);
      }}
    >
      <AlertDialog.Popup
        title={fbs(
          "Delete media",
          "Media page: title of the dialog that asks to confirm deleting the media",
        )}
        description={fbs(
          "Its files are removed from the store. This can't be undone.",
          "Media page: explains what deleting the media does",
        )}
      >
        <Field
          label={fbs(
            ['Type "', fbs.param("confirmationText", DELETE_CONFIRMATION), '" to confirm'],
            "Media page: label of the field in the delete dialog that the admin types the given text into to confirm, e.g. 'Type \"delete media\" to confirm'",
          )}
          name="confirmation"
          error={error}
        >
          <Input
            autoComplete="off"
            value={typed}
            onChange={(event) => setTyped(event.currentTarget.value)}
          />
        </Field>
        <AlertDialog.Actions>
          <AlertDialog.Cancel>
            <fbt desc="Media page: button that closes the delete dialog without deleting">
              Cancel
            </fbt>
          </AlertDialog.Cancel>
          <AlertDialog.Confirm
            variant="destructive"
            loading={processing}
            onClick={() =>
              router.delete(destroyUrl, {
                data: { confirmation: typed },
                preserveScroll: true,
                onSuccess: () => onOpenChange(false),
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) => setError(errors.confirmation),
              })
            }
          >
            <fbt desc="Media page: button in the dialog that deletes the media">Delete</fbt>
          </AlertDialog.Confirm>
        </AlertDialog.Actions>
      </AlertDialog.Popup>
    </AlertDialog.Root>
  );
}
