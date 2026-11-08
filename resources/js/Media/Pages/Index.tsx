import { cn } from "cn";
import { thumbHashToDataURL } from "thumbhash";
import { Link, router } from "@inertiajs/react";
import {
  Button,
  EmptyState,
  Icon,
  IconButton,
  Menu,
  Pagination,
  type PaginationChange,
  useToast,
} from "@larasell-dev/ui";
import {
  FolderAddLine,
  FolderTransferLine,
  ImageLine,
  More2Fill,
  UploadLine,
  VideoLine,
} from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import { useEffect, useId, useState } from "react";
import {
  dropTargetOver,
  ErrorToast,
  FolderBreadcrumb,
  FolderGrid,
  FolderNameDialog,
  useFolderDeletion,
  useFolderRename,
} from "../Components/Folders";
import {
  mediaDragSource,
  useMediaDropTarget,
  usePreventStrayMediaDrops,
} from "../Components/mediaDrag";
import { MoveMediaDialog } from "../Components/MoveMediaDialog";
import { uploadQueue } from "../Components/uploadQueue";
import { Page } from "../../Platform/Panel/Components/Page";
import SidebarLayout from "../../Platform/Panel/Layouts/SidebarLayout";

type Props = Larasell.Admin.Media.Data.MediaIndexPageData;
type Media = Larasell.Admin.Media.Data.MediaItemData;

/** How the library names a media item, e.g. in its link and the move dialog. */
function itemName(item: Media): string {
  return item.title ?? item.alt ?? item.fileName;
}

/** The media library: a grid of images and videos, a page at a time, with an upload button. */
export default function Index({
  folderId,
  folders,
  tree,
  media,
  pagination,
  storeUrl,
  rootUrl,
  createFolderUrl,
}: Props) {
  const [paginating, setPaginating] = useState(false);
  const [creating, setCreating] = useState(false);
  const { askToDelete, dialog: deleteFolderDialog, open: deletingFolder } = useFolderDeletion();
  const { askToRename, dialog: renameFolderDialog, open: renamingFolder } = useFolderRename();
  const toast = useToast();
  // The item dropped onto "New folder", moved in once the admin creates it.
  const [intoNewFolder, setIntoNewFolder] = useState<Media | null>(null);
  const [moving, setMoving] = useState<Media | null>(null);
  const [movingOpen, setMovingOpen] = useState(false);
  const dialogOpen =
    creating || intoNewFolder !== null || movingOpen || deletingFolder || renamingFolder;
  usePreventStrayMediaDrops();

  const byId = (mediaId: number) => media.find((item) => item.id === mediaId);
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
  const dropMedia = (mediaId: number, target: number | null) => {
    const item = byId(mediaId);
    if (item) move(item, target);
  };
  const newFolderDrop = useMediaDropTarget((mediaId) => {
    setIntoNewFolder(byId(mediaId) ?? null);
  }, !dialogOpen);

  return (
    <>
      <Page.Header
        title={fbs("Media", "Media library: page title")}
        actions={
          <div className="flex items-center gap-2">
            <span
              {...newFolderDrop.dropProps}
              data-drop-target={newFolderDrop.over ? "over" : undefined}
              className={cn("flex rounded-md", newFolderDrop.over && dropTargetOver)}
            >
              <Button variant="secondary" onClick={() => setCreating(true)}>
                <Icon icon={FolderAddLine} />
                <fbt desc="Media library: header button that creates a folder">New folder</fbt>
              </Button>
            </span>
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
      {intoNewFolder && (
        <FolderNameDialog
          key={`move-${intoNewFolder.id}`}
          open
          onClose={() => setIntoNewFolder(null)}
          url={intoNewFolder.moveToNewFolderUrl}
          method="post"
          initialName=""
          parentId={folderId}
          description={fbs(
            [fbs.param("itemName", itemName(intoNewFolder)), " is moved into it."],
            "Media library: in the new folder dialog opened by dropping a media item onto 'New folder', e.g. 'boots.png is moved into it.'",
          )}
          submitLabel={fbs(
            "Create and move",
            "Media library: button in the new folder dialog that creates the folder and moves the dropped item into it",
          )}
        />
      )}
      {moving && (
        <MoveMediaDialog
          key={moving.id}
          open={movingOpen}
          onClose={() => setMovingOpen(false)}
          itemName={itemName(moving)}
          tree={tree}
          folderId={folderId}
          onMove={(target) => move(moving, target)}
        />
      )}
      {deleteFolderDialog}
      {renameFolderDialog}
      {(folderId !== null || folders.length > 0) && (
        <div className="flex flex-col gap-4 px-6 pt-4">
          <FolderBreadcrumb
            tree={tree}
            folderId={folderId}
            rootUrl={rootUrl}
            onDropMedia={dropMedia}
            dropEnabled={!dialogOpen}
          />
          {folders.length > 0 && (
            <FolderGrid
              folders={folders}
              onRename={askToRename}
              onDelete={askToDelete}
              onDropMedia={dropMedia}
              dropEnabled={!dialogOpen}
            />
          )}
        </div>
      )}
      {media.length === 0 ? (
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
        <Page.Body width="wide">
          <div className="flex flex-col gap-6">
            <ul className="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-6">
              {media.map((item) => (
                <li key={item.id} className="relative">
                  <Link
                    href={item.showUrl}
                    {...mediaDragSource(item.id)}
                    aria-label={itemName(item)}
                    className="block aspect-square overflow-hidden bg-base-section bg-cover bg-center focus-visible:focus-ring"
                    style={
                      item.thumbHash && !item.thumbnailUrl
                        ? { backgroundImage: `url("${placeholderUrl(item.thumbHash)}")` }
                        : undefined
                    }
                  >
                    {item.thumbnailUrl ? (
                      // A WebP the queue made, of the image or a frame of the video.
                      <TileImage
                        src={item.thumbnailUrl}
                        alt={item.alt ?? ""}
                        thumbHash={item.thumbHash}
                      />
                    ) : (
                      // Never the original: a library of large photos and videos would load
                      // slowly. The queue makes the thumbnail shortly.
                      <span className="flex size-full items-center justify-center text-base-secondary">
                        <Icon icon={item.type === "video" ? VideoLine : ImageLine} />
                      </span>
                    )}
                  </Link>
                  {(tree.length > 0 || folderId !== null) && (
                    <div className="absolute top-1 right-1">
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
                          <Menu.Item
                            icon={FolderTransferLine}
                            onClick={() => {
                              setMoving(item);
                              setMovingOpen(true);
                            }}
                          >
                            <fbt desc="Media library item menu: opens a dialog that moves the item to another folder">
                              Move to…
                            </fbt>
                          </Menu.Item>
                        </Menu.Popup>
                      </Menu.Root>
                    </div>
                  )}
                </li>
              ))}
            </ul>
          </div>
        </Page.Body>
      )}
      {/* Across the whole page below the grid, always there, like a table's. */}
      <div
        data-testid="media-pagination"
        className="shrink-0 border-t border-base-subtle px-6 py-3"
      >
        <Pagination
          {...pagination}
          pageSizeOptions={[24, 48, 96]}
          disabled={paginating}
          onPaginationChange={({ page, pageSize }: PaginationChange) =>
            router.get(
              window.location.pathname,
              { page, per_page: pageSize },
              {
                preserveScroll: true,
                onStart: () => setPaginating(true),
                onFinish: () => setPaginating(false),
              },
            )
          }
        />
      </div>
    </>
  );
}

function UploadButton({ storeUrl }: { storeUrl: string }) {
  const id = useId();

  // Each file goes to the upload queue, which sends them a few at a time and shows the toast;
  // page 1 picks up each one as it lands.
  useEffect(() => {
    uploadQueue.onSucceeded = () => router.reload({ only: ["media", "pagination", "folders"] });
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
