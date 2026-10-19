import { router } from "@inertiajs/react";
import { DataList, Table } from "@larasell-dev/ui";
import { useState } from "react";
import type { TableAction, TableResource, TableRow } from "../types";
import ConfirmActionDialog from "./ConfirmActionDialog";

type Props = {
  resource: TableResource;
  "aria-label"?: string;
};

const cell = (row: TableRow, column: string) => row[column] as string | number | null;

function visit(url: string) {
  if (new URL(url, window.location.href).origin === window.location.origin) {
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

  const [pending, setPending] = useState<TableAction | null>(null);
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState<string>();

  const perform = (action: TableAction, confirmation?: string) => {
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

  const choose = (action: TableAction) => {
    setError(undefined);

    if (action.confirm) {
      setPending(action);
    } else {
      perform(action);
    }
  };

  const actionsMenu = (row: TableRow) => (
    <Table.Actions>
      <Table.ActionsTrigger
        aria-label={`Actions for ${primary ? cell(row, primary.name) : row.id}`}
      />
      <Table.ActionsPopup>
        {row._actions.map((action) => (
          <Table.ActionsItem
            key={action.name}
            variant={action.destructive ? "destructive" : "default"}
            disabled={action.disabled}
            onClick={() => choose(action)}
          >
            {action.label}
          </Table.ActionsItem>
        ))}
      </Table.ActionsPopup>
    </Table.Actions>
  );

  return (
    <>
      <div className="min-h-0 flex-1 overflow-y-auto lg:hidden">
        <DataList.Root aria-label={label}>
          {resource.rows.map((row) => (
            <DataList.Item key={row.id}>
              <div className="flex items-start justify-between gap-2">
                {primary && <DataList.Title>{cell(row, primary.name)}</DataList.Title>}
                {hasActions && actionsMenu(row)}
              </div>
              {rest.map((column) => (
                <DataList.Field key={column.name} label={column.label}>
                  {cell(row, column.name)}
                </DataList.Field>
              ))}
            </DataList.Item>
          ))}
        </DataList.Root>
      </div>

      <div className="hidden min-h-0 flex-1 lg:block">
        <Table.Root aria-label={label}>
          <Table.Content>
            <Table.Header>
              {resource.columns.map((column) => (
                <Table.Column key={column.name}>{column.label}</Table.Column>
              ))}
              {hasActions && (
                <Table.Column>
                  <span className="sr-only">Actions</span>
                </Table.Column>
              )}
            </Table.Header>
            <Table.Body>
              {resource.rows.map((row) => (
                <Table.Row key={row.id}>
                  {resource.columns.map((column) => (
                    <Table.Cell key={column.name}>{cell(row, column.name)}</Table.Cell>
                  ))}
                  {hasActions && <Table.Cell>{actionsMenu(row)}</Table.Cell>}
                </Table.Row>
              ))}
            </Table.Body>
          </Table.Content>
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
