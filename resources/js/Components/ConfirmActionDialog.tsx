import { Dialog } from "@base-ui/react/dialog";
import { Button, Field, Input, Stack } from "@larasell-dev/ui";
import { useState } from "react";
import type { TableActionConfirmation } from "../types";

type Props = {
  confirmation: TableActionConfirmation | null;
  processing: boolean;
  error?: string;
  onConfirm: (typed: string) => void;
  onCancel: () => void;
};

/** Asks the admin to confirm a table action, optionally by typing a given text. */
export default function ConfirmActionDialog({
  confirmation,
  processing,
  error,
  onConfirm,
  onCancel,
}: Props) {
  const [typed, setTyped] = useState("");
  const required = confirmation?.confirmationText ?? null;
  const matches = required === null || typed === required;

  return (
    <Dialog.Root
      open={confirmation !== null}
      onOpenChange={(open) => !open && onCancel()}
      onOpenChangeComplete={(open) => !open && setTyped("")}
    >
      <Dialog.Portal>
        <Dialog.Backdrop className="fixed inset-0 z-40 bg-[rgb(0_0_0/20%)] transition-opacity duration-150 data-ending-style:opacity-0 data-starting-style:opacity-0" />
        <Dialog.Popup className="fixed top-1/2 left-1/2 z-50 w-[calc(100vw-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 border border-border bg-white p-6 shadow-lg outline-none transition-opacity duration-150 data-ending-style:opacity-0 data-starting-style:opacity-0 dark:bg-mauve-950">
          {confirmation && (
            <form
              onSubmit={(event) => {
                event.preventDefault();
                if (matches) onConfirm(typed);
              }}
            >
              <Stack spacing={6}>
                <Stack spacing={2}>
                  <Dialog.Title className="type-body-lg text-mauve-950 dark:text-mauve-50">
                    {confirmation.title}
                  </Dialog.Title>
                  <Dialog.Description className="type-body-md text-mauve-600 dark:text-mauve-400">
                    {confirmation.message}
                  </Dialog.Description>
                </Stack>

                {required !== null && (
                  <Field label={`Type "${required}" to confirm`} name="confirmation" error={error}>
                    <Input
                      autoComplete="off"
                      autoFocus
                      value={typed}
                      onChange={(event) => setTyped(event.currentTarget.value)}
                    />
                  </Field>
                )}

                <div className="flex justify-end gap-2">
                  <Button type="button" variant="secondary" onClick={onCancel}>
                    {confirmation.cancelButton}
                  </Button>
                  <Button
                    type="submit"
                    {...(processing ? { loading: true } : { disabled: !matches })}
                  >
                    {confirmation.confirmButton}
                  </Button>
                </div>
              </Stack>
            </form>
          )}
        </Dialog.Popup>
      </Dialog.Portal>
    </Dialog.Root>
  );
}
