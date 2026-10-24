import { router, useFormContext } from "@inertiajs/react";
import { Button } from "@larasell-dev/ui";
import { AnimatePresence, MotionConfig, motion, useAnimate, useIsPresent } from "motion/react";
import { type RefObject, useEffect, useRef, useState, useSyncExternalStore } from "react";
import { createPortal } from "react-dom";
import { assertDefined } from "ts-extras";

type Position = { left: string; bottom: string };

type ToastState = {
  /** The UnsavedChangesToast that published this state. */
  owner: object;
  dirty: boolean;
  processing: boolean;
  position: Position;
  save: () => void;
  cancel: () => void;
};

/**
 * The form publishes its state here and the host in the persistent layout draws it, so the
 * toast can still animate out when the form unmounts, e.g. when saving leads to another page.
 */
const store = {
  state: null as ToastState | null,
  listeners: new Set<() => void>(),
  wobble: () => {},
  set(state: ToastState | null) {
    store.state = state;
    store.listeners.forEach((listener) => listener());
  },
  // An arrow function, so useSyncExternalStore can call it without `this`.
  subscribe: (listener: () => void) => {
    store.listeners.add(listener);
    return () => store.listeners.delete(listener);
  },
};

/**
 * Saves or discards the surrounding Inertia `<Form>` once it has unsaved changes. It floats at
 * the bottom center of the viewport, or of `container` when given, outside the form's layout.
 * While there are unsaved changes it blocks leaving the page and wobbles to point at itself.
 * An UnsavedChangesToastHost, which SidebarLayout renders, draws it.
 */
export default function UnsavedChangesToast({
  container,
}: {
  container?: RefObject<HTMLElement | null>;
}) {
  const form = useFormContext();
  assertDefined(form, "UnsavedChangesToast must be rendered inside an Inertia <Form>.");

  const [owner] = useState(() => ({}));
  // Set while the toast's own Save starts its visit, so that one isn't blocked.
  const saving = useRef(false);

  useEffect(() => {
    if (!form.isDirty) return;

    const stopVisits = router.on("before", (event) => {
      // Prefetching only loads the page in the background; it doesn't leave this one.
      if (saving.current || event.detail.visit.prefetch) return;

      store.wobble();
      return false;
    });

    // Leaving the admin panel itself (closing the tab, reloading) gets the browser's prompt.
    const askBeforeUnload = (event: BeforeUnloadEvent) => event.preventDefault();
    window.addEventListener("beforeunload", askBeforeUnload);

    return () => {
      stopVisits();
      window.removeEventListener("beforeunload", askBeforeUnload);
    };
  }, [form.isDirty]);

  // Without a container it centers in the viewport.
  const [position, setPosition] = useState<Position>({ left: "50%", bottom: "0px" });

  useEffect(() => {
    const element = container?.current;
    if (!element) return;

    const update = () => {
      const rect = element.getBoundingClientRect();
      setPosition({
        left: `${rect.left + rect.width / 2}px`,
        bottom: `${window.innerHeight - rect.bottom}px`,
      });
    };

    update();
    const observer = new ResizeObserver(update);
    observer.observe(element);
    window.addEventListener("resize", update);

    return () => {
      observer.disconnect();
      window.removeEventListener("resize", update);
    };
  }, [container]);

  // Republished on every render, so the host always calls the form's current methods.
  useEffect(() => {
    store.set({
      owner,
      dirty: form.isDirty,
      processing: form.processing,
      position,
      save() {
        saving.current = true;
        try {
          form.submit();
        } finally {
          saving.current = false;
        }
      },
      cancel: () => form.resetAndClearErrors(),
    });
  });

  // Only clears its own state; the next page's toast may already have published.
  useEffect(
    () => () => {
      if (store.state?.owner === owner) store.set(null);
    },
    [owner],
  );

  return null;
}

/** Draws the UnsavedChangesToast of the current page. Render it once in a persistent layout. */
export function UnsavedChangesToastHost() {
  const toast = useSyncExternalStore(store.subscribe, () => store.state);

  // AnimatePresence keeps the last rendered toast while it animates out, even once its form is gone.
  return createPortal(
    <MotionConfig reducedMotion="user">
      <AnimatePresence>
        {toast?.dirty && (
          <motion.div
            role="region"
            aria-label="Unsaved changes"
            style={{
              left: toast.position.left,
              bottom: `calc(${toast.position.bottom} + 1.5rem)`,
              x: "-50%",
            }}
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: 12 }}
            transition={{ type: "spring", bounce: 0.05, visualDuration: 0.16 }}
            // Above the page, but under dialogs, menus and popups (z-50).
            className="fixed z-30"
          >
            <ToastContent toast={toast} />
          </motion.div>
        )}
      </AnimatePresence>
    </MotionConfig>,
    document.body,
  );
}

function ToastContent({ toast }: { toast: ToastState }) {
  const [scope, animate] = useAnimate<HTMLDivElement>();
  // A leaving toast may belong to a form that is gone, so it no longer takes clicks.
  const present = useIsPresent();

  useEffect(() => {
    const wobble = () => {
      if (scope.current) {
        animate(
          scope.current,
          { x: [0, -10, 10, -6, 6, -2, 0] },
          { duration: 0.4, ease: "easeInOut" },
        );
      }
    };
    store.wobble = wobble;
    // A toast that is still leaving must not take the wobble from the one that replaced it.
    return () => {
      if (store.wobble === wobble) store.wobble = () => {};
    };
  }, [animate, scope]);

  return (
    // The wobble moves this inner box, since the outer one's x centers the toast.
    <div
      ref={scope}
      inert={!present}
      className="flex items-center gap-6 border border-border bg-white bg-clip-padding py-1.5 pr-1.5 pl-5 shadow-lg dark:bg-mauve-900"
    >
      <p className="type-body-md whitespace-nowrap text-mauve-950 dark:text-mauve-50">
        Unsaved changes
      </p>
      <div className="flex gap-1">
        <Button type="button" variant="tertiary" disabled={toast.processing} onClick={toast.cancel}>
          Cancel
        </Button>
        <Button type="button" loading={toast.processing} onClick={toast.save}>
          Save
        </Button>
      </div>
    </div>
  );
}
