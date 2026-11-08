import { Link, router, useForm } from "@inertiajs/react";
import {
  AlertDialog,
  Button,
  Dialog,
  Field,
  Icon,
  IconButton,
  Input,
  Inset,
  Menu,
  useToast,
} from "@larasell-dev/ui";
import {
  ArrowRightSLine,
  DeleteBinLine,
  EditLine,
  FolderLine,
  More2Fill,
} from "@larasell-dev/ui/icons";
import { cn } from "cn";
import { fbs } from "fbtee";
import { type ReactNode, useState } from "react";
import { useMediaDropTarget } from "./mediaDrag";

type Folder = Larasell.Admin.Media.Data.MediaFolderData;
type Node = Larasell.Admin.Media.Data.MediaFolderNodeData;

/** Classes of a drop target while a dragged media item is over it. */
export const dropTargetOver =
  "bg-state-selected-accent-default outline-2 outline-state-selected-accent";

/** Where a dragged media item goes when dropped: a folder's ID, or null for the top level. */
type DropMedia = (mediaId: number, folderId: number | null) => void;

/**
 * The folders from the top level down to the one shown, each a link; the top level is "All media".
 * A media item dropped onto a link moves to that folder; the current one is no target.
 */
export function FolderBreadcrumb({
  tree,
  folderId,
  rootUrl,
  onDropMedia,
  dropEnabled,
}: {
  tree: Node[];
  folderId: number | null;
  rootUrl: string;
  onDropMedia: DropMedia;
  dropEnabled: boolean;
}) {
  const path: Node[] = [];
  const byId = new Map(tree.map((node) => [node.id, node]));
  for (let node = folderId ? byId.get(folderId) : undefined; node;) {
    path.unshift(node);
    node = node.parentId ? byId.get(node.parentId) : undefined;
  }
  if (path.length === 0) return null;

  return (
    <nav
      aria-label={fbs("Folders", "Media library: accessible name of the breadcrumb of folders")}
      className="flex min-w-0 flex-wrap items-center gap-1 type-body-md text-base-secondary"
    >
      <BreadcrumbLink
        href={rootUrl}
        onDrop={(mediaId) => onDropMedia(mediaId, null)}
        dropEnabled={dropEnabled}
      >
        <fbt desc="Media library breadcrumb: link to the top level of the library">All media</fbt>
      </BreadcrumbLink>
      {path.map((node, index) => (
        <span key={node.id} className="flex min-w-0 items-center gap-1">
          <Icon icon={ArrowRightSLine} />
          {index === path.length - 1 ? (
            <span aria-current="page" className="truncate text-base-primary">
              {node.name}
            </span>
          ) : (
            <BreadcrumbLink
              href={node.url}
              onDrop={(mediaId) => onDropMedia(mediaId, node.id)}
              dropEnabled={dropEnabled}
            >
              {node.name}
            </BreadcrumbLink>
          )}
        </span>
      ))}
    </nav>
  );
}

function BreadcrumbLink({
  href,
  onDrop,
  dropEnabled,
  children,
}: {
  href: string;
  onDrop: (mediaId: number) => void;
  dropEnabled: boolean;
  children: ReactNode;
}) {
  const { over, dropProps } = useMediaDropTarget(onDrop, dropEnabled);

  return (
    <Link
      href={href}
      {...dropProps}
      data-drop-target={over ? "over" : undefined}
      className={cn(
        "truncate px-1 hover:text-base-primary focus-visible:focus-ring",
        over && [dropTargetOver, "text-base-primary"],
      )}
    >
      {children}
    </Link>
  );
}

/** The folders in the grid. A media item dropped onto one moves into it. */
export function FolderGrid({
  folders,
  onRename,
  onDelete,
  onDropMedia,
  dropEnabled,
}: {
  folders: Folder[];
  onRename: (folder: Folder) => void;
  onDelete: (folder: Folder) => void;
  onDropMedia: DropMedia;
  dropEnabled: boolean;
}) {
  return (
    <ul className="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-6">
      {folders.map((folder) => (
        <FolderTile
          key={folder.id}
          folder={folder}
          onRename={onRename}
          onDelete={onDelete}
          onDropMedia={onDropMedia}
          dropEnabled={dropEnabled}
        />
      ))}
    </ul>
  );
}

/**
 * Deletes folders, with one confirmation dialog for all of them. The page renders `dialog` outside
 * the folder list, so it stays mounted and keeps the last folder while it animates out, even when
 * that folder was the last one in the list.
 */
export function useFolderDeletion() {
  const toast = useToast();
  const [target, setTarget] = useState<Folder | null>(null);
  const [open, setOpen] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [typed, setTyped] = useState("");
  const [confirmError, setConfirmError] = useState<string>();

  const destroy = (folder: Folder, confirmation: string) =>
    router.delete(folder.destroyUrl, {
      data: { confirmation },
      preserveScroll: true,
      onStart: () => setDeleting(true),
      onFinish: () => setDeleting(false),
      onSuccess: () => setOpen(false),
      onError: (errors) => {
        if (errors.confirmation) {
          setConfirmError(errors.confirmation);
          return;
        }
        setOpen(false);
        toast.add({
          children: (
            <Inset>
              <p className="type-body-md text-base-primary">
                {errors.folder ?? Object.values(errors)[0]}
              </p>
            </Inset>
          ),
        });
      },
    });

  // An empty folder asks first; one with something in it goes to the server, which refuses and
  // says why. The counts may be stale, so the server decides either way.
  const askToDelete = (folder: Folder) => {
    if (folder.mediaCount > 0 || folder.folderCount > 0) {
      destroy(folder, "");
      return;
    }
    setTarget(folder);
    setTyped("");
    setConfirmError(undefined);
    setOpen(true);
  };

  const dialog = (
    <AlertDialog.Root open={open} onOpenChange={setOpen}>
      {target && (
        <AlertDialog.Popup
          title={fbs(
            ["Delete ", fbs.param("folderName", target.name)],
            "Media library: title of the dialog that confirms deleting an empty folder, e.g. 'Delete Summer'",
          )}
          description={fbs(
            "The folder is empty, so no files are deleted. This can't be undone.",
            "Media library: explains what deleting an empty folder does",
          )}
        >
          <Field
            label={fbs(
              ['Type "', fbs.param("folderName", target.name), '" to confirm'],
              "Media library: label of the field in the delete folder dialog that the admin types the folder's name into to confirm, e.g. 'Type \"Summer\" to confirm'",
            )}
            name="confirmation"
            error={confirmError}
          >
            <Input
              autoComplete="off"
              value={typed}
              onChange={(event) => setTyped(event.currentTarget.value)}
            />
          </Field>
          <AlertDialog.Actions>
            <AlertDialog.Cancel>
              <fbt desc="Media library: closes the delete folder dialog without deleting">
                Cancel
              </fbt>
            </AlertDialog.Cancel>
            <AlertDialog.Confirm
              variant="destructive"
              loading={deleting}
              disabled={typed !== target.name}
              onClick={() => destroy(target, typed)}
            >
              <fbt desc="Media library: button in the dialog that deletes the folder">Delete</fbt>
            </AlertDialog.Confirm>
          </AlertDialog.Actions>
        </AlertDialog.Popup>
      )}
    </AlertDialog.Root>
  );

  return { askToDelete, dialog, open };
}

/**
 * Renames folders, with one dialog for all of them, rendered by the page outside the folder list
 * like the delete dialog, so it keeps its folder while it animates out.
 */
export function useFolderRename() {
  const [target, setTarget] = useState<Folder | null>(null);
  const [open, setOpen] = useState(false);
  const askToRename = (folder: Folder) => {
    setTarget(folder);
    setOpen(true);
  };
  const dialog = target && (
    // Keyed by the folder, so each one starts with its own name.
    <FolderNameDialog
      key={target.id}
      open={open}
      onClose={() => setOpen(false)}
      url={target.renameUrl}
      method="put"
      initialName={target.name}
      parentId={null}
    />
  );

  return { askToRename, dialog, open };
}

/** A folder in the grid: opens it, with a menu to rename or delete it. */
function FolderTile({
  folder,
  onRename,
  onDelete,
  onDropMedia,
  dropEnabled,
}: {
  folder: Folder;
  onRename: (folder: Folder) => void;
  onDelete: (folder: Folder) => void;
  onDropMedia: DropMedia;
  dropEnabled: boolean;
}) {
  const { over, dropProps } = useMediaDropTarget(
    (mediaId) => onDropMedia(mediaId, folder.id),
    dropEnabled,
  );

  return (
    <li className="relative">
      <Link
        href={folder.url}
        {...dropProps}
        data-drop-target={over ? "over" : undefined}
        className={cn(
          "flex aspect-square flex-col items-center justify-center gap-2 bg-base-section p-3 text-center focus-visible:focus-ring",
          over && dropTargetOver,
        )}
      >
        <Icon icon={FolderLine} />
        <span className="w-full truncate type-body-md text-base-primary">{folder.name}</span>
        <span className="type-body-sm text-base-secondary">
          {over ? (
            fbs(
              ["Move into ", fbs.param("folderName", folder.name)],
              "Media library: shown on a folder tile while a media item is dragged over it, e.g. 'Move into Summer'",
            )
          ) : (
            <fbt desc="Media library: what a folder tile holds, e.g. '3 items'">
              <fbt:plural count={folder.mediaCount + folder.folderCount} showCount="yes">
                item
              </fbt:plural>
            </fbt>
          )}
        </span>
      </Link>
      <div className="absolute top-1 right-1">
        <Menu.Root>
          <Menu.Trigger
            render={
              <IconButton
                variant="tertiary"
                size="sm"
                aria-label={fbs(
                  ["Actions for ", fbs.param("folderName", folder.name)],
                  "Media library: accessible name of the button that opens a folder's menu, e.g. 'Actions for Summer'",
                )}
              >
                <Icon icon={More2Fill} />
              </IconButton>
            }
          />
          <Menu.Popup>
            <Menu.Item icon={EditLine} onClick={() => onRename(folder)}>
              <fbt desc="Media library folder menu: renames the folder">Rename</fbt>
            </Menu.Item>
            <Menu.Item icon={DeleteBinLine} variant="destructive" onClick={() => onDelete(folder)}>
              <fbt desc="Media library folder menu: deletes the folder">Delete</fbt>
            </Menu.Item>
          </Menu.Popup>
        </Menu.Root>
      </div>
    </li>
  );
}

/** Names a new folder, or renames one. */
export function FolderNameDialog({
  open,
  onClose,
  url,
  method,
  initialName,
  parentId,
  description,
  submitLabel,
}: {
  open: boolean;
  onClose: () => void;
  url: string;
  method: "post" | "put";
  initialName: string;
  parentId: number | null;
  /** Says what else creating it does, e.g. that a dropped item is moved in. */
  description?: string;
  submitLabel?: string;
}) {
  const toast = useToast();
  const form = useForm({ name: initialName, parent_id: parentId });

  return (
    <Dialog.Root open={open} onOpenChange={(next) => !next && onClose()}>
      <Dialog.Popup
        title={
          method === "post"
            ? fbs("New folder", "Media library: title of the dialog that creates a folder")
            : fbs("Rename folder", "Media library: title of the dialog that renames a folder")
        }
        description={description}
      >
        <form
          className="flex flex-col gap-4"
          onSubmit={(event) => {
            event.preventDefault();
            form.submit(method, url, {
              preserveScroll: true,
              onSuccess: () => {
                form.reset();
                onClose();
              },
              // The folder to create it in, or move into, was deleted meanwhile: nothing to fix here.
              onError: (errors) => {
                if (!("folder" in errors)) return;
                onClose();
                toast.add({ children: <ErrorToast message={errors.folder} /> });
              },
            });
          }}
        >
          <Field
            label={fbs("Name", "Media library folder dialog: label of the folder name field")}
            name="name"
            error={form.errors.name}
          >
            <Input
              autoFocus
              autoComplete="off"
              value={form.data.name}
              onChange={(event) => form.setData("name", event.currentTarget.value)}
            />
          </Field>
          <Dialog.Actions>
            <Button type="button" variant="secondary" onClick={onClose}>
              <fbt desc="Media library folder dialog: closes it without saving">Cancel</fbt>
            </Button>
            <Button type="submit" variant="primary" loading={form.processing}>
              {submitLabel ??
                (method === "post" ? (
                  <fbt desc="Media library folder dialog: creates the folder">Create</fbt>
                ) : (
                  <fbt desc="Media library folder dialog: saves the new name">Rename</fbt>
                ))}
            </Button>
          </Dialog.Actions>
        </form>
      </Dialog.Popup>
    </Dialog.Root>
  );
}

/** A toast with one line of text, e.g. why a move failed. */
export function ErrorToast({ message }: { message: string }) {
  return (
    <Inset>
      <p className="type-body-md text-base-primary">{message}</p>
    </Inset>
  );
}
