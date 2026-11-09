import { AlertDialog } from "@larasell-dev/ui";
import {
  type ComponentProps,
  Fragment,
  useLayoutEffect,
  useState,
  useSyncExternalStore,
} from "react";

type RootProps = ComponentProps<typeof AlertDialog.Root>;

type Entry = RootProps & {
  /** Set once the page that rendered the dialog has unmounted. */
  left: boolean;
  key: number;
};

/**
 * The pages publish their dialogs here and the host in the persistent layout draws them, so a
 * dialog can still animate out once its page is gone, e.g. after confirming leads to another page.
 */
const store = {
  entries: new Map<object, Entry>(),
  snapshot: [] as Entry[],
  listeners: new Set<() => void>(),
  nextKey: 0,
  changed() {
    store.snapshot = [...store.entries.values()];
    store.listeners.forEach((listener) => listener());
  },
  subscribe: (listener: () => void) => {
    store.listeners.add(listener);
    return () => store.listeners.delete(listener);
  },
};

/**
 * An `AlertDialog.Root` that the layout renders instead of the page, so it animates out even when
 * confirming it visits another page. Its children render in the layout: they read the layout's
 * context, not the page's, and show outside the page, so keep the trigger out of it. A PersistentAlertDialogHost, which SidebarLayout renders, draws it.
 */
export default function PersistentAlertDialog(props: RootProps) {
  const [owner] = useState(() => ({}));

  // Republished on every render, before paint, so typing into the dialog doesn't lag a frame.
  useLayoutEffect(() => {
    const key = store.entries.get(owner)?.key ?? store.nextKey++;
    // A new dialog takes the place of those whose pages left and that have closed by now.
    if (!store.entries.has(owner)) {
      for (const [other, entry] of store.entries) if (entry.left) store.entries.delete(other);
    }
    store.entries.set(owner, { ...props, left: false, key });
    store.changed();
  });

  // Closes it without dropping its content, which stays as the page last rendered it.
  useLayoutEffect(
    () => () => {
      const entry = store.entries.get(owner);
      if (!entry) return;
      store.entries.set(owner, { ...entry, open: false, left: true, onOpenChange: undefined });
      store.changed();
    },
    [owner],
  );

  return null;
}

/** Draws the PersistentAlertDialogs of the pages. Render it once in a persistent layout. */
export function PersistentAlertDialogHost() {
  const entries = useSyncExternalStore(store.subscribe, () => store.snapshot);

  return entries.map(({ left: _left, key, ...props }) => (
    <Fragment key={key}>
      <AlertDialog.Root {...props} />
    </Fragment>
  ));
}
