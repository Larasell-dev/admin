import { Form, router } from "@inertiajs/react";
import { AlertDialog, Button, Field, Icon, Input, Section, SectionStack } from "@larasell-dev/ui";
import { DeleteBinLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import { useState } from "react";
import { Page } from "../../Platform/Panel/Components/Page";
import PersistentAlertDialog from "../../Platform/Panel/Components/PersistentAlertDialog";
import EntryPoints from "../Components/EntryPoints";
import SalesChannelForm from "../Components/SalesChannelForm";
import UnsavedChangesToast from "../../Platform/Panel/Components/UnsavedChangesToast";
import SidebarLayout from "../../Platform/Panel/Layouts/SidebarLayout";

type Props = Larasell.Admin.SalesChannel.Data.EditSalesChannelPageData;

export default function Edit({
  channel,
  updateUrl,
  destroyUrl,
  locales,
  currencies,
  entryPoints,
  addEntryPointUrl,
}: Props) {
  return (
    <>
      <Page.Header title={channel.name} />
      <Page.Body>
        <SectionStack>
          <Form
            // Inertia keeps this page mounted when moving between channels; remounting resets the fields.
            key={updateUrl}
            action={updateUrl}
            method="put"
            // The saved values become the new baseline, so the form is no longer dirty.
            setDefaultsOnSuccess
          >
            <SalesChannelForm channel={channel} locales={locales} currencies={currencies} />
            <UnsavedChangesToast />
          </Form>
          {/* Outside the form, so adding or deleting a domain or the channel never submits it. */}
          {entryPoints && addEntryPointUrl && (
            <EntryPoints
              channel={channel}
              entryPoints={entryPoints}
              addUrl={addEntryPointUrl}
              locales={locales}
              currencies={currencies}
            />
          )}
          <Section.Root>
            <Section.Header
              title={fbs(
                "Delete sales channel",
                "Sales channel page: heading of the last section, with the button that deletes the channel",
              )}
              description={fbs(
                "Customers can no longer reach the channel. Channels with orders or carts can only be deactivated.",
                "Sales channel page: explains the section with the button that deletes the channel",
              )}
            />
            <div>
              <DeleteChannel name={channel.name} destroyUrl={destroyUrl} />
            </div>
          </Section.Root>
        </SectionStack>
      </Page.Body>
    </>
  );
}

/** Deletes the channel after asking; the server refuses it when carts or orders were made in it. */
function DeleteChannel({ name, destroyUrl }: { name: string; destroyUrl: string }) {
  const [open, setOpen] = useState(false);
  const [processing, setProcessing] = useState(false);
  const [typed, setTyped] = useState("");
  // Why the server refused: the typed name didn't match, or the channel can't be deleted at all.
  const [errors, setErrors] = useState<{ confirmation?: string; channel?: string }>({});

  const confirm = () =>
    router.delete(destroyUrl, {
      data: { confirmation: typed },
      onStart: () => setProcessing(true),
      onFinish: () => setProcessing(false),
      onSuccess: () => setOpen(false),
      onError: setErrors,
    });

  return (
    <>
      <Button variant="destructive" onClick={() => setOpen(true)}>
        <Icon icon={DeleteBinLine} />
        <fbt desc="Sales channel page: button in the delete section, below the currencies, that asks to delete the channel">
          Delete
        </fbt>
      </Button>
      {/* Persistent, since deleting leads to the dashboard and the dialog animates out over it. */}
      <PersistentAlertDialog
        open={open}
        onOpenChange={(next) => {
          setOpen(next);
          setTyped("");
          setErrors({});
        }}
      >
        <AlertDialog.Popup
          title={fbs(
            "Delete sales channel",
            "Sales channel page: title of the dialog that asks to confirm deleting the channel",
          )}
          description={
            errors.channel ??
            fbs(
              [
                "Customers can no longer reach ",
                fbs.param("channelName", name),
                ". This can't be undone.",
              ],
              "Sales channel page: explains what deleting the channel does, e.g. 'Customers can no longer reach Default, …'",
            )
          }
        >
          <Field
            label={fbs(
              ['Type "', fbs.param("channelName", name), '" to confirm'],
              "Sales channel page: label of the field in the delete dialog that the admin types the channel's name into to confirm, e.g. 'Type \"Default\" to confirm'",
            )}
            name="confirmation"
            error={errors.confirmation}
          >
            <Input
              autoComplete="off"
              value={typed}
              disabled={errors.channel !== undefined}
              onChange={(event) => setTyped(event.currentTarget.value)}
            />
          </Field>
          <AlertDialog.Actions>
            <AlertDialog.Cancel>
              <fbt desc="Sales channel page: button that closes the delete dialog without deleting">
                Cancel
              </fbt>
            </AlertDialog.Cancel>
            <AlertDialog.Confirm
              variant="destructive"
              loading={processing}
              disabled={errors.channel !== undefined}
              onClick={confirm}
            >
              <fbt desc="Sales channel page: button in the dialog that deletes the channel">
                Delete
              </fbt>
            </AlertDialog.Confirm>
          </AlertDialog.Actions>
        </AlertDialog.Popup>
      </PersistentAlertDialog>
    </>
  );
}

Edit.layout = [SidebarLayout];
