import { type DragEvent, useEffect, useState } from "react";

/**
 * The data type a dragged media tile carries, holding its ID. Drop targets accept only drags with
 * it, so files from the computer and text or links dragged from the page pass them by.
 */
const MEDIA_TYPE = "application/x-larasell-media";

function isMediaDrag(event: DragEvent | globalThis.DragEvent): boolean {
  return Array.from(event.dataTransfer?.types ?? []).includes(MEDIA_TYPE);
}

/** Makes a media tile draggable onto the library's drop targets. */
export function mediaDragSource(mediaId: number) {
  return {
    draggable: true,
    onDragStart: (event: DragEvent) => {
      event.dataTransfer.setData(MEDIA_TYPE, String(mediaId));
      event.dataTransfer.effectAllowed = "move";
    },
  };
}

/**
 * Keeps a dropped media item from doing what the browser does with a dropped link, such as
 * opening it, wherever it lands outside a drop target. Targets cancel dragover themselves first.
 */
export function usePreventStrayMediaDrops() {
  useEffect(() => {
    const onDragOver = (event: globalThis.DragEvent) => {
      if (!isMediaDrag(event) || event.defaultPrevented) return;
      event.preventDefault();
      event.dataTransfer!.dropEffect = "none";
    };
    const onDrop = (event: globalThis.DragEvent) => {
      if (isMediaDrag(event)) event.preventDefault();
    };
    document.addEventListener("dragover", onDragOver);
    document.addEventListener("drop", onDrop);
    return () => {
      document.removeEventListener("dragover", onDragOver);
      document.removeEventListener("drop", onDrop);
    };
  }, []);
}

/**
 * Makes an element a target for dragged media items: `over` while one is above it, and `onDrop`
 * with its ID when it lands. dragenter and dragleave fire for every child the pointer crosses, so
 * it counts them instead of trusting the last one. Without `enabled`, e.g. while a dialog is
 * open, the element ignores drags.
 */
export function useMediaDropTarget(onDrop: (mediaId: number) => void, enabled = true) {
  const [depth, setDepth] = useState(0);
  const dragging = depth > 0;

  // A drag that ends anywhere, or a target that turns off, leaves no highlight behind.
  useEffect(() => {
    if (!dragging) return;
    const reset = () => setDepth(0);
    document.addEventListener("dragend", reset);
    document.addEventListener("drop", reset);
    return () => {
      document.removeEventListener("dragend", reset);
      document.removeEventListener("drop", reset);
    };
  }, [dragging]);
  // A target turned off mid-drag starts over when it's on again.
  if (!enabled && depth !== 0) setDepth(0);

  if (!enabled) return { over: false, dropProps: {} };

  return {
    over: dragging,
    dropProps: {
      onDragEnter: (event: DragEvent) => {
        if (!isMediaDrag(event)) return;
        event.preventDefault();
        setDepth((count) => count + 1);
      },
      onDragOver: (event: DragEvent) => {
        if (!isMediaDrag(event)) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = "move";
      },
      onDragLeave: (event: DragEvent) => {
        if (!isMediaDrag(event)) return;
        setDepth((count) => Math.max(0, count - 1));
      },
      onDrop: (event: DragEvent) => {
        if (!isMediaDrag(event)) return;
        event.preventDefault();
        setDepth(0);
        const mediaId = Number(event.dataTransfer.getData(MEDIA_TYPE));
        if (mediaId > 0) onDrop(mediaId);
      },
    },
  };
}
