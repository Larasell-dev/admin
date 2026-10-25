import { Link, router } from "@inertiajs/react";
import { Button, Dialog, EmptyState, Icon, IconButton } from "@larasell-dev/ui";
import { ArrowLeftLine, FolderUnknowLine, ImageLine, UploadLine } from "@larasell-dev/ui/icons";
import { type ChangeEvent, useRef, useState } from "react";
import Freeze from "../../Components/Freeze";
import MediaTable, { type MediaTableResource } from "../../Components/Media/MediaTable";
import SidebarLayout from "../../Layouts/SidebarLayout";

/** A null table means the folder doesn't exist, which the server answers with a 404. */
type Props =
  | { table: null }
  | {
      table: MediaTableResource;
      /** Where to post product images as `files[]`. */
      uploadUrl: string;
      /** The folder on this page, which uploads go into; null at the top level. */
      folderId: number | null;
    };

/** The media at the top level or in a folder, with buttons to upload more. */
export default function Index(props: Props) {
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
        <Button
          variant="secondary"
          size="sm"
          loading={uploading}
          onClick={() => fileInput.current?.click()}
        >
          <Icon icon={UploadLine} />
          Upload
        </Button>
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
            <Button
              variant="primary"
              loading={uploading}
              onClick={() => fileInput.current?.click()}
            >
              <Icon icon={UploadLine} />
              Upload
            </Button>
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
    </div>
  );
}

Index.layout = [SidebarLayout];
