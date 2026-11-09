import { cn } from "cn";
import { thumbHashToDataURL } from "thumbhash";
import { DragDropProvider } from "@dnd-kit/react";
import { InfiniteScroll, Link, router } from "@inertiajs/react";
import {
  Badge,
  Button,
  ButtonGroup,
  EmptyState,
  Icon,
  IconButton,
  Menu,
  Spinner,
  useToast,
} from "@larasell-dev/ui";
import {
  FolderAddLine,
  DeleteBinLine,
  ImageLine,
  LayoutGridLine,
  ListUnordered,
  More2Fill,
  UploadLine,
  VideoLine,
} from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import { type ReactNode, useEffect, useId, useState } from "react";
import {
  ErrorToast,
  FolderBreadcrumb,
  FolderTile,
  FolderNameDialog,
  useFolderDeletion,
  useFolderRename,
} from "../Components/Folders";
import { dropMedia, useMediaDraggable, useMediaDropTarget } from "../Components/mediaDrag";
import { plugins, sensors } from "../../Platform/Support/dragAndDrop";
import { DeleteMediaDialog } from "../Components/DeleteMediaDialog";
import { uploadQueue } from "../Components/uploadQueue";
import { Page } from "../../Platform/Panel/Components/Page";
import SidebarLayout from "../../Platform/Panel/Layouts/SidebarLayout";

type Props = Larasell.Admin.Media.Data.MediaIndexPageData & {
  /** A page at a time, merged by Inertia's infinite scroll. */
  entries: { data: Larasell.Admin.Media.Data.MediaLibraryEntryData[] };
};
type Media = Larasell.Admin.Media.Data.MediaItemData;
/** How the library shows its entries: tiles, or rows with their names. */
export type LibraryView = "grid" | "list";

const VIEW_STORAGE_KEY = "larasell-admin.media.view";

/** The view toggle's picked button looks selected, as the UI library has no toggle button. */
const pressedClassName = "aria-pressed:bg-state-selected-neutral-default";

/** The view the admin picked last, kept in the browser. */
function useLibraryView() {
  const [view, setView] = useState<LibraryView>(() =>
    localStorage.getItem(VIEW_STORAGE_KEY) === "list" ? "list" : "grid",
  );
  const change = (next: LibraryView) => {
    localStorage.setItem(VIEW_STORAGE_KEY, next);
    setView(next);
  };
  return [view, change] as const;
}

/** Loads the entries again from the start, e.g. after an upload or another admin's change. */
function reloadEntries() {
  router.reload({ only: ["entries"], reset: ["entries"] });
}

/** How the library names a media item, e.g. in its link and the new folder dialog. */
function itemName(item: Media): string {
  return item.title ?? item.alt ?? item.fileName;
}

/**
 * The media library: a folder's subfolders, then its images and videos, in a grid or a list that
 * loads more as the admin scrolls, with buttons to make a folder and upload.
 */
export default function Index({
  folderId,
  tree,
  entries,
  storeUrl,
  rootUrl,
  createFolderUrl,
}: Props) {
  const [view, setView] = useLibraryView();
  const [creating, setCreating] = useState(false);
  const { askToDelete, dialog: deleteFolderDialog, open: deletingFolder } = useFolderDeletion();
  const { askToRename, dialog: renameFolderDialog, open: renamingFolder } = useFolderRename();
  const toast = useToast();
  // The item dropped onto "New folder", moved in once the admin creates it.
  // Each dialog keeps its last item after closing, so it still shows it while it animates out.
  const [intoNewFolder, setIntoNewFolder] = useState<Media | null>(null);
  const [intoNewFolderOpen, setIntoNewFolderOpen] = useState(false);
  const [deleting, setDeleting] = useState<Media | null>(null);
  const [deletingOpen, setDeletingOpen] = useState(false);
  const dialogOpen =
    creating || intoNewFolderOpen || deletingOpen || deletingFolder || renamingFolder;

  // The tree reloads itself when another admin changes the library; the entries, loaded a page
  // at a time, can't be live props, so they start over then.
  useEffect(() => router.on("live", reloadEntries), []);

  const byId = (mediaId: number) =>
    entries.data.find((entry) => entry.media?.id === mediaId)?.media ?? undefined;
  // Back on the same page, without the item: the server takes it off the grid.
  const move = (item: Media, target: number | null) => {
    if (target === folderId) return;
    router.post(
      item.moveUrl,
      { folder_id: target },
      {
        preserveScroll: true,
        onError: (errors) =>
          toast.add({
            children: <ErrorToast message={errors.folder ?? Object.values(errors)[0]} />,
          }),
      },
    );
  };
  const dropInto = (mediaId: number, target: number | null) => {
    const item = byId(mediaId);
    if (item) move(item, target);
  };

  return (
    <DragDropProvider sensors={sensors} plugins={plugins} onDragEnd={dropMedia}>
      <Page.Header
        title={fbs("Media", "Media library: page title")}
        actions={
          <div className="flex items-center gap-2">
            <ButtonGroup
              label={fbs(
                "View",
                "Media library: accessible name of the buttons that show the library as a grid or a list",
              )}
            >
              <IconButton
                variant="secondary"
                aria-label={fbs("Grid view", "Media library: shows the entries as tiles")}
                aria-pressed={view === "grid"}
                DANGEROUS_className={pressedClassName}
                onClick={() => setView("grid")}
              >
                <Icon icon={LayoutGridLine} />
              </IconButton>
              <IconButton
                variant="secondary"
                aria-label={fbs(
                  "List view",
                  "Media library: shows the entries as rows with their names",
                )}
                aria-pressed={view === "list"}
                DANGEROUS_className={pressedClassName}
                onClick={() => setView("list")}
              >
                <Icon icon={ListUnordered} />
              </IconButton>
            </ButtonGroup>
            <NewFolderButton
              onClick={() => setCreating(true)}
              onDropMedia={(mediaId) => {
                const item = byId(mediaId);
                if (!item) return;
                setIntoNewFolder(item);
                setIntoNewFolderOpen(true);
              }}
              dropEnabled={!dialogOpen}
            />
            <UploadButton storeUrl={storeUrl} />
          </div>
        }
      />
      <FolderNameDialog
        key={`new-${folderId}`}
        open={creating}
        onClose={() => setCreating(false)}
        url={createFolderUrl}
        method="post"
        initialName=""
        parentId={folderId}
      />
      {/* Always mounted, so it starts closed and animates in when an item is dropped. */}
      <FolderNameDialog
        open={intoNewFolderOpen}
        onClose={() => setIntoNewFolderOpen(false)}
        url={intoNewFolder?.moveToNewFolderUrl ?? ""}
        method="post"
        initialName=""
        parentId={folderId}
        description={
          intoNewFolder
            ? fbs(
                [fbs.param("itemName", itemName(intoNewFolder)), " is moved into it."],
                "Media library: in the new folder dialog opened by dropping a media item onto 'New folder', e.g. 'boots.png is moved into it.'",
              )
            : undefined
        }
        submitLabel={fbs(
          "Create and move",
          "Media library: button in the new folder dialog that creates the folder and moves the dropped item into it",
        )}
      />
      <DeleteMediaDialog
        destroyUrl={deleting?.destroyUrl ?? ""}
        open={deletingOpen}
        onOpenChange={setDeletingOpen}
      />
      {deleteFolderDialog}
      {renameFolderDialog}
      {folderId !== null && (
        <div className="px-6 pt-4">
          <FolderBreadcrumb
            tree={tree}
            folderId={folderId}
            rootUrl={rootUrl}
            onDropMedia={dropInto}
            dropEnabled={!dialogOpen}
          />
        </div>
      )}
      {entries.data.length === 0 ? (
        // Fills everything below the header, like an empty table page, so it sits in the middle.
        <div className="flex flex-1 items-center justify-center px-6 py-12">
          <EmptyState
            icon={ImageLine}
            title={fbs(
              "No media yet",
              "Media library: shown instead of the grid when nothing was uploaded",
            )}
            description={fbs(
              "Images and videos you upload show up here.",
              "Media library: below 'No media yet'",
            )}
          />
        </div>
      ) : (
        <LibraryBody view={view}>
          <InfiniteScroll
            data="entries"
            as="ul"
            buffer={600}
            className={cn(
              view === "grid"
                ? "grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-6"
                : "flex flex-col divide-y divide-base-subtle",
            )}
            loading={() => (
              <li className="col-span-full flex justify-center py-6 text-base-secondary">
                <Spinner
                  size={6}
                  aria-label={fbs(
                    "Loading more",
                    "Media library: spinner below the entries while the next ones load",
                  )}
                />
              </li>
            )}
          >
            {entries.data.map((entry) =>
              entry.folder ? (
                <FolderTile
                  key={entry.key}
                  folder={entry.folder}
                  view={view}
                  onRename={askToRename}
                  onDelete={askToDelete}
                  onDropMedia={dropInto}
                  dropEnabled={!dialogOpen}
                />
              ) : entry.media ? (
                <MediaTile
                  key={entry.key}
                  item={entry.media}
                  view={view}
                  onDelete={() => {
                    setDeleting(entry.media);
                    setDeletingOpen(true);
                  }}
                />
              ) : null,
            )}
          </InfiniteScroll>
        </LibraryBody>
      )}
    </DragDropProvider>
  );
}

function UploadButton({ storeUrl }: { storeUrl: string }) {
  const id = useId();

  // Each file goes to the upload queue, which sends them a few at a time and shows the toast;
  // page 1 picks up each one as it lands.
  useEffect(() => {
    uploadQueue.onSucceeded = reloadEntries;
    return () => {
      uploadQueue.onSucceeded = () => {};
    };
  }, []);

  return (
    <>
      <input
        id={id}
        type="file"
        multiple
        accept="image/jpeg,image/png,image/gif,image/webp,image/avif,video/mp4,video/webm,video/quicktime"
        aria-label={fbs(
          "Upload",
          "Media library: accessible name of the file picker that uploads media",
        )}
        className="sr-only"
        onChange={(event) => {
          const files = Array.from(event.currentTarget.files ?? []);
          event.currentTarget.value = "";
          if (files.length > 0) uploadQueue.add(storeUrl, files);
        }}
      />
      <Button render={<label htmlFor={id} />}>
        <Icon icon={UploadLine} />
        <fbt desc="Media library: button in the header that picks files to upload">Upload</fbt>
      </Button>
    </>
  );
}

/**
 * A media item: a tile in the grid, a row with its name in the list. Opens it, drags onto a
 * folder, with a menu to delete it.
 */
function MediaTile({
  item,
  view,
  onDelete,
}: {
  item: Media;
  view: LibraryView;
  onDelete: () => void;
}) {
  const { ref, dragging } = useMediaDraggable(item.id);
  const preview = (
    <span
      className={cn(
        "block shrink-0 overflow-hidden bg-base-section bg-cover bg-center",
        view === "grid" ? "aspect-square w-full" : "size-10",
      )}
      style={
        item.thumbHash && !item.thumbnailUrl
          ? { backgroundImage: `url("${placeholderUrl(item.thumbHash)}")` }
          : undefined
      }
    >
      {item.thumbnailUrl ? (
        // A WebP the queue made, of the image or a frame of the video.
        <TileImage src={item.thumbnailUrl} alt={item.alt ?? ""} thumbHash={item.thumbHash} />
      ) : (
        // Never the original: a library of large photos and videos would load slowly. The queue
        // makes the thumbnail shortly.
        <span className="flex size-full items-center justify-center text-base-secondary">
          <Icon icon={item.type === "video" ? VideoLine : ImageLine} />
        </span>
      )}
    </span>
  );

  return (
    <li className="relative">
      <Link
        href={item.showUrl}
        ref={ref}
        // A native link drag would take over the pointer before dnd-kit's starts.
        draggable={false}
        // dnd-kit makes a draggable a button unless it has a role; this one stays a link.
        role="link"
        data-dragging={dragging || undefined}
        aria-label={itemName(item)}
        // No text selection or iOS link callout on a long press, which starts a drag instead.
        className={cn(
          "block select-none [-webkit-touch-callout:none] focus-visible:focus-ring data-dragging:opacity-60",
          view === "list" && "flex items-center gap-3 py-2 pr-14 pl-6 hover:bg-state-hover",
        )}
      >
        {view === "grid" ? (
          <span className="relative block">
            {preview}
            {item.status !== "ready" && (
              <span className="absolute bottom-1 left-1">
                <StatusBadge status={item.status} />
              </span>
            )}
          </span>
        ) : (
          preview
        )}
        {view === "list" && (
          <span className="flex min-w-0 flex-col">
            <span className="truncate type-body-md text-base-primary">{itemName(item)}</span>
            <span className="truncate type-body-sm text-base-secondary">
              {item.status !== "ready" ? (
                <StatusBadge status={item.status} />
              ) : itemName(item) !== item.fileName ? (
                item.fileName
              ) : item.type === "video" ? (
                fbs("Video", "Media library list: what a row is, for a video")
              ) : (
                fbs("Image", "Media library list: what a row is, for an image")
              )}
            </span>
          </span>
        )}
      </Link>
      <div
        className={cn(
          "absolute",
          view === "grid" ? "top-1 right-1" : "top-1/2 right-4 -translate-y-1/2",
        )}
      >
        <Menu.Root>
          <Menu.Trigger
            render={
              <IconButton
                variant="tertiary"
                size="sm"
                aria-label={fbs(
                  ["Actions for ", fbs.param("itemName", itemName(item))],
                  "Media library: accessible name of the button that opens a media item's menu, e.g. 'Actions for boots.png'",
                )}
              >
                <Icon icon={More2Fill} />
              </IconButton>
            }
          />
          <Menu.Popup>
            <Menu.Item icon={DeleteBinLine} variant="destructive" onClick={onDelete}>
              <fbt desc="Media library item menu: asks to delete the media item">Delete</fbt>
            </Menu.Item>
          </Menu.Popup>
        </Menu.Root>
      </div>
    </li>
  );
}

/** Creates a folder; a media item dropped onto it asks for the name and is moved in. */
function NewFolderButton({
  onClick,
  onDropMedia,
  dropEnabled,
}: {
  onClick: () => void;
  onDropMedia: (mediaId: number) => void;
  dropEnabled: boolean;
}) {
  const { ref, over } = useMediaDropTarget(onDropMedia, dropEnabled);

  return (
    <span
      ref={ref}
      data-drop-target={over ? "over" : undefined}
      className={cn("flex", over && "focus-ring")}
    >
      <Button variant="secondary" onClick={onClick}>
        <Icon icon={FolderAddLine} />
        <fbt desc="Media library: header button that creates a folder">New folder</fbt>
      </Button>
    </span>
  );
}

/**
 * Scrolls the entries. The grid sits in the page's padded body; the list, like a table, runs
 * from edge to edge, its rows padded instead.
 */
function LibraryBody({ view, children }: { view: LibraryView; children: ReactNode }) {
  if (view === "grid") return <Page.Body width="wide">{children}</Page.Body>;
  return <div className="min-h-0 flex-1 overflow-y-auto pt-4 pb-28">{children}</div>;
}

/** Says that a media item isn't ready yet, e.g. while Mux transcodes it, or that it failed. */
function StatusBadge({ status }: { status: Media["status"] }) {
  return status === "failed" ? (
    <Badge variant="danger">
      <fbt desc="Media library: badge on an item its provider, e.g. Mux, failed to process">
        Failed
      </fbt>
    </Badge>
  ) : (
    <Badge variant="info">
      <fbt desc="Media library: badge on an item its provider, e.g. Mux, still works on">
        Processing
      </fbt>
    </Badge>
  );
}

Index.layout = [SidebarLayout];

/** A blurry miniature of the image, decoded from its ThumbHash, shown until the thumbnail loads. */
function placeholderUrl(thumbHash: string): string {
  return thumbHashToDataURL(Uint8Array.from(atob(thumbHash), (char) => char.charCodeAt(0)));
}

/**
 * A tile's thumbnail, hidden until it has loaded and then faded in over the ThumbHash behind it,
 * so the blurry preview turns into the image instead of being swapped for it. The ThumbHash is
 * dropped once the image has faded in, so its blur can't peek out around the image's antialiased edges.
 */
function TileImage({
  src,
  alt,
  thumbHash,
}: {
  src: string;
  alt: string;
  thumbHash?: string | null;
}) {
  const [loaded, setLoaded] = useState(false);
  const [shown, setShown] = useState(false);
  const reveal = (image: HTMLImageElement) => {
    setLoaded(true);
    // Without motion there is no fade, and no transitionend to wait for.
    if (getComputedStyle(image).transitionDuration === "0s") setShown(true);
  };

  return (
    <span
      className="block size-full bg-cover bg-center"
      style={
        thumbHash && !shown ? { backgroundImage: `url("${placeholderUrl(thumbHash)}")` } : undefined
      }
    >
      <img
        // A cached image may have loaded before React listened for it.
        ref={(image) => {
          if (image?.complete && image.naturalWidth > 0) reveal(image);
        }}
        src={src}
        alt={alt}
        // The tile is what's dragged, carrying the item; the image alone would carry its URL.
        draggable={false}
        loading="lazy"
        decoding="async"
        data-loaded={loaded}
        onLoad={(event) => reveal(event.currentTarget)}
        onTransitionEnd={() => setShown(true)}
        className="size-full object-cover opacity-0 transition-opacity duration-300 ease-out data-[loaded=true]:opacity-100 motion-reduce:transition-none"
      />
    </span>
  );
}
