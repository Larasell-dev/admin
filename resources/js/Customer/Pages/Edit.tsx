import { Form, Link, router } from "@inertiajs/react";
import { useState } from "react";
import {
  Badge,
  EmptyState,
  Button,
  Field,
  Icon,
  IconButton,
  Input,
  Menu,
  Section,
  SectionStack,
  Select,
  Stack,
} from "@larasell-dev/ui";
import {
  DeleteBinLine,
  GroupLine,
  LinksLine,
  MapPinLine,
  More2Fill,
  UserLine,
} from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import { Page } from "../../Platform/Panel/Components/Page";
import UnsavedChangesToast from "../../Platform/Panel/Components/UnsavedChangesToast";
import SidebarLayout from "../../Platform/Panel/Layouts/SidebarLayout";
import AddDialog from "../Components/AddDialog";
import CountrySelect from "../Components/CountrySelect";
import { useConfirmRemoval } from "../Components/ConfirmRemoval";
import ConfirmDialog from "../../Platform/Panel/Components/ConfirmDialog";
import Freeze from "../../Platform/Panel/Components/Freeze";
import ProfileFields from "../Components/ProfileFields";

type Props = Larasell.Admin.Customer.Data.EditCustomerPageData;

export default function Edit({
  customer,
  updateUrl,
  indexUrl,
  statusUrl,
  addresses,
  addAddressUrl,
  contacts,
  addContactUrl,
  accounts,
  groups,
  availableGroups,
  addGroupUrl,
  createGroupUrl,
  locales,
  currencies,
  countries,
}: Props) {
  const addressRemoval = useConfirmRemoval();
  const groupRemoval = useConfirmRemoval();
  const contactRemoval = useConfirmRemoval();
  const accountUnlinking = useConfirmRemoval();
  const [addingAddress, setAddingAddress] = useState(false);
  const [addingContact, setAddingContact] = useState(false);
  const [addingGroup, setAddingGroup] = useState(false);

  return (
    <>
      <Page.Header
        title={customer.name}
        backLink={{
          href: indexUrl,
          label: fbs(
            "Back to customers",
            "Customer page: accessible name of the back arrow to the customers",
          ),
        }}
        actions={<StatusMenu status={customer.status} statusUrl={statusUrl} />}
      />
      <Page.Body>
        <SectionStack>
          <Form key={updateUrl} action={updateUrl} method="put" setDefaultsOnSuccess>
            <ProfileFields
              type={customer.type as "individual" | "company"}
              customer={customer}
              locales={locales}
              currencies={currencies}
              countries={countries}
            />
            <UnsavedChangesToast />
          </Form>

          <Section.Root>
            <Section.Header
              title={fbs(
                "Addresses",
                "Customer page: heading of the section with the customer's addresses",
              )}
              description={fbs(
                "Where the customer receives orders and invoices. The badges mark the defaults checkout suggests.",
                "Customer page: explains the addresses section and its default billing and shipping badges",
              )}
              action={
                addresses.length > 0 && (
                  <Button variant="secondary" onClick={() => setAddingAddress(true)}>
                    <fbt desc="Customer page: button that opens the dialog to add an address">
                      Add address
                    </fbt>
                  </Button>
                )
              }
            />
            <Stack spacing={4}>
              {addresses.length > 0 ? (
                <ul className="border-base-subtle divide-base-subtle divide-y border">
                  {addresses.map((address) => (
                    <li key={address.id} className="flex items-center gap-3 py-2 pr-2 pl-4">
                      <span className="type-body-md flex-1">
                        {address.firstName} {address.lastName},{" "}
                        {Object.values(address.street).join(", ")}, {address.postcode}{" "}
                        {address.city}, {address.countryCode}
                      </span>
                      {address.defaultBilling && (
                        <Badge>
                          <fbt desc="Customer page: badge on the default billing address">
                            Billing
                          </fbt>
                        </Badge>
                      )}
                      {address.defaultShipping ? (
                        <Badge>
                          <fbt desc="Customer page: badge on the default shipping address">
                            Shipping
                          </fbt>
                        </Badge>
                      ) : (
                        <Button
                          variant="tertiary"
                          size="sm"
                          onClick={() =>
                            router.post(
                              address.defaultUrl,
                              { use: "shipping" },
                              { preserveScroll: true },
                            )
                          }
                        >
                          <fbt desc="Customer page: button that makes an address the default shipping address">
                            Ship here by default
                          </fbt>
                        </Button>
                      )}
                      {!address.defaultBilling && (
                        <Button
                          variant="tertiary"
                          size="sm"
                          onClick={() =>
                            router.post(
                              address.defaultUrl,
                              { use: "billing" },
                              { preserveScroll: true },
                            )
                          }
                        >
                          <fbt desc="Customer page: button that makes an address the default billing address">
                            Bill here by default
                          </fbt>
                        </Button>
                      )}
                      <IconButton
                        variant="tertiary"
                        size="sm"
                        aria-label={fbs(
                          "Delete address",
                          "Customer page: accessible name of the button that deletes an address",
                        )}
                        onClick={() =>
                          addressRemoval.confirm({
                            url: address.destroyUrl,
                            title: fbs(
                              "Delete address",
                              "Customer page: title of the dialog that confirms deleting an address",
                            ),
                            message: fbs(
                              [
                                "Delete the address in ",
                                fbs.param("city", address.city),
                                "? Past orders keep the address they were placed with.",
                              ],
                              "Customer page: asks to confirm deleting an address, e.g. 'Delete the address in Berlin? …'",
                            ),
                            confirmButton: fbs(
                              "Delete",
                              "Customer page: button in the dialog that deletes an address",
                            ),
                          })
                        }
                      >
                        <Icon icon={DeleteBinLine} />
                      </IconButton>
                    </li>
                  ))}
                </ul>
              ) : (
                <Section.Card>
                  <div className="flex justify-center py-8">
                    <EmptyState
                      icon={MapPinLine}
                      title={fbs(
                        "No addresses yet",
                        "Customer page: shown in the addresses section while the customer has none",
                      )}
                      description={fbs(
                        "Add the addresses the customer gets orders and invoices at. The first one becomes the default for both.",
                        "Customer page: explains the empty addresses section",
                      )}
                    >
                      <Button variant="primary" onClick={() => setAddingAddress(true)}>
                        <fbt desc="Customer page: button in the empty addresses section that opens the dialog to add an address">
                          Add address
                        </fbt>
                      </Button>
                    </EmptyState>
                  </div>
                </Section.Card>
              )}
            </Stack>
          </Section.Root>

          {contacts && addContactUrl && (
            <Section.Root>
              <Section.Header
                title={fbs(
                  "Contacts",
                  "Customer page: heading of the section with the company's contacts",
                )}
                description={fbs(
                  "People who act for the company. They need no login.",
                  "Customer page: explains contacts",
                )}
                action={
                  contacts.length > 0 && (
                    <Button variant="secondary" onClick={() => setAddingContact(true)}>
                      <fbt desc="Customer page: button that opens the dialog to add a contact">
                        Add contact
                      </fbt>
                    </Button>
                  )
                }
              />
              <Stack spacing={4}>
                {contacts.length > 0 ? (
                  <ul className="border-base-subtle divide-base-subtle divide-y border">
                    {contacts.map((contact) => (
                      <li key={contact.id} className="flex items-center gap-3 py-2 pr-2 pl-4">
                        <span className="type-body-md flex-1">
                          {contact.name}
                          {contact.jobTitle && (
                            <span className="text-base-secondary"> · {contact.jobTitle}</span>
                          )}
                        </span>
                        <IconButton
                          variant="tertiary"
                          size="sm"
                          aria-label={fbs(
                            ["Remove ", fbs.param("contactName", contact.name)],
                            "Customer page: accessible name of the button that removes a contact",
                          )}
                          onClick={() =>
                            contactRemoval.confirm({
                              url: contact.destroyUrl,
                              title: fbs(
                                "Remove contact",
                                "Customer page: title of the dialog that confirms removing a contact",
                              ),
                              message: fbs(
                                [
                                  fbs.param("contactName", contact.name),
                                  " no longer acts for the company. Accounts linked to them stay linked to the customer.",
                                ],
                                "Customer page: asks to confirm removing a contact, e.g. 'Max Mustermann no longer acts for the company. …'",
                              ),
                              confirmButton: fbs(
                                "Remove",
                                "Customer page: button in the dialog that removes the contact",
                              ),
                            })
                          }
                        >
                          <Icon icon={DeleteBinLine} />
                        </IconButton>
                      </li>
                    ))}
                  </ul>
                ) : (
                  <Section.Card>
                    <div className="flex justify-center py-8">
                      <EmptyState
                        icon={UserLine}
                        title={fbs(
                          "No contacts yet",
                          "Customer page: shown in the contacts section while the company has none",
                        )}
                        description={fbs(
                          "Add the people you deal with at this company, such as buyers or accounting.",
                          "Customer page: explains the empty contacts section",
                        )}
                      >
                        <Button variant="primary" onClick={() => setAddingContact(true)}>
                          <fbt desc="Customer page: button in the empty contacts section that opens the dialog to add a contact">
                            Add contact
                          </fbt>
                        </Button>
                      </EmptyState>
                    </div>
                  </Section.Card>
                )}
              </Stack>
            </Section.Root>
          )}

          <Section.Root>
            <Section.Header
              title={fbs(
                "Accounts",
                "Customer page: heading of the section with the users who act for the customer",
              )}
              description={fbs(
                "Users of the shop who act for this customer. They are linked when they sign up or sign in.",
                "Customer page: explains linked accounts",
              )}
            />
            {accounts.length > 0 ? (
              <ul className="border-base-subtle divide-base-subtle divide-y border">
                {accounts.map((account) => (
                  <li key={account.id} className="flex items-center gap-3 py-2 pr-2 pl-4">
                    <span className="type-body-md flex-1">
                      {account.label}
                      {account.contact && (
                        <span className="text-base-secondary"> · {account.contact}</span>
                      )}
                    </span>
                    <Button
                      variant="tertiary"
                      size="sm"
                      onClick={() =>
                        accountUnlinking.confirm({
                          url: account.destroyUrl,
                          title: fbs(
                            "Unlink account",
                            "Customer page: title of the dialog that confirms unlinking a user from the customer",
                          ),
                          message: fbs(
                            [
                              fbs.param("account", account.label),
                              " can no longer act for this customer. Their user account stays.",
                            ],
                            "Customer page: asks to confirm unlinking a user, e.g. 'max@muster.de can no longer act for this customer. …'",
                          ),
                          confirmButton: fbs(
                            "Unlink",
                            "Customer page: button in the dialog that unlinks the user from the customer",
                          ),
                        })
                      }
                    >
                      <fbt desc="Customer page: button that unlinks a user from the customer">
                        Unlink
                      </fbt>
                    </Button>
                  </li>
                ))}
              </ul>
            ) : (
              <Section.Card>
                <div className="flex justify-center py-8">
                  <EmptyState
                    icon={LinksLine}
                    title={fbs(
                      "No accounts linked yet",
                      "Customer page: shown while no user acts for the customer",
                    )}
                    description={fbs(
                      "Accounts appear here once a user of the shop signs up or signs in for this customer.",
                      "Customer page: explains the empty accounts section; accounts can't be added here",
                    )}
                  />
                </div>
              </Section.Card>
            )}
          </Section.Root>

          <Section.Root>
            <Section.Header
              title={fbs(
                "Groups",
                "Customer page: heading of the section with the customer's groups",
              )}
              description={fbs(
                "Commercial groups such as Retail or Wholesale. Prices, discounts and catalogs can depend on them.",
                "Customer page: explains the groups section",
              )}
              action={
                groups.length > 0 &&
                availableGroups.length > 0 && (
                  <Button variant="secondary" onClick={() => setAddingGroup(true)}>
                    <fbt desc="Customer page: button that opens the dialog to add the customer to a group">
                      Add to group
                    </fbt>
                  </Button>
                )
              }
            />
            <Stack spacing={4}>
              {groups.length > 0 ? (
                <ul className="border-base-subtle divide-base-subtle divide-y border">
                  {groups.map((group) => (
                    <li key={group.id} className="flex items-center gap-3 py-2 pr-2 pl-4">
                      <span className="type-body-md flex-1">{group.name}</span>
                      <IconButton
                        variant="tertiary"
                        size="sm"
                        aria-label={fbs(
                          ["Remove from ", fbs.param("groupName", group.name)],
                          "Customer page: accessible name of the button that takes the customer out of a group",
                        )}
                        onClick={() =>
                          groupRemoval.confirm({
                            url: group.destroyUrl,
                            title: fbs(
                              "Remove from group",
                              "Customer page: title of the dialog that confirms taking the customer out of a group",
                            ),
                            message: fbs(
                              [
                                "The customer leaves ",
                                fbs.param("groupName", group.name),
                                ". Rules for that group no longer apply to them.",
                              ],
                              "Customer page: asks to confirm taking the customer out of a group, e.g. 'The customer leaves Wholesale. …'",
                            ),
                            confirmButton: fbs(
                              "Remove",
                              "Customer page: button in the dialog that takes the customer out of the group",
                            ),
                          })
                        }
                      >
                        <Icon icon={DeleteBinLine} />
                      </IconButton>
                    </li>
                  ))}
                </ul>
              ) : (
                // Without any groups in the shop, the card below says so instead.
                availableGroups.length > 0 && (
                  <Section.Card>
                    <div className="flex justify-center py-8">
                      <EmptyState
                        icon={GroupLine}
                        title={fbs(
                          "Not in any group yet",
                          "Customer page: shown in the groups section while the customer is in no group",
                        )}
                        description={fbs(
                          "Add the customer to a group to give them its prices, discounts and catalogs.",
                          "Customer page: explains the empty groups section",
                        )}
                      >
                        <Button variant="primary" onClick={() => setAddingGroup(true)}>
                          <fbt desc="Customer page: button in the empty groups section that opens the dialog to add the customer to a group">
                            Add to group
                          </fbt>
                        </Button>
                      </EmptyState>
                    </div>
                  </Section.Card>
                )
              )}
              {availableGroups.length === 0 && (
                <Section.Card>
                  <div className="flex justify-center py-8">
                    <EmptyState
                      icon={GroupLine}
                      title={fbs(
                        "No customer groups yet",
                        "Customer page: shown in the groups section while the shop has no customer groups",
                      )}
                      description={fbs(
                        "Create a group such as Retail or Wholesale first, then add the customer to it.",
                        "Customer page: explains that customer groups must be created before customers can join one",
                      )}
                    >
                      <Button render={<Link href={createGroupUrl} />}>
                        <fbt desc="Customer page: link in the groups section to the page that creates a customer group">
                          Create customer group
                        </fbt>
                      </Button>
                    </EmptyState>
                  </div>
                </Section.Card>
              )}
            </Stack>
          </Section.Root>
        </SectionStack>
      </Page.Body>
      <AddDialog
        open={addingAddress}
        onOpenChange={setAddingAddress}
        title={fbs("Add address", "Customer page: title of the dialog that adds an address")}
        description={fbs(
          "Where the customer receives orders or invoices.",
          "Customer page: description of the dialog that adds an address",
        )}
        action={addAddressUrl}
        submitLabel={fbs(
          "Add address",
          "Customer page: button in the dialog that adds the address",
        )}
      >
        {(errors) => (
          <>
            <div className="grid grid-cols-2 items-start gap-4">
              <Field
                label={fbs("First name", "Customer page: address first name")}
                name="first_name"
                error={errors.first_name}
              >
                <Input autoComplete="off" />
              </Field>
              <Field
                label={fbs("Last name", "Customer page: address last name")}
                name="last_name"
                error={errors.last_name}
              >
                <Input autoComplete="off" />
              </Field>
            </div>
            <Field
              label={fbs("Street", "Customer page: address street")}
              name="street[0]"
              error={errors.street ?? errors["street.0"]}
            >
              <Input autoComplete="off" />
            </Field>
            <Field
              label={fbs("Company", "Customer page: address company")}
              name="company"
              error={errors.company}
            >
              <Input autoComplete="off" />
            </Field>
            <div className="grid grid-cols-2 items-start gap-4">
              <Field
                label={fbs("Postcode", "Customer page: address postcode")}
                name="postcode"
                error={errors.postcode}
              >
                <Input autoComplete="off" />
              </Field>
              <Field
                label={fbs("City", "Customer page: address city")}
                name="city"
                error={errors.city}
              >
                <Input autoComplete="off" />
              </Field>
            </div>
            <CountrySelect countries={countries} error={errors.country_code} />
          </>
        )}
      </AddDialog>
      {addContactUrl && (
        <AddDialog
          open={addingContact}
          onOpenChange={setAddingContact}
          title={fbs("Add contact", "Customer page: title of the dialog that adds a contact")}
          description={fbs(
            "A person who acts for the company. They need no login.",
            "Customer page: description of the dialog that adds a contact",
          )}
          action={addContactUrl}
          submitLabel={fbs(
            "Add contact",
            "Customer page: button in the dialog that adds the contact",
          )}
        >
          {(errors) => (
            <>
              <div className="grid grid-cols-2 items-start gap-4">
                <Field
                  label={fbs("First name", "Customer page: contact first name")}
                  name="first_name"
                  error={errors.first_name}
                >
                  <Input autoComplete="off" />
                </Field>
                <Field
                  label={fbs("Last name", "Customer page: contact last name")}
                  name="last_name"
                  error={errors.last_name}
                >
                  <Input autoComplete="off" />
                </Field>
              </div>
              <Field
                label={fbs("Job title", "Customer page: contact job title")}
                name="job_title"
                error={errors.job_title}
              >
                <Input autoComplete="off" />
              </Field>
            </>
          )}
        </AddDialog>
      )}
      <AddDialog
        open={addingGroup}
        onOpenChange={setAddingGroup}
        title={fbs(
          "Add to group",
          "Customer page: title of the dialog that adds the customer to a group",
        )}
        description={fbs(
          "Groups decide which prices, discounts and catalogs apply to the customer.",
          "Customer page: description of the dialog that adds the customer to a group",
        )}
        action={addGroupUrl}
        submitLabel={fbs(
          "Add to group",
          "Customer page: button in the dialog that adds the customer to the group",
        )}
      >
        {(errors) => (
          <>
            <Field
              label={fbs("Group", "Customer page: select of the group to add the customer to")}
              name="group"
              error={errors.group}
            >
              <Select
                items={availableGroups}
                itemToKey={(option) => String(option.value)}
                itemToLabel={(option) => option.label}
                defaultValue={null}
                placeholder={fbs(
                  "Choose a group",
                  "Customer page: shown in the group select until one is picked",
                )}
                filter={{
                  placeholder: fbs(
                    "Search groups",
                    "Customer page: placeholder of the search field in the group select",
                  ),
                  empty: fbs(
                    "No group matches",
                    "Customer page: shown in the group select when the search matches none",
                  ),
                }}
              />
            </Field>
          </>
        )}
      </AddDialog>
      {addressRemoval.dialog}
      {groupRemoval.dialog}
      {contactRemoval.dialog}
      {accountUnlinking.dialog}
    </>
  );
}

Edit.layout = [SidebarLayout];

type StatusAction = {
  to: "active" | "blocked" | "archived";
  label: string;
  /** Shown in red in the menu. */
  destructive?: boolean;
  /** Asked before the change; the actions that only undo one need no confirmation. */
  confirm?: { title: string; message: string };
};

/**
 * The customer's status, and a menu to change it with the same actions as the customers table:
 * Block and Deactivate for active customers, Unblock and Deactivate for blocked ones, Reactivate
 * for deactivated ones.
 */
function StatusMenu({ status, statusUrl }: { status: string; statusUrl: string }) {
  const [confirming, setConfirming] = useState<StatusAction | null>(null);

  const block: StatusAction = {
    to: "blocked",
    label: fbs("Block", "Customer page: status menu item that blocks the customer"),
    destructive: true,
    confirm: {
      title: fbs(
        "Block customer",
        "Customer page: title of the dialog that confirms blocking the customer",
      ),
      message: fbs(
        "The customer keeps their history but can't get new accounts until unblocked.",
        "Customer page: explains what blocking the customer does",
      ),
    },
  };
  const deactivate: StatusAction = {
    to: "archived",
    label: fbs("Deactivate", "Customer page: status menu item that deactivates the customer"),
    confirm: {
      title: fbs(
        "Deactivate customer",
        "Customer page: title of the dialog that confirms deactivating the customer",
      ),
      message: fbs(
        "The customer is archived. Their history stays, and you can reactivate them later.",
        "Customer page: explains what deactivating the customer does",
      ),
    },
  };
  const unblock: StatusAction = {
    to: "active",
    label: fbs("Unblock", "Customer page: status menu item that unblocks the customer"),
  };
  const reactivate: StatusAction = {
    to: "active",
    label: fbs(
      "Reactivate",
      "Customer page: status menu item that reactivates a deactivated customer",
    ),
  };
  const actions =
    status === "blocked"
      ? [unblock, deactivate]
      : status === "archived"
        ? [reactivate]
        : [block, deactivate];
  const label =
    status === "blocked"
      ? fbs("Blocked", "Customer page: status menu button of a blocked customer")
      : status === "archived"
        ? fbs("Deactivated", "Customer page: status menu button of a deactivated customer")
        : fbs("Active", "Customer page: status menu button of an active customer");

  // Actions that only undo a change need no confirmation.
  const run = (action: StatusAction) =>
    router.post(statusUrl, { status: action.to }, { preserveScroll: true });

  return (
    <>
      <Menu.Root>
        <Menu.Trigger
          render={
            <IconButton
              variant="tertiary"
              size="sm"
              aria-label={fbs(
                ["Status: ", fbs.param("status", label)],
                "Customer page: accessible name of the button that opens the menu to change the customer's status, e.g. 'Status: Active'",
              )}
            >
              <Icon icon={More2Fill} />
            </IconButton>
          }
        />
        <Menu.Popup>
          {actions.map((action) => (
            <Menu.Item
              key={action.label}
              variant={action.destructive ? "destructive" : "default"}
              onClick={() => {
                if (!action.confirm) return run(action);
                setConfirming(action);
              }}
            >
              {action.label}
            </Menu.Item>
          ))}
        </Menu.Popup>
      </Menu.Root>
      <ConfirmDialog.Root
        open={confirming !== null}
        onOpenChange={(open) => !open && setConfirming(null)}
      >
        <Freeze frozen={confirming === null}>
          {confirming?.confirm && (
            <ConfirmDialog.Popup
              title={confirming.confirm.title}
              description={confirming.confirm.message}
              action={`${statusUrl}?status=${confirming.to}`}
              method="post"
              confirmButton={confirming.label}
            />
          )}
        </Freeze>
      </ConfirmDialog.Root>
    </>
  );
}
