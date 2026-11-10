import { Form } from "@inertiajs/react";
import { Button, Field, Icon, Input, Section, SectionStack, Stack } from "@larasell-dev/ui";
import { DeleteBinLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import ConfirmDialog from "../../Platform/Panel/Components/ConfirmDialog";
import { Page } from "../../Platform/Panel/Components/Page";
import UnsavedChangesToast from "../../Platform/Panel/Components/UnsavedChangesToast";
import PersistentAlertDialog from "../../Platform/Panel/Components/PersistentAlertDialog";
import SidebarLayout from "../../Platform/Panel/Layouts/SidebarLayout";

type Props = Larasell.Admin.Customer.Data.CustomerGroupPageData;

/** Adds a customer group, or edits one when the page has a group. */
export default function EditGroup({ group, saveUrl, destroyUrl, indexUrl }: Props) {
  return (
    <>
      <Page.Header
        title={
          group?.name ??
          fbs("New customer group", "Customer group page: title while adding a group")
        }
        backLink={{
          href: indexUrl,
          label: fbs(
            "Back to customer groups",
            "Customer group page: accessible name of the back arrow",
          ),
        }}
      />
      <Page.Body>
        <SectionStack>
          <Form key={saveUrl} action={saveUrl} method={group ? "put" : "post"} setDefaultsOnSuccess>
            {({ errors }) => (
              <Section.Root>
                <Section.Header
                  title={fbs(
                    "General",
                    "Customer group page: heading of the section with the group's name and code",
                  )}
                  description={fbs(
                    "Rules for prices or catalogs refer to the group by its code.",
                    "Customer group page: explains the section with the group's name and code",
                  )}
                />
                <Stack spacing={5}>
                  <Field
                    label={fbs("Name", "Customer group page: label of the group's name")}
                    name="name"
                    error={errors.name}
                  >
                    <Input defaultValue={group?.name} autoComplete="off" />
                  </Field>
                  <Field
                    label={fbs(
                      "Code",
                      "Customer group page: label of the group's code, e.g. wholesale",
                    )}
                    name="code"
                    error={errors.code}
                  >
                    <Input defaultValue={group?.code} autoComplete="off" spellCheck={false} />
                  </Field>
                  <Field
                    label={fbs(
                      "Description",
                      "Customer group page: label of the group's optional description",
                    )}
                    name="description"
                    error={errors.description}
                  >
                    {/* The UI package has no textarea yet; this one matches its inputs. */}
                    <textarea
                      name="description"
                      rows={4}
                      defaultValue={group?.description ?? ""}
                      className="type-input-md border-base-strong bg-base-surface text-base-primary placeholder:text-base-tertiary focus:focus-ring min-h-28 w-full resize-y border px-3.5 py-2.5 outline-none"
                    />
                  </Field>
                </Stack>
                <UnsavedChangesToast />
              </Section.Root>
            )}
          </Form>
          {destroyUrl && (
            <Section.Root>
              <Section.Header
                title={fbs(
                  "Delete customer group",
                  "Customer group page: heading of the section that deletes the group",
                )}
                description={fbs(
                  "Only groups without customers can be deleted.",
                  "Customer group page: explains when the group can be deleted",
                )}
              />
              <div>{group && <DeleteGroup code={group.code} destroyUrl={destroyUrl} />}</div>
            </Section.Root>
          )}
        </SectionStack>
      </Page.Body>
    </>
  );
}

EditGroup.layout = [SidebarLayout];

/**
 * Deletes the group once the admin typed its code; the server refuses a group that has customers.
 * Deleting leaves the page, so the layout draws the dialog and it animates out over the list.
 */
function DeleteGroup({ code, destroyUrl }: { code: string; destroyUrl: string }) {
  return (
    <PersistentAlertDialog.Root>
      <PersistentAlertDialog.Trigger render={<Button variant="destructive" />}>
        <Icon icon={DeleteBinLine} />
        <fbt desc="Customer group page: button that asks to delete the group">Delete</fbt>
      </PersistentAlertDialog.Trigger>
      <ConfirmDialog.Popup
        title={fbs(
          "Delete customer group",
          "Customer group page: title of the dialog that confirms deleting the group",
        )}
        description={fbs(
          "Rules that refer to the group's code stop applying. This can't be undone.",
          "Customer group page: explains what deleting the group does",
        )}
        action={destroyUrl}
        confirmationText={code}
      />
    </PersistentAlertDialog.Root>
  );
}
