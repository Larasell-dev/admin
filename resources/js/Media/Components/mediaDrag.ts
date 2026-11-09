import { pointerIntersection } from "@dnd-kit/collision";
import { type DragEndEvent, useDraggable, useDroppable } from "@dnd-kit/react";
import { useId } from "react";

/**
 * Makes a media tile draggable onto the library's drop targets. It uses dnd-kit's pointer
 * sensors, as the category tree does, and not the browser's own drag: a mouse drags once it moved
 * a little, so a click still opens the item, and touch waits for a long press, so a tap opens it
 * and a swipe scrolls. A native link drag would also make iOS Safari open the link on a long press.
 */
export function useMediaDraggable(mediaId: number) {
  const { ref, isDragging } = useDraggable({ id: `media-${mediaId}`, data: { mediaId } });
  return { ref, dragging: isDragging };
}

/**
 * Makes an element a target for dragged media items: `over` while one is above it, and `onDrop`
 * with its ID when it lands. Without `enabled`, e.g. while a dialog is open, it takes no drops.
 * Only the pointer counts: a dragged tile is large and would also overlap the targets beside it.
 */
export function useMediaDropTarget(onDrop: (mediaId: number) => void, enabled = true) {
  const { ref, isDropTarget } = useDroppable({
    id: useId(),
    disabled: !enabled,
    data: { onDrop },
    collisionDetector: pointerIntersection,
  });
  return { ref, over: enabled && isDropTarget };
}

/** Hands a dropped media item to the target it landed on; the DragDropProvider's onDragEnd. */
export function dropMedia(event: DragEndEvent) {
  const { source, target } = event.operation;
  if (event.canceled || !source || !target) return;
  (target.data.onDrop as (mediaId: number) => void)(source.data.mediaId as number);
}
