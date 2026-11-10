import { Field as BaseField } from "@base-ui/react/field";
import { Form, router } from "@inertiajs/react";
import {
  Badge,
  Button,
  Checkbox,
  Dialog,
  Field,
  Icon,
  IconButton,
  Input,
  Pagination,
  Section,
  Select,
  Stack,
  Table,
  Tooltip,
  TruncateMiddle,
} from "@larasell-dev/ui";
import { AddLine, DeleteBinLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import { useState } from "react";
import FormCheckbox from "../../Platform/Panel/Components/FormCheckbox";
import ConfirmDialog from "../../Platform/Panel/Components/ConfirmDialog";
import Freeze from "../../Platform/Panel/Components/Freeze";

type Channel = Larasell.Admin.SalesChannel.Data.SalesChannelData;
type EntryPoint = Larasell.Admin.SalesChannel.Data.EntryPointData;
type Option = Larasell.Admin.SalesChannel.Data.SalesChannelOptionData;

/** An option's name, e.g. "Swiss Franc" for CHF, or the value itself when it isn't offered. */
const label = (options: Option[], value: string) =>
  options.find((option) => option.value === value)?.label ?? value;

/** The page sizes the server takes, the first by default. */
const PAGE_SIZES = [10, 25, 50, 100];

/**
 * This page's address with the domains on the given page and page size, in domains[page] and
 * [size]; other query parameters stay.
 */
function withPage(page: number, pageSize: number): string {
  const next = new URL(window.location.href);
  const set = (key: string, value: number, fallback: number) =>
    value === fallback
      ? next.searchParams.delete(`domains[${key}]`)
      : next.searchParams.set(`domains[${key}]`, String(value));
  set("page", page, 1);
  set("size", pageSize, PAGE_SIZES[0]);
  return next.pathname + next.search + next.hash;
}

/**
 * The section with a storefront's domains: the URLs customers reach it through, each in one of its
 * languages; customers switch between the channel's currencies on any of them. Only the saved
 * languages and currencies are offered, since the server checks against those. The server pages
 * the domains by domains[page] and [size] in the query.
 */
export default function EntryPoints({
  channel,
  entryPoints,
  addUrl,
  locales,
  currencies,
}: {
  channel: Channel;
  entryPoints: Larasell.Admin.SalesChannel.Data.EntryPointsData;
  addUrl: string;
  locales: Option[];
  currencies: Option[];
}) {
  // The domain the delete dialog asks about, or null while it is closed.
  const [deleting, setDeleting] = useState<EntryPoint | null>(null);

  return (
    <Section.Root>
      <Section.Header
        title={fbs(
          "Domains",
          "Sales channel page: heading of the section with the URLs a storefront is reached through",
        )}
        description={fbs(
          "The URLs customers open the storefront at, one language each. Customers can switch between the channel's currencies on any of them. Links in a language use its primary domain.",
          "Sales channel page: explains the domains section; a primary domain is the one links in its language point to",
        )}
      />
      <Stack spacing={4}>
        {entryPoints.total > 0 ? (
          <Section.Card>
            <Table.Root
              aria-label={fbs(
                "Domains",
                "Sales channel page: name of the table of the URLs a storefront is reached through",
              )}
            >
              <Table.Content>
                <Table.Header>
                  <Table.Column>
                    <fbt desc="Sales channel page: heading of the domains table's column with each domain's URL">
                      URL
                    </fbt>
                  </Table.Column>
                  <Table.Column>
                    <fbt desc="Sales channel page: heading of the domains table's column with the language each domain shows">
                      Language
                    </fbt>
                  </Table.Column>
                  <Table.Column>
                    <fbt desc="Sales channel page: heading of the domains table's column with the currency visits through a domain start in">
                      Starting currency
                    </fbt>
                  </Table.Column>
                  <Table.Column type="action">
                    <fbt desc="Sales channel page: heading of the domains table's column with each domain's delete button">
                      Actions
                    </fbt>
                  </Table.Column>
                </Table.Header>
                <Table.Body>
                  {entryPoints.entryPoints.map((entryPoint) => (
                    <Table.Row key={entryPoint.id}>
                      <Table.Cell>
                        <div className="flex min-w-0 flex-col items-start gap-1">
                          {/* Without its scheme and cut in the middle, so the language path at its end
                              stays; hover it for all of it. */}
                          <Tooltip.Truncated>
                            <TruncateMiddle>{entryPoint.domain}</TruncateMiddle>
                          </Tooltip.Truncated>
                          {entryPoint.primary && (
                            <Badge>
                              <fbt desc="Sales channel page: badge on the domain that links in its language and currency use">
                                Primary
                              </fbt>
                            </Badge>
                          )}
                        </div>
                      </Table.Cell>
                      <Table.Cell>
                        <Tooltip.Truncated>
                          <span className="block truncate">
                            {label(locales, entryPoint.locale)}
                          </span>
                        </Tooltip.Truncated>
                      </Table.Cell>
                      <Table.Cell>
                        <Tooltip.Truncated>
                          <span className="block truncate">
                            {label(
                              currencies,
                              entryPoint.defaultCurrency ?? channel.defaultCurrency,
                            )}
                          </span>
                        </Tooltip.Truncated>
                        {!entryPoint.defaultCurrency && (
                          <span className="type-body-sm block text-base-secondary">
                            <fbt desc="Sales channel page: in the domains table, below the currency of a domain whose visits start in the channel's default currency">
                              (Channel default)
                            </fbt>
                          </span>
                        )}
                      </Table.Cell>
                      <Table.Cell>
                        <IconButton
                          variant="tertiary"
                          size="sm"
                          aria-label={fbs(
                            ["Delete ", fbs.param("url", entryPoint.url)],
                            "Sales channel page: accessible name of the button on a domain that deletes it, e.g. 'Delete https://shop.example/de'",
                          )}
                          onClick={() => setDeleting(entryPoint)}
                        >
                          <Icon icon={DeleteBinLine} />
                        </IconButton>
                      </Table.Cell>
                    </Table.Row>
                  ))}
                </Table.Body>
              </Table.Content>
            </Table.Root>
            {/* Shown while there's more than the smallest page, so a bigger size can be undone. */}
            {entryPoints.total > PAGE_SIZES[0] && (
              <div className="border-t border-base-subtle px-(--inset) py-3">
                <Pagination
                  page={entryPoints.page}
                  pageSize={entryPoints.pageSize}
                  total={entryPoints.total}
                  pageSizeOptions={PAGE_SIZES}
                  onPaginationChange={({ page, pageSize }) =>
                    router.visit(withPage(page, pageSize), {
                      preserveScroll: true,
                      preserveState: true,
                    })
                  }
                />
              </div>
            )}
          </Section.Card>
        ) : (
          <p className="type-body-md text-base-secondary">
            <fbt desc="Sales channel page: shown in the domains section while the storefront has none">
              The storefront has no domains, so customers can't reach it yet.
            </fbt>
          </p>
        )}
        <div>
          <AddEntryPoint
            channel={channel}
            addUrl={addUrl}
            locales={locales.filter((option) => channel.locales.includes(option.value))}
            currencies={currencies.filter((option) => channel.currencies.includes(option.value))}
          />
        </div>
      </Stack>
      {/* One dialog outside the list, so it outlives the row it deletes and can animate out. */}
      <ConfirmDialog.Root
        open={deleting !== null}
        onOpenChange={(open) => !open && setDeleting(null)}
      >
        {/* Keeps the deleted domain while the dialog animates out, so its text does not go blank. */}
        <Freeze frozen={deleting === null}>
          {deleting && (
            <ConfirmDialog.Popup
              title={fbs(
                "Delete domain",
                "Sales channel page: title of the dialog that asks to confirm deleting a domain",
              )}
              description={fbs(
                [
                  "Customers can no longer open the storefront at ",
                  fbs.param("url", deleting.url),
                  ".",
                ],
                "Sales channel page: explains what deleting a domain does, e.g. 'Customers can no longer open the storefront at https://shop.example/de.'",
              )}
              action={deleting.destroyUrl}
              // Without its scheme, e.g. "shop.example/de"; the server checks it too.
              confirmationText={deleting.domain}
            />
          )}
        </Freeze>
      </ConfirmDialog.Root>
    </Section.Root>
  );
}

function AddEntryPoint({
  channel,
  addUrl,
  locales,
  currencies,
}: {
  channel: Channel;
  addUrl: string;
  locales: Option[];
  currencies: Option[];
}) {
  return (
    <Dialog.Root>
      <Dialog.Trigger render={<Button variant="secondary" />}>
        <Icon icon={AddLine} />
        <fbt desc="Sales channel page: button below the domains that opens the dialog to add one">
          Add domain
        </fbt>
      </Dialog.Trigger>
      <Dialog.Popup
        title={fbs(
          "Add domain",
          "Sales channel page: title of the dialog that adds a URL the storefront is reached through",
        )}
        description={fbs(
          "Point the domain at this shop first; Larasell doesn't check its DNS or certificate.",
          "Add domain dialog: reminds the admin that the domain's DNS must already lead to the shop",
        )}
      >
        {({ close }) => (
          <Form action={addUrl} method="post" onSuccess={close} options={{ preserveScroll: true }}>
            {({ errors, processing }) => (
              <Stack spacing={5}>
                <Field
                  label={fbs(
                    "URL",
                    "Add domain dialog: label of the field for the URL, a host with an optional path",
                  )}
                  name="url"
                  error={errors.url}
                  description={fbs(
                    "A host, with a path for several languages on one host, e.g. https://shop.example/de.",
                    "Add domain dialog: hint below the URL field",
                  )}
                >
                  <Input
                    inputMode="url"
                    autoComplete="off"
                    spellCheck={false}
                    placeholder="https://"
                    autoFocus
                  />
                </Field>
                <div className="grid items-start gap-5">
                  <Field
                    label={fbs(
                      "Language",
                      "Add domain dialog: label of the select for the language the domain shows",
                    )}
                    name="locale"
                    error={errors.locale}
                  >
                    <Select
                      items={locales}
                      itemToKey={(option) => option.value}
                      itemToLabel={(option) => option.label}
                      defaultValue={channel.defaultLocale}
                      filter={{
                        placeholder: fbs(
                          "Search languages",
                          "Add domain dialog: placeholder of the search field in the list of languages",
                        ),
                        empty: fbs(
                          "No language matches",
                          "Add domain dialog: shown in the list of languages when the typed text matches none",
                        ),
                      }}
                    />
                  </Field>
                  <Field
                    label={fbs(
                      "Starting currency",
                      "Add domain dialog: label of the optional select for the currency visits through the domain start in; visitors can still switch",
                    )}
                    name="default_currency"
                    error={errors.default_currency}
                    description={fbs(
                      "Customers can switch to any of the channel's currencies.",
                      "Add domain dialog: hint below the starting currency select",
                    )}
                  >
                    <Select
                      items={[
                        {
                          value: "",
                          label: fbs(
                            [
                              "Channel default (",
                              fbs.param("currency", label(currencies, channel.defaultCurrency)),
                              ")",
                            ],
                            "Add domain dialog: first option of the starting currency select, which uses the channel's default currency, e.g. 'Channel default (Swiss Franc)'",
                          ),
                        },
                        ...currencies.map((option) => ({
                          value: option.value,
                          label:
                            option.label === option.value
                              ? option.label
                              : `${option.label} (${option.value})`,
                        })),
                      ]}
                      itemToKey={(option) => option.value}
                      itemToLabel={(option) => option.label}
                      defaultValue=""
                    />
                  </Field>
                </div>
                <BaseField.Root name="primary" className="relative flex flex-col gap-1.5">
                  <Checkbox.Label>
                    <FormCheckbox
                      name="primary"
                      value="1"
                      uncheckedValue="0"
                      defaultChecked={false}
                    />
                    <fbt desc="Add domain dialog: checkbox that makes links in the domain's language and currency use it; the first domain of them is primary anyway">
                      Make primary
                    </fbt>
                  </Checkbox.Label>
                </BaseField.Root>
                <Dialog.Actions>
                  <Dialog.Close>
                    <fbt desc="Add domain dialog: button that closes it without adding">Cancel</fbt>
                  </Dialog.Close>
                  <Button type="submit" variant="primary" loading={processing}>
                    <fbt desc="Add domain dialog: button that adds the domain">Add</fbt>
                  </Button>
                </Dialog.Actions>
              </Stack>
            )}
          </Form>
        )}
      </Dialog.Popup>
    </Dialog.Root>
  );
}
