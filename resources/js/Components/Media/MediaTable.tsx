import { Link, router, usePage } from "@inertiajs/react";
import type { PaginationChange } from "@larasell-dev/ui";
import { AlertDialog, DataList, Field, Input, Pagination, Table } from "@larasell-dev/ui";
import { DeleteBinLine } from "@larasell-dev/ui/icons";
import { type ReactNode, useState } from "react";
import type { Page, TableResource, TableRow } from "../../types";
import { isPanelUrl } from "../../utils/isPanelUrl";
import Freeze from "../Freeze";

/** A row of Larasell\Admin\Tables\Panel\Media\Media, which also names its type and where to delete it. */
export type MediaRow = Pick<TableRow, "id" | "_actions" | "_link"> & {
  name: string;
  /** "Folder" or "Product image". */
  type: string;
  _type: "folder" | "product-image";
  _deleteUrl: string;
};

export type MediaTableResource = Omit<TableResource, "rows"> & { rows: MediaRow[] };

/**
 * The media as a data list on small screens and a table from `lg` up. Unlike ResourceTable, it
 * draws its own actions, so deleting can ask with copy that fits a folder or a product image.
 */
export default function MediaTable({ table }: { table: MediaTableResource }) {
  const { panelUrl } = usePage<Page>().props;
  const [deleting, setDeleting] = useState<MediaRow | null>(null);
  const [isPaginating, setIsPaginating] = useState(false);

  const pagination = {
    ...table.pagination,
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

  /** Links the row through its name; the link stretches over the whole row. */
  const linked = (row: MediaRow, RowLink: typeof Table.Link) =>
    row._link ? (
      <RowLink
        render={
          // Product images link to their file, which is no Inertia page.
          isPanelUrl(row._link, panelUrl) ? <Link href={row._link} /> : <a href={row._link} />
        }
      >
        {row.name}
      </RowLink>
    ) : (
      row.name
    );

  const actionsMenu = (row: MediaRow) => (
    <Table.Actions>
      <Table.ActionsTrigger aria-label={`Actions for ${row.name}`} />
      <Table.ActionsPopup>
        <Table.ActionsItem
          variant="destructive"
          icon={DeleteBinLine}
          onClick={() => setDeleting(row)}
        >
          Delete
        </Table.ActionsItem>
      </Table.ActionsPopup>
    </Table.Actions>
  );

  return (
    <>
      <div className="flex min-h-0 flex-1 flex-col lg:hidden">
        <div className="min-h-0 flex-1 overflow-y-auto">
          <DataList.Root aria-label={table.title}>
            {table.rows.map((row) => (
              <DataList.Item key={row.id}>
                <div className="flex items-start justify-between gap-2">
                  <DataList.Title>{linked(row, DataList.Link)}</DataList.Title>
                  {actionsMenu(row)}
                </div>
                <DataList.Field label="Type">{row.type}</DataList.Field>
              </DataList.Item>
            ))}
          </DataList.Root>
        </div>
        <div className="border-border shrink-0 border-t px-4 py-3">
          <Pagination {...pagination} />
        </div>
      </div>

      <div className="hidden min-h-0 flex-1 lg:block">
        <Table.Root aria-label={table.title}>
          <Table.Content>
            <Table.Header>
              <Table.Column>Name</Table.Column>
              <Table.Column>Type</Table.Column>
              <Table.Column type="action">Actions</Table.Column>
            </Table.Header>
            <Table.Body>
              {table.rows.map((row) => (
                <Table.Row key={row.id}>
                  <Table.Cell>{linked(row, Table.Link)}</Table.Cell>
                  <Table.Cell>{row.type}</Table.Cell>
                  <Table.Cell>{actionsMenu(row)}</Table.Cell>
                </Table.Row>
              ))}
            </Table.Body>
          </Table.Content>
          <Table.Pagination {...pagination} />
        </Table.Root>
      </div>

      <DeleteMediaDialog row={deleting} onClose={() => setDeleting(null)} />
    </>
  );
}

function DeleteMediaDialog({ row, onClose }: { row: MediaRow | null; onClose: () => void }) {
  // Kept here rather than in the confirmation, so freezing it also freezes them: the request
  // finishes after the dialog starts closing, and the button should keep loading meanwhile.
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState<string>();

  const destroy = (row: MediaRow, typed: string) =>
    router.delete(row._deleteUrl, {
      data: { confirmation: typed },
      preserveScroll: true,
      onStart: () => {
        setProcessing(true);
        setError(undefined);
      },
      onFinish: () => setProcessing(false),
      onSuccess: onClose,
      onError: (errors) => setError(errors.confirmation),
    });

  return (
    <AlertDialog.Root
      open={row !== null}
      onOpenChange={(open) => {
        if (!open) {
          onClose();
          setError(undefined);
        }
      }}
    >
      {/* Keeps the last row's copy, typed name, error and loading button while the popup animates out. */}
      <Freeze frozen={row === null}>
        {row && (
          // Keyed by the row, so the typed name starts empty for each one.
          <DeleteConfirmation
            key={row.id}
            row={row}
            processing={processing}
            error={error}
            onConfirm={(typed) => destroy(row, typed)}
          />
        )}
      </Freeze>
    </AlertDialog.Root>
  );
}

type DeleteConfirmationProps = {
  row: MediaRow;
  processing: boolean;
  error?: string;
  onConfirm: (typed: string) => void;
};

/** Asks to type the media's name before deleting it; the server checks the name too. */
function DeleteConfirmation({ row, processing, error, onConfirm }: DeleteConfirmationProps) {
  const [typed, setTyped] = useState("");

  return (
    <AlertDialog.Popup {...deleteCopy(row)}>
      <Field label={`Type "${row.name}" to confirm`} name="confirmation" error={error}>
        <Input
          autoComplete="off"
          value={typed}
          onChange={(event) => setTyped(event.currentTarget.value)}
        />
      </Field>
      <AlertDialog.Actions>
        <AlertDialog.Cancel />
        <AlertDialog.Confirm
          variant="destructive"
          loading={processing}
          onClick={() => onConfirm(typed)}
        >
          Delete
        </AlertDialog.Confirm>
      </AlertDialog.Actions>
    </AlertDialog.Popup>
  );
}

function deleteCopy(row: MediaRow): { title: string; description: ReactNode } {
  return row._type === "folder"
    ? {
        title: "Delete folder",
        description: `"${row.name}" and everything in it, including its folders, will be deleted. This can't be undone.`,
      }
    : {
        title: "Delete product image",
        description: `"${row.name}" will be deleted and removed from every product that shows it. This can't be undone.`,
      };
}
