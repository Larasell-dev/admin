import { Button, Dialog, Field, Select } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import { useState } from "react";

type Node = Larasell.Admin.Media.Data.MediaFolderNodeData;
type Destination = { id: number | null; label: string };

/**
 * Moves a media item to a folder picked from a list: what dragging it does, for the keyboard and
 * screen readers. Folders are listed by their path, as names repeat in different parents.
 */
export function MoveMediaDialog({
  open,
  onClose,
  itemName,
  tree,
  folderId,
  onMove,
}: {
  open: boolean;
  onClose: () => void;
  itemName: string;
  tree: Node[];
  /** The folder the item is in, which isn't offered. */
  folderId: number | null;
  onMove: (folderId: number | null) => void;
}) {
  const [picked, setPicked] = useState<string | null>(null);
  const destinations = moveDestinations(tree, folderId);

  return (
    <Dialog.Root open={open} onOpenChange={(next) => !next && onClose()}>
      <Dialog.Popup
        title={fbs(
          ["Move ", fbs.param("itemName", itemName)],
          "Media library: title of the dialog that moves a media item to another folder, e.g. 'Move boots.png'",
        )}
      >
        <form
          className="flex flex-col gap-4"
          onSubmit={(event) => {
            event.preventDefault();
            const destination = destinations.find((item) => key(item) === picked);
            if (!destination) return;
            onMove(destination.id);
            onClose();
          }}
        >
          <Field
            label={fbs(
              "Folder",
              "Media library move dialog: label of the field that picks the folder",
            )}
            name="folder_id"
          >
            <Select<Destination>
              items={destinations}
              itemToKey={key}
              itemToLabel={(item) => item.label}
              value={picked}
              onValueChange={setPicked}
              filter={{
                placeholder: fbs(
                  "Search folders",
                  "Media library move dialog: placeholder of the search in the folder list",
                ),
              }}
            />
          </Field>
          <Dialog.Actions>
            <Button type="button" variant="secondary" onClick={onClose}>
              <fbt desc="Media library move dialog: closes it without moving">Cancel</fbt>
            </Button>
            <Button type="submit" variant="primary" disabled={picked === null}>
              <fbt desc="Media library move dialog: moves the item to the picked folder">Move</fbt>
            </Button>
          </Dialog.Actions>
        </form>
      </Dialog.Popup>
    </Dialog.Root>
  );
}

function key(destination: Destination): string {
  return destination.id === null ? "top" : String(destination.id);
}

/** The top level and every folder by its path, e.g. "Campaigns / Summer", but the current one. */
function moveDestinations(tree: Node[], folderId: number | null): Destination[] {
  const byId = new Map(tree.map((node) => [node.id, node]));
  const path = (node: Node): string => {
    const parent = node.parentId ? byId.get(node.parentId) : undefined;
    return parent ? `${path(parent)} / ${node.name}` : node.name;
  };
  const folders = tree
    .filter((node) => node.id !== folderId)
    .map((node) => ({ id: node.id, label: path(node) }))
    .sort((a, b) => a.label.localeCompare(b.label));
  const top = {
    id: null,
    label: fbs(
      "All media",
      "Media library move dialog: the top level of the library, in the folder list",
    ),
  };

  return folderId === null ? folders : [top, ...folders];
}
