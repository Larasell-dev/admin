import { Link, router } from "@inertiajs/react";
import type { PaginationChange } from "@larasell-dev/ui";
import { Badge, DataList, Pagination, Table } from "@larasell-dev/ui";
import { HotkeysProvider } from "@tanstack/react-hotkeys";
import { type ReactNode, useState } from "react";
import type { TableRowAction, TableColumn, TableResource, TableRow } from "../types";
import { overlayExclusive } from "../utils/hotkeys";
import { isSameSite } from "../utils/isSameSite";
import { navigationIcon } from "../utils/navigationIcon";
import ConfirmActionDialog from "./ConfirmActionDialog";

type Props = {
  resource: TableResource;
  "aria-label"?: string;
};

const cell = (row: TableRow, column: string) => row[column] as string | number | null;

/** What a column shows for the row: its text, in a badge for badge columns. */
function value(row: TableRow, column: TableColumn): ReactNode {
  const text = cell(row, column.name);

  if (column.type !== "badge") {
    return text;
  }

  // A badge without text would show as an empty pill.
  if (text === null) {
    return text;
  }

  return <Badge variant={row._variants[column.name]}>{text}</Badge>;
}

function visit(url: string) {
  if (isSameSite(url)) {
    router.visit(url);
  } else {
    window.location.assign(url);
  }
}

/** Renders a PHP-defined table: a data list on small screens, the UI table from `lg` up. */
export default function ResourceTable({ resource, "aria-label": ariaLabel }: Props) {
  const label = ariaLabel ?? resource.title;
  const [primary, ...rest] = resource.columns;
  const hasActions = resource.rows.some((row) => row._actions.length > 0);

  const [pending, setPending] = useState<TableRowAction | null>(null);
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState<string>();
  const [isPaginating, setIsPaginating] = useState(false);

  const pagination = {
    ...resource.pagination,
    pageSizeOptions: [25, 50, 100],
    disabled: isPaginating,
    onPaginationChange: ({ page, pageSize }: PaginationChange) =>
      router.get(
        window.location.pathname,
        { page, per_page: pageSize },
        {
          preserveScroll: true,
          onStart: () => setIsPaginating(true),
          onFinish: () => setIsPaginating(false),
        },
      ),
  };

  const perform = (action: TableRowAction, confirmation?: string) => {
    if (action.method === "get") {
      setPending(null);
      visit(action.url);
      return;
    }

    router.post(action.url, confirmation ? { confirmation } : {}, {
      preserveScroll: true,
      onStart: () => setProcessing(true),
      onFinish: () => setProcessing(false),
      onSuccess: () => setPending(null),
      onError: (errors) => setError(errors.confirmation),
    });
  };

  const choose = (action: TableRowAction) => {
    setError(undefined);

    if (action.confirm) {
      setPending(action);
    } else {
      perform(action);
    }
  };

  /** Links the row through its primary cell's value; the link stretches over the whole row. */
  const linked = (row: TableRow, RowLink: typeof Table.Link, value: ReactNode) =>
    row._link ? (
      <RowLink render={isSameSite(row._link) ? <Link href={row._link} /> : <a href={row._link} />}>
        {value}
      </RowLink>
    ) : (
      value
    );

  const actionsMenu = (row: TableRow) => (
    <Table.Actions>
      <Table.ActionsTrigger
        aria-label={`Actions for ${primary ? cell(row, primary.name) : row.id}`}
      />
      <Table.ActionsPopup>
        <HotkeysProvider scope="row-actions-menu" exclusive={overlayExclusive}>
          {row._actions.map((action) => (
            <Table.ActionsItem
              key={action.name}
              variant={action.destructive ? "destructive" : "default"}
              disabled={action.disabled}
              icon={action.icon ? navigationIcon(action.icon, false) : undefined}
              onClick={() => choose(action)}
            >
              {action.label}
            </Table.ActionsItem>
          ))}
        </HotkeysProvider>
      </Table.ActionsPopup>
    </Table.Actions>
  );

  return (
    <>
      <div className="flex min-h-0 flex-1 flex-col lg:hidden">
        <div className="min-h-0 flex-1 overflow-y-auto">
          <DataList.Root aria-label={label}>
            {resource.rows.map((row) => (
              <DataList.Item key={row.id}>
                <div className="flex items-start justify-between gap-2">
                  {primary && (
                    <DataList.Title>
                      {linked(row, DataList.Link, value(row, primary))}
                    </DataList.Title>
                  )}
                  {hasActions && actionsMenu(row)}
                </div>
                {rest.map((column) => (
                  <DataList.Field key={column.name} label={column.label}>
                    {value(row, column)}
                  </DataList.Field>
                ))}
              </DataList.Item>
            ))}
          </DataList.Root>
        </div>
        <div className="border-border shrink-0 border-t px-4 py-3">
          <Pagination {...pagination} />
        </div>
      </div>

      <div className="hidden min-h-0 flex-1 lg:block">
        <Table.Root aria-label={label}>
          <Table.Content>
            <Table.Header>
              {resource.columns.map((column) => (
                <Table.Column key={column.name}>{column.label}</Table.Column>
              ))}
              {hasActions && <Table.Column type="action">Actions</Table.Column>}
            </Table.Header>
            <Table.Body>
              {resource.rows.map((row) => (
                <Table.Row key={row.id}>
                  {resource.columns.map((column) => (
                    <Table.Cell key={column.name}>
                      {column === primary
                        ? linked(row, Table.Link, value(row, column))
                        : value(row, column)}
                    </Table.Cell>
                  ))}
                  {hasActions && <Table.Cell>{actionsMenu(row)}</Table.Cell>}
                </Table.Row>
              ))}
            </Table.Body>
          </Table.Content>
          <Table.Pagination {...pagination} />
        </Table.Root>
      </div>

      <ConfirmActionDialog
        action={pending}
        processing={processing}
        error={error}
        onConfirm={(typed) => pending && perform(pending, typed)}
        onCancel={() => setPending(null)}
      />
    </>
  );
}
