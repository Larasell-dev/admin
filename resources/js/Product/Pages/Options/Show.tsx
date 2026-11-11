import { Form } from "@inertiajs/react";
import { Button, Icon, Section, SectionStack } from "@larasell-dev/ui";
import { DeleteBinLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import { useState } from "react";
import OptionForm, { type OptionDisplay } from "../../Components/OptionForm";
import OptionValues from "../../Components/OptionValues";
import ContentLocaleSelect from "../../../Platform/ContentLocale/Components/ContentLocaleSelect";
import ConfirmDialog from "../../../Platform/Panel/Components/ConfirmDialog";
import { Page } from "../../../Platform/Panel/Components/Page";
import PersistentAlertDialog from "../../../Platform/Panel/Components/PersistentAlertDialog";
import UnsavedChangesToast from "../../../Platform/Panel/Components/UnsavedChangesToast";
import SidebarLayout from "../../../Platform/Panel/Layouts/SidebarLayout";
import type { SharedProps } from "../../../types";

type Props = Larasell.Admin.Product.Data.OptionPageData & Pick<SharedProps, "contentLocale">;

export default function Show(props: Props) {
  const { option, values, displays, updateUrl, destroyUrl, indexUrl } = props;
  const name = option.name ?? option.fallbackName ?? "";
  const [display, setDisplay] = useState<OptionDisplay>(option.display);

  return (
    <>
      <Page.Header
        title={name}
        backLink={{
          href: indexUrl,
          label: fbs(
            "Back to options",
            "Option page: accessible name of the back arrow to the list of options",
          ),
        }}
        actions={<ContentLocaleSelect contentLocale={props.contentLocale} />}
      />
      <Page.Body>
        <SectionStack>
          <Form
            // Remounts with the new values when the language changes.
            key={`${option.id}-${props.contentLocale?.current}`}
            action={updateUrl}
            method="put"
            setDefaultsOnSuccess
            options={{ preserveScroll: true }}
          >
            <SectionStack>
              <Section.Root>
                <Section.Header
                  title={fbs("General", "Option page: heading of the option's settings")}
                  description={fbs(
                    "How the option is named, and how the store shows it.",
                    "Option page: explains the section with the option's settings",
                  )}
                />
                <OptionForm
                  option={option}
                  displays={displays}
                  display={display}
                  onDisplayChange={setDisplay}
                />
              </Section.Root>
              <OptionValues
                // Starts again from what was saved once the server sends other values.
                key={values
                  .map((value) => `${value.id}:${value.slug}:${value.name}:${value.swatch}`)
                  .join()}
                values={values}
                swatches={display === "swatch"}
              />
            </SectionStack>
            <UnsavedChangesToast />
          </Form>
          <Section.Root>
            <Section.Header
              title={fbs("Delete option", "Option page: heading of the delete section")}
              description={
                option.variants > 0
                  ? fbs(
                      [
                        "Variants use it: ",
                        fbs.plural("variant", option.variants, {
                          many: "variants",
                          showCount: "yes",
                          name: "variants",
                        }),
                        " of ",
                        fbs.plural("product", option.products, {
                          many: "products",
                          showCount: "yes",
                          name: "products",
                        }),
                        ". Delete those variants on their products first.",
                      ],
                      "Option page: explains why an option in use can't be deleted",
                    )
                  : fbs(
                      "Its values are deleted with it. No variant uses it.",
                      "Option page: explains the section with the button that deletes an unused option",
                    )
              }
            />
            <div>
              <PersistentAlertDialog.Root>
                <PersistentAlertDialog.Trigger
                  render={<Button variant="destructive" disabled={option.variants > 0} />}
                >
                  <Icon icon={DeleteBinLine} />
                  <fbt desc="Option page: button that asks to delete the option">Delete</fbt>
                </PersistentAlertDialog.Trigger>
                <ConfirmDialog.Popup
                  title={fbs("Delete option", "Option page: title of the delete dialog")}
                  description={fbs(
                    [
                      fbs.param("name", name),
                      " and its values are deleted, and products stop offering them. This can't be undone.",
                    ],
                    "Option page: delete dialog text",
                  )}
                  action={destroyUrl}
                  // The server checks it too.
                  confirmationText={option.slug}
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
