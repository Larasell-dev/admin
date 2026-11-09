import { AlertDialog } from "@larasell-dev/ui";
import {
  type ComponentProps,
  type ReactElement,
  type ReactNode,
  cloneElement,
  createContext,
  Fragment,
  use,
  useLayoutEffect,
  useState,
  useSyncExternalStore,
} from "react";
import { assertDefined } from "ts-extras";

type RootProps = ComponentProps<typeof AlertDialog.Root>;
type PopupProps = ComponentProps<typeof AlertDialog.Popup>;

type Entry = {
  open: boolean;
  onOpenChange?: (open: boolean) => void;
  popup: PopupProps;
  key: number;
  /** Set once the page that rendered the dialog has unmounted. */
  left: boolean;
};

/**
 * The pages publish their popups here and the host in the persistent layout draws them, so a
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

const RootContext = createContext<
  | {
      open: boolean;
      setOpen: (open: boolean) => void;
    }
  | undefined
>(undefined);

function useRoot(part: string) {
  const root = use(RootContext);
  assertDefined(root, `PersistentAlertDialog.${part} must be inside a PersistentAlertDialog.Root.`);
  return root;
}

/**
 * Like `AlertDialog.Root`, but the layout draws its popup instead of the page, so the dialog
 * animates out even when confirming it visits another page. The root and trigger stay in the page.
 */
function Root({ open: openProp, defaultOpen = false, onOpenChange, children }: RootProps) {
  const [uncontrolled, setUncontrolled] = useState(defaultOpen);
  const open = openProp ?? uncontrolled;
  const setOpen = (next: boolean) => {
    setUncontrolled(next);
    onOpenChange?.(next);
  };

  return <RootContext value={{ open, setOpen }}>{children}</RootContext>;
}

/** Opens the dialog. Like `AlertDialog.Trigger`, e.g. `render={<Button variant="destructive" />}`. */
function Trigger({
  render = <button type="button" />,
  children,
}: {
  render?: ReactElement<{ onClick?: () => void }>;
  children?: ReactNode;
}) {
  const { open, setOpen } = useRoot("Trigger");

  return cloneElement(
    render,
    { onClick: () => setOpen(true), "aria-haspopup": "dialog", "aria-expanded": open } as object,
    children,
  );
}

/**
 * Like `AlertDialog.Popup`, drawn by the PersistentAlertDialogHost that SidebarLayout renders.
 * Its content reads the layout's context, not the page's.
 */
function Popup(popup: PopupProps) {
  const { open, setOpen } = useRoot("Popup");
  const [owner] = useState(() => ({}));

  // Republished on every render, before paint, so typing into the dialog doesn't lag a frame.
  useLayoutEffect(() => {
    const key = store.entries.get(owner)?.key ?? store.nextKey++;
    // A new dialog takes the place of those whose pages left and that have closed by now.
    if (!store.entries.has(owner)) {
      for (const [other, entry] of store.entries) if (entry.left) store.entries.delete(other);
    }
    store.entries.set(owner, { open, onOpenChange: setOpen, popup, key, left: false });
    store.changed();
  });

  // Closes it without dropping its content, which stays as the page last rendered it.
  useLayoutEffect(
    () => () => {
      const entry = store.entries.get(owner);
      if (!entry) return;
      store.entries.set(owner, { ...entry, open: false, onOpenChange: undefined, left: true });
      store.changed();
    },
    [owner],
  );

  return null;
}

/**
 * The popup for the dialog it's in: the persistent one inside a PersistentAlertDialog.Root,
 * otherwise `AlertDialog.Popup`. Lets content such as ConfirmDialog.Popup work in either root.
 */
export function AnyAlertDialogPopup(props: PopupProps) {
  return use(RootContext) ? <Popup {...props} /> : <AlertDialog.Popup {...props} />;
}

/**
 * An alert dialog that animates out even when confirming it visits another page. Use it like
 * `AlertDialog`: the same parts, with Root, Trigger and Popup swapped for ones that hand the popup
 * to the layout.
 */
const PersistentAlertDialog = { ...AlertDialog, Root, Trigger, Popup };
export default PersistentAlertDialog;

/** Draws the PersistentAlertDialogs' popups. Render it once in a persistent layout. */
export function PersistentAlertDialogHost() {
  const entries = useSyncExternalStore(store.subscribe, () => store.snapshot);

  return entries.map(({ key, open, onOpenChange, popup }) => (
    <Fragment key={key}>
      <AlertDialog.Root open={open} onOpenChange={onOpenChange}>
        <AlertDialog.Popup {...popup} />
      </AlertDialog.Root>
    </Fragment>
  ));
}
