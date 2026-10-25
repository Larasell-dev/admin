import { Link, router, useForm } from "@inertiajs/react";
import {
  Button,
  ButtonGroup,
  Dialog,
  EmptyState,
  Field,
  Icon,
  IconButton,
  Input,
  Menu,
} from "@larasell-dev/ui";
import {
  ArrowDownSLine,
  ArrowLeftLine,
  FolderAddLine,
  FolderUnknowLine,
  ImageLine,
  UploadLine,
} from "@larasell-dev/ui/icons";
import { type ChangeEvent, type FormEvent, useRef, useState } from "react";
import Freeze from "../../Components/Freeze";
import MediaTable, { type MediaTableResource } from "../../Components/Media/MediaTable";
import SidebarLayout from "../../Layouts/SidebarLayout";

/** A null table means the folder doesn't exist, which the server answers with a 404. */
type Props =
  | { table: null }
  | {
      table: MediaTableResource;
      createFolderUrl: string;
      /** Where to post product images as `files[]`. */
      uploadUrl: string;
      /** The folder on this page, which new folders and uploads go into; null at the top level. */
      folderId: number | null;
    };

/** The media at the top level or in a folder, with buttons to add more. */
export default function Index(props: Props) {
  const [creatingFolder, setCreatingFolder] = useState(false);
  const fileInput = useRef<HTMLInputElement>(null);
  const [uploading, setUploading] = useState(false);
  const [uploadErrors, setUploadErrors] = useState<string[]>([]);

  if (props.table === null) {
    return (
      <div className="flex h-[calc(100dvh-3.5rem)] flex-col lg:h-dvh">
        <header className="border-border flex h-14 shrink-0 items-center gap-2 border-b px-6">
          <h1 className="flex-1 truncate type-body-md text-mauve-950 dark:text-mauve-50">
            Folder not found
          </h1>
        </header>
        <div className="flex flex-1 items-center justify-center px-6 py-12">
          <EmptyState
            icon={FolderUnknowLine}
            title="This folder doesn't exist anymore"
            description="It may have been deleted, or the link you followed is out of date."
          />
        </div>
      </div>
    );
  }

  const { table, uploadUrl, folderId } = props;

  function upload(event: ChangeEvent<HTMLInputElement>) {
    const files = [...(event.currentTarget.files ?? [])];
    // Cleared, so picking the same files again still counts as a change.
    event.currentTarget.value = "";
    if (files.length === 0) return;

    // Inertia sends the files as multipart form data.
    router.post(
      uploadUrl,
      { files, folder_id: folderId },
      {
        preserveScroll: true,
        onStart: () => setUploading(true),
        onFinish: () => setUploading(false),
        onError: (errors) => setUploadErrors(Object.values(errors)),
      },
    );
  }

  return (
    <div className="flex h-[calc(100dvh-3.5rem)] flex-col lg:h-dvh">
      <header className="border-border flex h-14 shrink-0 items-center gap-2 border-b px-6">
        {table.backLink && (
          <IconButton
            variant="tertiary"
            size="sm"
            aria-label={table.backLink.label}
            nativeButton={false}
            // Base UI marks non-native buttons as buttons; this navigates, so it stays a link.
            role="link"
            render={<Link href={table.backLink.url} prefetch="hover" />}
          >
            <Icon icon={ArrowLeftLine} />
          </IconButton>
        )}
        <h1 className="flex-1 truncate type-body-md text-mauve-950 dark:text-mauve-50">
          {table.title}
        </h1>
        <ButtonGroup label="Add media" size="sm">
          <Button
            variant="secondary"
            loading={uploading}
            onClick={() => fileInput.current?.click()}
          >
            <Icon icon={UploadLine} />
            Upload
          </Button>
          <Menu.Root>
            <Menu.Trigger
              // Nothing else to start while files upload.
              disabled={uploading}
              render={
                <IconButton variant="secondary" aria-label="More actions">
                  <Icon icon={ArrowDownSLine} />
                </IconButton>
              }
            />
            <Menu.Popup>
              <Menu.Item icon={FolderAddLine} onClick={() => setCreatingFolder(true)}>
                New folder
              </Menu.Item>
            </Menu.Popup>
          </Menu.Root>
        </ButtonGroup>
      </header>
      {/* Opened by both Upload buttons. The types match the server's: raster images only. */}
      <input
        ref={fileInput}
        type="file"
        multiple
        accept="image/jpeg,image/png,image/gif,image/bmp,image/webp"
        hidden
        onChange={upload}
      />

      {table.pagination.total === 0 ? (
        <div className="flex flex-1 items-center justify-center px-6 py-12">
          <EmptyState
            icon={ImageLine}
            title={table.emptyState?.title ?? "No media yet"}
            description={table.emptyState?.description ?? undefined}
          >
            <ButtonGroup label="Add media">
              <Button
                variant="primary"
                loading={uploading}
                onClick={() => fileInput.current?.click()}
              >
                <Icon icon={UploadLine} />
                Upload
              </Button>
              <Menu.Root>
                <Menu.Trigger
                  // Nothing else to start while files upload.
                  disabled={uploading}
                  render={
                    <IconButton variant="primary" aria-label="More actions">
                      <Icon icon={ArrowDownSLine} />
                    </IconButton>
                  }
                />
                <Menu.Popup>
                  <Menu.Item icon={FolderAddLine} onClick={() => setCreatingFolder(true)}>
                    New folder
                  </Menu.Item>
                </Menu.Popup>
              </Menu.Root>
            </ButtonGroup>
          </EmptyState>
        </div>
      ) : (
        <MediaTable table={table} />
      )}

      <Dialog.Root
        open={uploadErrors.length > 0}
        onOpenChange={(open) => !open && setUploadErrors([])}
      >
        {/* Keeps the errors while the popup animates out. */}
        <Freeze frozen={uploadErrors.length === 0}>
          <Dialog.Popup
            title="Couldn't upload"
            description="Nothing was uploaded. Leave out or fix these files and try again."
          >
            <ul className="list-disc pl-5 type-body-md text-mauve-950 dark:text-mauve-50">
              {uploadErrors.map((error) => (
                <li key={error}>{error}</li>
              ))}
            </ul>
            <Dialog.Actions>
              <Dialog.Close />
            </Dialog.Actions>
          </Dialog.Popup>
        </Freeze>
      </Dialog.Root>

      <NewFolderDialog
        open={creatingFolder}
        onOpenChange={setCreatingFolder}
        url={props.createFolderUrl}
        parentId={props.folderId}
      />
    </div>
  );
}

Index.layout = [SidebarLayout];

type NewFolderDialogProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  url: string;
  parentId: number | null;
};

/** Asks for a name and creates the folder inside the given parent, or at the top level. */
function NewFolderDialog({ open, onOpenChange, url, parentId }: NewFolderDialogProps) {
  const form = useForm<{ name: string; parent_id: number | null }>({ name: "", parent_id: null });

  function changeOpen(open: boolean) {
    onOpenChange(open);

    // Unseen while the popup is frozen, so the next folder starts without the last one's name or errors.
    if (!open) {
      form.reset();
      form.clearErrors();
    }
  }

  function submit(event: FormEvent) {
    event.preventDefault();
    // The parent comes from the page, which Inertia reuses when moving between folders.
    form.transform((data) => ({ ...data, parent_id: parentId }));
    form.post(url, { preserveScroll: true, onSuccess: () => changeOpen(false) });
  }

  return (
    <Dialog.Root open={open} onOpenChange={changeOpen}>
      {/* Keeps the name, errors and loading button as they were while the popup animates out. */}
      <Freeze frozen={!open}>
        <Dialog.Popup title="New folder">
          <form className="grid gap-4" onSubmit={submit}>
            <Field
              label="Name"
              name="name"
              required
              // A parent deleted in another tab has no field of its own, so it shows here.
              error={form.errors.name ?? form.errors.parent_id}
            >
              <Input
                autoComplete="off"
                value={form.data.name}
                onChange={(event) => form.setData("name", event.currentTarget.value)}
              />
            </Field>
            <Dialog.Actions>
              <Dialog.Close>Cancel</Dialog.Close>
              <Button type="submit" loading={form.processing}>
                Create
              </Button>
            </Dialog.Actions>
          </form>
        </Dialog.Popup>
      </Freeze>
    </Dialog.Root>
  );
}
