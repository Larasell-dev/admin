import { Form } from "@inertiajs/react";
import { Button, Icon, Section, SectionStack } from "@larasell-dev/ui";
import { DeleteBinLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import { Page } from "../../Platform/Panel/Components/Page";
import ConfirmDialog from "../../Platform/Panel/Components/ConfirmDialog";
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
  return (
    // Persistent, since deleting leads to the dashboard and the dialog animates out over it.
    <PersistentAlertDialog.Root>
      <PersistentAlertDialog.Trigger render={<Button variant="destructive" />}>
        <Icon icon={DeleteBinLine} />
        <fbt desc="Sales channel page: button in the delete section, below the currencies, that asks to delete the channel">
          Delete
        </fbt>
      </PersistentAlertDialog.Trigger>
      <ConfirmDialog.Popup
        title={fbs(
          "Delete sales channel",
          "Sales channel page: title of the dialog that asks to confirm deleting the channel",
        )}
        description={fbs(
          [
            "Customers can no longer reach ",
            fbs.param("channelName", name),
            ". This can't be undone.",
          ],
          "Sales channel page: explains what deleting the channel does, e.g. 'Customers can no longer reach Default, …'",
        )}
        action={destroyUrl}
        // The server refuses it, saying why, when carts or orders were made in the channel.
        confirmationText={name}
      />
    </PersistentAlertDialog.Root>
  );
}

Edit.layout = [SidebarLayout];
