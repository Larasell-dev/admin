import type { Icon } from "@larasell-dev/ui";
import { type ComponentProps, useEffect, useLayoutEffect, useRef } from "react";

/** What a command, or an item in a command's nested view, shows and does. */
export type CommandFields = {
  label: string;
  /** A Remix icon from `@larasell-dev/ui/icons`, shown before the label. */
  icon?: ComponentProps<typeof Icon>["icon"];
  /** More words that find it, besides its label. */
  keywords?: string[];
  /** Marks it, e.g. the theme in use, with a check on the right. */
  checked?: boolean;
  /** Why it can't be used right now; shown in its place. A disabled command can't be opened either. */
  disabled?: string | false;
} & (
  | { run: () => void; items?: undefined; placeholder?: undefined }
  | {
      /** Opens a nested view with these items. */
      items: CommandItem[];
      /** The search's placeholder in the nested view. */
      placeholder?: string;
      run?: undefined;
    }
);

export type CommandItem = CommandFields & {
  /** Unique among its siblings. */
  id: string;
};

/**
 * A command in the menu: its fields, or a `use` hook the menu calls while it is open, which can
 * read the page and other state and returns the fields, or null to leave the command out.
 */
export type CommandDefinition =
  | (CommandItem & { use?: undefined })
  | { id: string; use: () => CommandFields | null };

/** Types a command defined once, e.g. in its own module, to register with commands.register(). */
export function defineCommand<T extends CommandDefinition>(definition: T): T {
  return definition;
}

function createCommandRegistry() {
  let definitions: CommandDefinition[] = [];
  const listeners = new Set<() => void>();
  const notify = () => listeners.forEach((listener) => listener());

  return {
    /** Every registered command, in the order they were registered. */
    all: () => definitions,
    subscribe: (listener: () => void) => {
      listeners.add(listener);
      return () => {
        listeners.delete(listener);
      };
    },
    /**
     * Adds commands to the menu until the returned function removes them again. Ids are unique,
     * so registering one twice throws instead of one command silently replacing another.
     */
    register(...added: CommandDefinition[]): () => void {
      for (const definition of added) {
        if (definitions.some((existing) => existing.id === definition.id)) {
          throw new Error(`A command with the id [${definition.id}] is already registered.`);
        }
      }

      definitions = [...definitions, ...added];
      notify();

      const ids = added.map((definition) => definition.id);
      return () => {
        definitions = definitions.filter((definition) => !ids.includes(definition.id));
        notify();
      };
    },
    /** Swaps a registered command for a newer version of it, keeping its place. */
    update(definition: CommandDefinition) {
      if (!definitions.some((existing) => existing.id === definition.id)) return;

      definitions = definitions.map((existing) =>
        existing.id === definition.id ? definition : existing,
      );
      notify();
    },
  };
}

/** The commands the menu offers. Register those that are always there once, at boot. */
export const commands = createCommandRegistry();

/**
 * Offers a command while the component is mounted, e.g. one that acts on what a page shows.
 * The menu always uses the definition of the latest render, so it needs no dependencies.
 */
export function useCommand(definition: CommandDefinition): void {
  const latest = useRef(definition);

  // Before the effect below, so a command registered on mount is already the latest one.
  useLayoutEffect(() => {
    latest.current = definition;
    commands.update(definition);
  });

  useEffect(() => commands.register(latest.current), [definition.id]);
}
