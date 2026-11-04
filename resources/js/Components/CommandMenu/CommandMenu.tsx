import { Dialog } from "@base-ui/react/dialog";
import { Icon } from "@larasell-dev/ui";
import { ArrowLeftSLine, ArrowRightSLine, CheckLine, SearchLine } from "@larasell-dev/ui/icons";
import { HotkeysProvider, useAppHotkeys, useHotkey } from "@tanstack/react-hotkeys";
import { cn } from "cn";
import { Command, defaultFilter, useCommandState } from "cmdk";
import {
  Fragment,
  type ReactNode,
  useEffect,
  useLayoutEffect,
  useState,
  useSyncExternalStore,
} from "react";
import { overlayExclusive } from "../../utils/hotkeys";
import {
  type CommandDefinition,
  type CommandFields,
  type CommandHookState,
  type CommandItem,
  type CommandLabelState,
  commands,
  textOf,
} from "./commands";

/**
 * A level of the menu: the ids leading to its nested view, none for the root, and its own search
 * and highlighted item, which going back to it restores.
 */
type View = { path: string[]; search: string; highlighted: string };

const rootView: View = { path: [], search: "", highlighted: "" };

/** cmdk tells items apart by a string; the ids leading to an item are unique. */
const valueOf = (path: string[]) => path.join("\u001f");

/** Scores the words an item shows and its keywords, not its value, which is made of ids. */
const filter = (_value: string, search: string, keywords?: string[]) =>
  defaultFilter(keywords?.join(" ") ?? "", search);

/** What the menu shows of the open nested view besides its items. */
type Shown = Pick<CommandFields, "placeholder" | "filterItems" | "loading"> & { label: string };

type Actions = {
  view: View;
  openView: (path: string[], item: CommandFields) => void;
  run: (run: () => void) => void;
  /** Called by the command whose nested view is open, with the view's own fields. */
  showView: (view: Shown) => void;
  /** Called by the command whose nested view is open once that view no longer exists. */
  leaveView: () => void;
};

/**
 * Searches and runs the commands registered with `commands` or `useCommand`. Mod+K opens it.
 * Arrow keys move the highlight while the search keeps focus. A command with items opens a
 * nested view; searching also finds the items of nested views. Escape clears the search, then
 * goes back a level, then closes; Backspace in an empty search also goes back.
 */
export default function CommandMenu() {
  const definitions = useSyncExternalStore(commands.subscribe, commands.all);
  const [open, setOpen] = useState(false);
  const [views, setViews] = useState<View[]>([rootView]);
  const view = views[views.length - 1] ?? rootView;
  const nested = views.length > 1;
  const [shown, setShown] = useState<Shown | null>(null);
  // A view of items that are already the results of its search, e.g. from the server.
  const searched = nested && shown?.filterItems === false;
  // Such a view has nothing to show below its search until there is one.
  const bare = searched && view.search.trim() === "";

  // From the views as they are when it applies: cmdk reports the highlight in the same update
  // that opens a nested view, which must not bring back the view it was opened from.
  const updateView = (changes: Partial<View>) =>
    setViews((current) => [
      ...current.slice(0, -1),
      { ...(current[current.length - 1] ?? rootView), ...changes },
    ]);
  const back = () => setViews((current) => (current.length > 1 ? current.slice(0, -1) : current));
  const leaveView = () => setViews((current) => current.slice(0, 1));

  useHotkey(useAppHotkeys()["commandMenu.open"], () => {
    setViews([rootView]);
    setOpen(true);
  });

  // A command registered with useCommand goes away with its component, even while its view is
  // open; the menu then goes back to the root.
  useEffect(
    () =>
      commands.subscribe(() =>
        setViews((current) => {
          const open = current[current.length - 1]?.path[0];
          return open === undefined || commands.all().some(({ id }) => id === open)
            ? current
            : current.slice(0, 1);
        }),
      ),
    [],
  );

  const actions: Actions = {
    view,
    openView(path, item) {
      const checked = item.items?.find((child) => child.checked);
      const highlighted = checked ? valueOf([...path, checked.id]) : "";
      setViews((current) => [...current, { path, search: "", highlighted }]);
    },
    run(run) {
      setOpen(false);
      run();
    },
    showView(next) {
      setShown((current) =>
        current &&
        (Object.keys(next) as Array<keyof Shown>).every((key) => current[key] === next[key])
          ? current
          : next,
      );
    },
    leaveView,
  };

  return (
    <Dialog.Root
      open={open}
      onOpenChange={(next, details) => {
        if (!next && details.reason === "escape-key") {
          if (view.search !== "") {
            details.cancel();
            updateView({ search: "" });
            return;
          }
          if (nested) {
            details.cancel();
            back();
            return;
          }
        }
        setOpen(next);
      }}
      // Closing forgets every search.
      onOpenChangeComplete={(isOpen) => !isOpen && setViews([rootView])}
    >
      <Dialog.Portal>
        <Dialog.Backdrop className="fixed inset-0 z-40 bg-overlay backdrop-blur-overlay" />
        <Dialog.Popup
          aria-label="Command menu"
          className="fixed top-[15vh] left-1/2 z-50 w-[min(36rem,calc(100vw-2rem))] -translate-x-1/2 border border-border bg-elevated bg-clip-padding shadow-elevated outline-none"
        >
          {/* Suspends the page's hotkeys while open, like other overlays. */}
          <HotkeysProvider scope="command-menu" exclusive={overlayExclusive} enabled={open}>
            <Command
              // A cmdk of its own for each level, which starts on the level's highlight. A shared
              // one would highlight the first item once the search changes with the level.
              key={views.length}
              label="Command menu"
              filter={filter}
              shouldFilter={!searched}
              value={view.highlighted || undefined}
              onValueChange={(highlighted) => updateView({ highlighted })}
              onKeyDown={(event) => {
                // The page's hotkeys are suspended, so Mod+K closes the menu here.
                if (event.key.toLowerCase() === "k" && (event.metaKey || event.ctrlKey)) {
                  event.preventDefault();
                  setOpen(false);
                }
              }}
            >
              <div
                className={cn("flex items-center gap-3 px-4.5", !bare && "border-b border-border")}
              >
                {nested ? (
                  <button
                    type="button"
                    aria-label={shown ? `Back from ${shown.label}` : "Back"}
                    onClick={back}
                    // Otherwise cmdk takes Enter for running the highlighted item.
                    onKeyDown={(event) => {
                      if (event.key === "Enter" || event.key === " ") event.stopPropagation();
                    }}
                    className="-m-1 flex shrink-0 p-1 text-mauve-500 hover:text-mauve-950 focus-visible:focus-ring dark:hover:text-mauve-50"
                  >
                    <span className="flex translate-y-px">
                      <Icon icon={ArrowLeftSLine} size={4} />
                    </span>
                  </button>
                ) : (
                  <span aria-hidden className="flex shrink-0 text-mauve-500">
                    <Icon icon={SearchLine} size={4} />
                  </span>
                )}
                <Command.Input
                  // Each level's cmdk has its own search.
                  autoFocus
                  value={view.search}
                  onValueChange={(search) => updateView({ search })}
                  onKeyDown={(event) => {
                    if (event.key === "Backspace" && view.search === "" && nested) {
                      event.preventDefault();
                      back();
                    }
                  }}
                  placeholder={nested ? (shown?.placeholder ?? "Search") : "Search commands"}
                  className="h-11 min-w-0 flex-1 bg-transparent type-body-md text-mauve-950 outline-none placeholder:text-mauve-500 dark:text-mauve-50"
                />
              </div>
              <Command.List
                // Hidden, not left out: the commands' hooks run inside it, also while it's bare.
                hidden={bare}
                className="max-h-80 scroll-py-1.5 overflow-y-auto p-1.5"
              >
                <Command.Empty className="px-3 py-6 text-center type-body-md text-mauve-500">
                  {nested && shown?.loading ? "Searching…" : "No results"}
                </Command.Empty>
                {definitions.map((definition) => (
                  // Keyed by kind too, so a command never switches between calling a hook and not.
                  <Entry
                    key={`${definition.id}-${definition.use ? "hook" : "fields"}`}
                    definition={definition}
                    actions={actions}
                  />
                ))}
              </Command.List>
            </Command>
          </HotkeysProvider>
        </Dialog.Popup>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

function Entry({ definition, actions }: { definition: CommandDefinition; actions: Actions }) {
  return definition.use ? (
    <HookEntry id={definition.id} use={definition.use} actions={actions} />
  ) : (
    <Rows command={definition} actions={actions} />
  );
}

/** Its own component, so each command's hook keeps its state as other commands come and go. */
function HookEntry({
  id,
  use,
  actions,
}: {
  id: string;
  use: (state: CommandHookState) => CommandFields | null;
  actions: Actions;
}) {
  const fields = use({ search: actions.view.path[0] === id ? actions.view.search : null });
  const gone = fields === null && actions.view.path[0] === id;

  useEffect(() => {
    if (gone) actions.leaveView();
  });

  return fields && <Rows command={{ ...fields, id } as CommandItem} actions={actions} />;
}

type Row = { item: CommandItem; path: string[]; crumbs: string[] };

/** The items to the path, and below them, while searching, the items of their nested views. */
function rows(items: CommandItem[], path: string[], crumbs: string[], deep: boolean): Row[] {
  return items.flatMap((item) => {
    const row = { item, path: [...path, item.id], crumbs };
    // A disabled command can't be opened, so its items aren't found either, and neither are those
    // of a command whose items are only searched in its own view.
    return deep && item.items && !item.disabled && item.searchItemsFromRoot !== false
      ? [row, ...rows(item.items, row.path, [...crumbs, textOf(item)], deep)]
      : [row];
  });
}

/** Finds the item at the ids below the command, or null once it's gone or can't be opened. */
function find(item: CommandItem, path: string[]): CommandItem | null {
  if (item.disabled || !item.items) return null;
  if (path.length === 0) return item;

  const child = item.items.find(({ id }) => id === path[0]);
  return child ? find(child, path.slice(1)) : null;
}

/** The rows a command adds to the open view: itself at the root, or the items of its nested view. */
function Rows({ command, actions }: { command: CommandItem; actions: Actions }) {
  const { view } = actions;
  const atRoot = view.path.length === 0;
  const own = view.path[0] === command.id;
  const node = own ? find(command, view.path.slice(1)) : null;

  useLayoutEffect(() => {
    if (node) {
      actions.showView({
        label: textOf(node),
        placeholder: node.placeholder,
        filterItems: node.filterItems,
        loading: node.loading,
      });
    }
  });
  useEffect(() => {
    if (own && !node) actions.leaveView();
  });

  if (!atRoot && !node) return null;

  const items = node?.items ?? [command];
  const basePath = node ? view.path : [];

  return rows(items, basePath, [], view.search !== "").map(({ item, path, crumbs }) => (
    <Command.Item
      key={valueOf(path)}
      value={valueOf(path)}
      keywords={[...crumbs, textOf(item), ...(item.keywords ?? [])]}
      disabled={!!item.disabled}
      onSelect={() => (item.items ? actions.openView(path, item) : actions.run(item.run))}
      className={cn(
        "flex min-h-9 cursor-default items-center gap-3 px-3 py-1.5 select-none",
        "type-body-md text-mauve-950 dark:text-mauve-50",
        "data-[disabled=true]:text-mauve-400 dark:data-[disabled=true]:text-mauve-500",
        "data-[selected=true]:bg-mauve-100 dark:data-[selected=true]:bg-mauve-800",
      )}
    >
      {/* An item without an icon keeps its place, so every label starts in the same column. */}
      <span aria-hidden className="flex size-4 shrink-0">
        {item.icon && <Icon icon={item.icon} size={4} />}
      </span>
      <span className="flex min-w-0 flex-1 flex-wrap items-center gap-1">
        {crumbs.map((crumb) => (
          <Fragment key={crumb}>
            <span className="shrink-0 text-mauve-500">{crumb}</span>
            <span aria-hidden className="flex shrink-0 text-mauve-400">
              <Icon icon={ArrowRightSLine} />
            </span>
          </Fragment>
        ))}
        {typeof item.label === "string" ? (
          <span>{item.label}</span>
        ) : (
          <>
            {/* Named by its text value, which says what the rendered label shows. */}
            <span aria-hidden className="min-w-0">
              <RenderedLabel label={item.label} value={valueOf(path)} />
            </span>
            <span className="sr-only">{item.textValue}</span>
          </>
        )}
      </span>
      {item.disabled ? (
        <span className="shrink-0 type-body-sm text-mauve-500">{item.disabled}</span>
      ) : (
        item.checked && (
          <>
            <span aria-hidden className="flex shrink-0">
              <Icon icon={CheckLine} />
            </span>
            <span className="sr-only">, current</span>
          </>
        )
      )}
    </Command.Item>
  ));
}

/**
 * Renders a label function from cmdk's own state. The menu's view only learns the highlight once
 * it moves, not when cmdk picks the first item of a search.
 */
function RenderedLabel({
  label,
  value,
}: {
  label: (state: CommandLabelState) => ReactNode;
  value: string;
}) {
  const search = useCommandState((state) => state.search);
  const selected = useCommandState((state) => state.value === value);

  return label({ search, selected });
}
