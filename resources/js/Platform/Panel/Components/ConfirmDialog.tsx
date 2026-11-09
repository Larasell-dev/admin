import { Form } from "@inertiajs/react";
import type { Method } from "@inertiajs/core";
import { AlertDialog, Field, Input } from "@larasell-dev/ui";
import { HotkeysProvider } from "@tanstack/react-hotkeys";
import type { ReactNode } from "react";
import { overlayExclusive } from "../../Support/hotkeys";
import { AnyAlertDialogPopup } from "./PersistentAlertDialog";
import { fbs } from "fbtee";

type PopupProps = {
  /** "Verb thing", e.g. "Delete domain". */
  title: ReactNode;
  /** What happens once confirmed. */
  description: ReactNode;
  /** Where the form sends the confirmation. */
  action: string;
  method?: Method;
  /** Text the admin types to confirm, e.g. the record's name; the server checks it again. */
  confirmationText?: string | null;
  /** The verb, e.g. "Revoke". */
  confirmButton?: ReactNode;
};

/**
 * Asks the admin to confirm a destructive request, optionally by typing a text, and sends it
 * with a form that closes the dialog once it succeeds. Goes into an `AlertDialog.Root` (the
 * `ConfirmDialog.Root`) or, when confirming leaves the page, a `PersistentAlertDialog.Root`.
 */
function Popup({
  title,
  description,
  action,
  method = "delete",
  confirmationText = null,
  confirmButton,
}: PopupProps) {
  return (
    <AnyAlertDialogPopup title={title} description={description}>
      {({ close }) => (
        <HotkeysProvider scope="confirm-dialog" exclusive={overlayExclusive}>
          {/* The popup unmounts once closed, so the typed text starts empty each time. */}
          <Form
            action={action}
            method={method}
            options={{ preserveScroll: true }}
            onSuccess={close}
          >
            {({ errors, processing }) => {
              // Also shows why the server refused, e.g. a channel with orders can't be deleted.
              const error = errors.confirmation ?? Object.values(errors)[0];

              return (
                <>
                  {confirmationText !== null ? (
                    <Field
                      label={fbs(
                        ['Type "', fbs.param("confirmationText", confirmationText), '" to confirm'],
                        "Confirmation dialog, e.g. deleting: label of the field the admin types the given text into to confirm, e.g. 'Type \"DELETE\" to confirm'",
                      )}
                      name="confirmation"
                      error={error}
                    >
                      <Input autoComplete="off" spellCheck={false} />
                    </Field>
                  ) : (
                    error && (
                      <p role="alert" className="type-body-md text-intent-negative">
                        {error}
                      </p>
                    )
                  )}
                  <AlertDialog.Actions>
                    <AlertDialog.Cancel>
                      <fbt desc="Confirmation dialog, e.g. deleting: button that closes it without doing anything">
                        Cancel
                      </fbt>
                    </AlertDialog.Cancel>
                    <AlertDialog.Confirm type="submit" variant="destructive" loading={processing}>
                      {confirmButton ?? (
                        <fbt desc="Confirmation dialog: button that deletes what the dialog asks about">
                          Delete
                        </fbt>
                      )}
                    </AlertDialog.Confirm>
                  </AlertDialog.Actions>
                </>
              );
            }}
          </Form>
        </HotkeysProvider>
      )}
    </AnyAlertDialogPopup>
  );
}

/**
 * A confirmation for something destructive. Use it like `AlertDialog`, with `ConfirmDialog.Popup`
 * as the popup; put that popup in a `PersistentAlertDialog.Root` instead when confirming leaves
 * the page, so it still animates out.
 */
const ConfirmDialog = { Root: AlertDialog.Root, Trigger: AlertDialog.Trigger, Popup };
export default ConfirmDialog;
