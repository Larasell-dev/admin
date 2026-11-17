import { Form } from "@inertiajs/react";
import { Button, Icon, Section, SectionStack } from "@larasell-dev/ui";
import { DeleteBinLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import { ManufacturerSections } from "./Create";
import ConfirmDialog from "../../Platform/Panel/Components/ConfirmDialog";
import { Page } from "../../Platform/Panel/Components/Page";
import PersistentAlertDialog from "../../Platform/Panel/Components/PersistentAlertDialog";
import UnsavedChangesToast from "../../Platform/Panel/Components/UnsavedChangesToast";
import SidebarLayout from "../../Platform/Panel/Layouts/SidebarLayout";

type Props = Larasell.Admin.Manufacturer.Data.ManufacturerPageData;

export default function Show({ manufacturer, countries, updateUrl, destroyUrl, indexUrl }: Props) {
  return (
    <>
      <Page.Header
        title={manufacturer.name}
        backLink={{
          href: indexUrl,
          label: fbs(
            "Back to manufacturers",
            "Manufacturer page: accessible name of the back arrow to the manufacturers",
          ),
        }}
      />
      <Page.Body>
        <SectionStack>
          <Form
            action={updateUrl}
            method="put"
            setDefaultsOnSuccess
            options={{ preserveScroll: true }}
          >
            <SectionStack>
              <ManufacturerSections manufacturer={manufacturer} countries={countries} />
            </SectionStack>
            <UnsavedChangesToast />
          </Form>
          <Section.Root>
            <Section.Header
              title={fbs("Delete manufacturer", "Manufacturer page: heading of the delete section")}
              description={
                manufacturer.products > 0
                  ? fbs(
                      [
                        "Used by ",
                        fbs.plural("product", manufacturer.products, {
                          many: "products",
                          showCount: "yes",
                          name: "products",
                        }),
                        ". Give them another manufacturer first.",
                      ],
                      "Manufacturer page: explains why a manufacturer products use can't be deleted",
                    )
                  : fbs(
                      "No product uses it.",
                      "Manufacturer page: explains that the manufacturer can be deleted",
                    )
              }
            />
            <div>
              <PersistentAlertDialog.Root>
                <PersistentAlertDialog.Trigger
                  render={<Button variant="destructive" disabled={manufacturer.products > 0} />}
                >
                  <Icon icon={DeleteBinLine} />
                  <fbt desc="Manufacturer page: button that asks to delete the manufacturer">
                    Delete
                  </fbt>
                </PersistentAlertDialog.Trigger>
                <ConfirmDialog.Popup
                  title={fbs(
                    "Delete manufacturer",
                    "Manufacturer page: title of the delete dialog",
                  )}
                  description={fbs(
                    [fbs.param("name", manufacturer.name), " is deleted. This can't be undone."],
                    "Manufacturer page: delete dialog text",
                  )}
                  action={destroyUrl}
                  confirmationText={manufacturer.name}
                />
              </PersistentAlertDialog.Root>
            </div>
          </Section.Root>
        </SectionStack>
      </Page.Body>
    </>
  );
}

Show.layout = [SidebarLayout];
