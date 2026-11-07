import { router } from "@inertiajs/react";
import {
  Field,
  Input,
  Pagination,
  Section,
  Select,
  Stack,
  Table,
  Button,
  EmptyState,
  Icon,
  Tooltip,
  TruncateMiddle,
} from "@larasell-dev/ui";
import { ArrowDownSLine, ArrowGoBackLine, DeleteBinLine, FilterLine } from "@larasell-dev/ui/icons";
import { HotkeysProvider } from "@tanstack/react-hotkeys";
import { fbs } from "fbtee";
import { useMemo, useState, type ReactNode } from "react";
import type { SectionComponentProps } from "../../extensions";
import type { TableRowAction } from "../../types";
import ConfirmActionDialog from "../../Platform/Panel/Components/ConfirmActionDialog";
import { overlayExclusive } from "../../Platform/Support/hotkeys";
import { useStorage } from "../../Platform/Support/useStorage";

type Props = Larasell.Admin.Seo.Data.SeoUrlsData;

/**
 * The IDs of the channels the admin chose not to see. The hidden ones are kept rather than the
 * shown ones, so a channel added later shows.
 */
const HIDDEN_CHANNELS_STORAGE_KEY = "larasell-admin-seo-hidden-channels";

/**
 * The header and URL fields of a product's or category's URLs section, one per storefront channel
 * that sells in the content locale, or `empty` while there is none. An empty field makes the URL
 * from the name, which then follows renames; a typed path stays. Below each field, the channel's
 * old URLs, which redirect to it, each with a menu to remove it or make it current again.
 *
 * With several channels, the header picks which ones show, kept in this browser; with none picked,
 * the section says so.
 * Hidden channels keep their fields in the form, so saving never drops their custom URLs.
 */
export default function UrlFields({
  props,
  name,
  error,
  title,
  description,
  empty,
}: SectionComponentProps<Props> & { title: string; description: string; empty: ReactNode }) {
  const [stored, setHidden] = useStorage<string[]>(HIDDEN_CHANNELS_STORAGE_KEY, []);
  // Whatever another version of the panel stored there, only a list of IDs is used.
  const hidden = Array.isArray(stored) ? stored.map(String) : [];
  const ids = props.urls.map((url) => String(url.channelId));
  // Channels added since the choice was made show, so a new channel isn't silently hidden.
  const shown = ids.filter((id) => !hidden.includes(id));
  const visible = (id: number) => shown.includes(String(id));

  const choose = (value: string[]) => {
    const next = ids.filter((id) => !value.includes(id));
    setHidden(next);
  };

  return (
    <>
      <Section.Header
        title={title}
        description={description}
        action={
          props.urls.length > 1 && (
            <ChannelFilter channels={props.urls} shown={shown} onChange={choose} />
          )
        }
      />
      {props.urls.length > 0 ? (
        <Stack spacing={5}>
          {shown.length === 0 && (
            <Section.Card>
              <div className="flex justify-center px-(--inset) py-12">
                <EmptyState
                  icon={FilterLine}
                  title={fbs(
                    "No sales channels selected",
                    "Product and category forms, URLs section: heading shown when the admin unchecked every sales channel in the filter",
                  )}
                  description={fbs(
                    "Pick sales channels in the filter to see their URLs.",
                    "Product and category forms, URLs section: text below 'No sales channels selected'",
                  )}
                >
                  <Button type="button" variant="secondary" onClick={() => choose(ids)}>
                    <fbt desc="Product and category forms, URLs section: button in the empty state that shows every sales channel's URLs again">
                      Show all sales channels
                    </fbt>
                  </Button>
                </EmptyState>
              </div>
            </Section.Card>
          )}
          {props.urls.map((url) => (
            <div key={url.channelId} hidden={!visible(url.channelId)}>
              <Stack spacing={3}>
                <Field
                  label={fbs(
                    ["URL in ", fbs.param("channel", url.channel)],
                    "Product and category forms, URLs section: label of the field with the current address in a sales channel, e.g. 'URL in Europe'",
                  )}
                  name={name(`urls.${url.channelId}.path`)}
                  error={error(`urls.${url.channelId}.path`)}
                >
                  <Input
                    prefix={
                      // The domain is cut in the middle, so the language at its end
                      // still tells a channel's domains apart; hover it for all of it.
                      <Tooltip.Truncated>
                        <Input.Text>
                          <TruncateMiddle>{url.baseUrl.replace(/^https?:\/\//, "")}</TruncateMiddle>
                        </Input.Text>
                      </Tooltip.Truncated>
                    }
                    // Remounted when the saved path changes, e.g. after making an old URL current,
                    // since the field keeps what it was given first.
                    key={url.customPath ?? ""}
                    defaultValue={url.customPath ?? ""}
                    placeholder={
                      url.automaticPath ??
                      fbs(
                        "Made from the name",
                        "Product and category forms: placeholder of a URL field while there is no name in the content language yet",
                      )
                    }
                    autoComplete="off"
                    spellCheck={false}
                  />
                </Field>
                {url.history.total > 0 && <OldUrls url={url} />}
              </Stack>
            </div>
          ))}
        </Stack>
      ) : (
        <p className="type-body-md text-base-secondary">{empty}</p>
      )}
    </>
  );
}

/** The page sizes the server takes, the first by default. */
const PAGE_SIZES = [10, 25, 50, 100];

/**
 * This page's address with the channel's old URLs on the given page and page size, in
 * old-urls[{channel ID}][page] and [size]; the other channels' and other query parameters stay.
 */
function withPage(channelId: number, page: number, pageSize: number): string {
  const next = new URL(window.location.href);
  const set = (key: string, value: number, fallback: number) =>
    value === fallback
      ? next.searchParams.delete(`old-urls[${channelId}][${key}]`)
      : next.searchParams.set(`old-urls[${channelId}][${key}]`, String(value));
  set("page", page, 1);
  set("size", pageSize, PAGE_SIZES[0]);
  return next.pathname + next.search + next.hash;
}

/**
 * One page of the channel's old URLs, newest first, each redirecting to the current one. More
 * than a page's worth adds pagination, which the server does.
 */
function OldUrls({ url }: { url: Larasell.Admin.Seo.Data.SeoUrlData }) {
  // Keeps the scroll position and what the admin typed elsewhere on the page.
  const options = { preserveScroll: true, preserveState: true } as const;
  const [removing, setRemoving] = useState<TableRowAction | null>(null);
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState<string>();

  // The shared confirmation dialog of table actions; the server checks the typed text.
  const askToRemove = (old: Larasell.Admin.Seo.Data.OldUrlData) => {
    setError(undefined);
    setRemoving({
      name: "remove",
      label: "",
      method: "post",
      url: old.removeUrl,
      destructive: true,
      disabled: false,
      icon: null,
      confirm: {
        title: fbs(
          "Delete this redirect?",
          "Product and category forms, URLs section: title of the dialog that confirms removing an old URL",
        ),
        message: fbs(
          [
            "/",
            fbs.param("path", old.path),
            " will no longer lead to this page, and another page can use it.",
          ],
          "Product and category forms, URLs section: text of the dialog that confirms removing an old URL, naming its path",
        ),
        confirmButton: fbs(
          "Delete redirect",
          "Product and category forms, URLs section: button in the dialog that removes an old URL",
        ),
        cancelButton: fbs(
          "Cancel",
          "Product and category forms, URLs section: button that closes the dialog without removing the old URL",
        ),
        // Must match the server's __('Delete redirect'), which is in the same interface language.
        confirmationText: fbs(
          "Delete redirect",
          "Product and category forms, URLs section: text the admin types to confirm removing an old URL",
        ),
      },
    });
  };

  const remove = (confirmation: string) =>
    removing &&
    router.delete(removing.url, {
      ...options,
      data: { confirmation },
      onStart: () => setProcessing(true),
      onFinish: () => setProcessing(false),
      onSuccess: () => setRemoving(null),
      onError: (errors) => setError(errors.confirmation),
    });

  return (
    <Section.Card>
      <Table.Root
        aria-label={fbs(
          ["Old URLs in ", fbs.param("channel", url.channel)],
          "Product and category forms, URLs section: name of the table of a channel's earlier URLs",
        )}
      >
        <Table.Content>
          <Table.Header>
            <Table.Column>
              <fbt desc="Product and category forms, URLs section: heading of the column with earlier URLs that now redirect to the current one">
                Redirects to the current URL
              </fbt>
            </Table.Column>
            <Table.Column type="action">
              <fbt desc="Product and category forms, URLs section: heading of the column with each old URL's menu of actions">
                Actions
              </fbt>
            </Table.Column>
          </Table.Header>
          <Table.Body>
            {url.history.urls.map((old) => (
              <Table.Row key={old.id}>
                <Table.Cell>
                  {/* Cut in the middle, so the end that tells similar paths apart stays; hover it for all
                      of it. */}
                  <Tooltip.Truncated>
                    <TruncateMiddle>{`/${old.path}`}</TruncateMiddle>
                  </Tooltip.Truncated>
                </Table.Cell>
                <Table.Cell>
                  <Table.Actions>
                    <Table.ActionsTrigger
                      // Moved into the cell's right padding, so it sits as far from the right edge as from the top and
                      // bottom. A negative margin doesn't move it: the action column keeps its width.
                      DANGEROUS_className="left-5"
                      aria-label={fbs(
                        ["Actions for /", fbs.param("path", old.path)],
                        "Product and category forms, URLs section: label of the button that opens an old URL's menu",
                      )}
                    />
                    <Table.ActionsPopup>
                      <HotkeysProvider scope="old-url-actions-menu" exclusive={overlayExclusive}>
                        <Table.ActionsItem
                          icon={ArrowGoBackLine}
                          onClick={() => router.post(old.restoreUrl, {}, options)}
                        >
                          <fbt desc="Product and category forms, URLs section: menu item that makes an old URL the current one again">
                            Make current URL
                          </fbt>
                        </Table.ActionsItem>
                        <Table.ActionsItem
                          variant="destructive"
                          icon={DeleteBinLine}
                          onClick={() => askToRemove(old)}
                        >
                          <fbt desc="Product and category forms, URLs section: menu item that removes an old URL, so it stops redirecting">
                            Remove redirect
                          </fbt>
                        </Table.ActionsItem>
                      </HotkeysProvider>
                    </Table.ActionsPopup>
                  </Table.Actions>
                </Table.Cell>
              </Table.Row>
            ))}
          </Table.Body>
        </Table.Content>
      </Table.Root>
      {/* Shown while there's more than the smallest page, so a bigger size can be undone. */}
      {url.history.total > PAGE_SIZES[0] && (
        <div className="border-t border-base-subtle px-(--inset) py-3">
          <Pagination
            page={url.history.page}
            pageSize={url.history.pageSize}
            total={url.history.total}
            pageSizeOptions={PAGE_SIZES}
            onPaginationChange={({ page, pageSize }) =>
              router.visit(withPage(url.channelId, page, pageSize), options)
            }
          />
        </div>
      )}
      <ConfirmActionDialog
        action={removing}
        processing={processing}
        error={error}
        onConfirm={remove}
        onCancel={() => setRemoving(null)}
      />
    </Section.Card>
  );
}

/**
 * A button naming how many channels show, which opens a searchable list of every channel with a
 * checkbox each.
 */
function ChannelFilter({
  channels,
  shown,
  onChange,
}: {
  channels: Larasell.Admin.Seo.Data.SeoUrlData[];
  shown: string[];
  onChange: (value: string[]) => void;
}) {
  const label = useMemo(() => {
    if (shown.length === 0) {
      return fbs(
        "No sales channels",
        "Product and category forms, URLs section: button that picks which sales channels' URLs show, while none show",
      );
    }

    if (shown.length === channels.length) {
      return fbs(
        "All sales channels",
        "Product and category forms, URLs section: button that picks which sales channels' URLs show, while all of them show",
      );
    }

    return fbs(
      [
        fbs.plural("sales channel", shown.length, {
          many: "sales channels",
          showCount: "yes",
          name: "count",
        }),
      ],
      "Product and category forms, URLs section: button that picks which sales channels' URLs show, naming how many show, e.g. '3 sales channels'",
    );
  }, [shown.length, channels.length]);

  return (
    <Select.Root
      multiple
      items={channels}
      itemToKey={(url) => String(url.channelId)}
      itemToLabel={(url) => url.channel}
      value={shown}
      onValueChange={onChange}
      filter={{
        placeholder: fbs(
          "Search sales channels",
          "Product and category forms, URLs section: placeholder of the search field in the list of sales channels to show",
        ),
        empty: fbs(
          "No sales channel matches.",
          "Product and category forms, URLs section: shown in the list of sales channels when the search matches none",
        ),
      }}
    >
      <Select.Trigger render={<Button type="button" variant="secondary" />}>
        {label}
        <Icon icon={ArrowDownSLine} />
      </Select.Trigger>
      <Select.Popup align="end" />
    </Select.Root>
  );
}
