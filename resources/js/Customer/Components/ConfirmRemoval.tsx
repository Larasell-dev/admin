import { useState } from "react";
import ConfirmDialog from "../../Platform/Panel/Components/ConfirmDialog";
import Freeze from "../../Platform/Panel/Components/Freeze";

export type Removal = { url: string; title: string; message: string; confirmButton: string };

/**
 * Confirms removing one item of a list, e.g. an address. Render `dialog` once, outside the list's
 * map(), and open it with `confirm(removal)`: it outlives the row it removes and keeps its last
 * text while it closes, so it can animate out.
 */
export function useConfirmRemoval() {
  const [removal, setRemoval] = useState<Removal | null>(null);

  const dialog = (
    <ConfirmDialog.Root open={removal !== null} onOpenChange={(open) => !open && setRemoval(null)}>
      <Freeze frozen={removal === null}>
        {removal && (
          <ConfirmDialog.Popup
            title={removal.title}
            description={removal.message}
            action={removal.url}
            confirmButton={removal.confirmButton}
          />
        )}
      </Freeze>
    </ConfirmDialog.Root>
  );

  return { confirm: setRemoval, dialog };
}
