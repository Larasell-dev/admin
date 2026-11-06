/**
 * Types for @larasell-dev/admin, which the panel's Vite plugins, larasell() for extensions and
 * admin() for an app's resources/js/admin directory, provide at build time. Reference this file
 * from the tsconfig.json "include" (or "files").
 */

declare module "@larasell-dev/admin" {
  import type { BadgeVariant } from "@larasell-dev/ui";
  import type { ComponentType, ReactNode } from "react";

  export type PageLoader = () => Promise<{ default: ComponentType<any> }>;

  /** What the panel gives a section's component. */
  export type SectionComponentProps<Props = unknown> = {
    /** What the section's server side returned from props(), e.g. what it saved before. */
    props: Props;
    /** The name of one of the section's fields: name("note") is "sections[acme-notes][note]". */
    name: (field: string) => string;
    /** The error of one of the section's fields, e.g. error("note"), from its rules or listener. */
    error: (field: string) => string | undefined;
  };

  /** The forms sections can be added to by the server's case names: SectionForm.Product. */
  export const SectionForm: {
    readonly Product: "product";
  };

  /** The forms sections can be added to, the values of the server's SectionForm enum. */
  export type SectionForm = (typeof SectionForm)[keyof typeof SectionForm];

  export type SectionLoader = () => Promise<{
    default: ComponentType<SectionComponentProps<any>>;
  }>;

  export type BootingContext = {
    pages: {
      /**
       * Adds a page that controllers render with Inertia::render($name). Prefix the name with
       * the extension's name, e.g. "acme-wishlists::Wishlist/Show", so extensions can't clash.
       * The loader runs when the page is first opened: () => import("./Pages/Wishlist/Show").
       */
      register(name: string, loader: PageLoader): void;
    };
    sections: {
      /**
       * Adds the component of a section that the server added to a form with
       * Section::add($form, $key). It renders inside the form, where the server placed it, so
       * its fields are saved with the form; name its fields with `name()`. The loader runs when
       * a page with the section is first opened.
       */
      register(form: SectionForm | (string & {}), key: string, loader: SectionLoader): void;
    };
    commands: {
      /**
       * Adds commands to the command menu (Mod+K) on every page. Prefix their ids with the
       * extension's name, e.g. "acme-wishlists.export", so extensions can't clash. A command's
       * `use` hook can read the page to leave itself out where it doesn't apply.
       */
      register(...definitions: CommandDefinition[]): void;
    };
  };

  /** What a rendered label can change with. */
  export type CommandLabelState = { search: string; selected: boolean };

  /** What a command, or an item in a command's nested view, shows and does. */
  export type CommandFields = (
    | { label: string; textValue?: undefined }
    | {
        /** Renders the row between its icon and check mark. No buttons or links inside. */
        label: (state: CommandLabelState) => ReactNode;
        /** The label as text, which search, crumbs and screen readers use instead. */
        textValue: string;
      }
  ) & {
    /** A Remix icon from `@larasell-dev/ui/icons`, shown before the label. */
    icon?: import("react").ComponentProps<typeof import("@larasell-dev/ui").Icon>["icon"];
    /** More words that find it, besides its label. */
    keywords?: string[];
    /** Marks it, e.g. the option in use, with a check on the right. */
    checked?: boolean;
    /** Why it can't be used right now; shown in its place. */
    disabled?: string | false;
  } & (
      | {
          run: () => void;
          items?: undefined;
          placeholder?: undefined;
          searchItemsFromRoot?: undefined;
          filterItems?: undefined;
          loading?: undefined;
        }
      | {
          items: CommandItem[];
          placeholder?: string;
          /** Whether searching the root also finds these items. On unless set to false. */
          searchItemsFromRoot?: boolean;
          /** Whether the view filters its items by its search; off for items that are results already. */
          filterItems?: boolean;
          /** Says the items are loading, e.g. while a server searches, instead of "No results". */
          loading?: boolean;
          run?: undefined;
        }
    );

  export type CommandItem = CommandFields & { id: string };

  /** What the menu tells a command's `use` hook. */
  export type CommandHookState = {
    /** The search of the command's nested view while it is open, e.g. to search a server; else null. */
    search: string | null;
  };

  /** A command: its fields, or a `use` hook that returns them, or null to leave it out. */
  export type CommandDefinition =
    | (CommandItem & { use?: undefined })
    | { id: string; use: (state: CommandHookState) => CommandFields | null };

  /** Types a command defined in its own module, to register in Larasell.booting(). */
  export function defineCommand<T extends CommandDefinition>(definition: T): T;

  /** The text with the ranges, [start, end) like String.slice(), marked, e.g. a search match. */
  export const Highlight: ComponentType<{
    text: string;
    ranges: ReadonlyArray<readonly [start: number, end: number]>;
  }>;

  /** Where a search matched a text, for Highlight: its letters in order, case and gaps ignored. */
  export function matchRanges(text: string, search: string): Array<[start: number, end: number]>;

  /** Offers a command in the command menu while the component is mounted. */
  export function useCommand(definition: CommandDefinition): void;

  export const Larasell: {
    /** Runs the callback just before the panel renders its first page, or right away after. */
    booting(callback: (context: BootingContext) => void): void;
  };

  export const SidebarLayout: ComponentType<{ children: ReactNode }>;

  /** A "badge" column shows its value in a Badge, coloured by the row's `_variants`. */
  export type TableColumn = { name: string; label: string; type: "text" | "badge" };

  export type TableActionConfirmation = {
    title: string;
    message: string;
    confirmButton: string;
    cancelButton: string;
    /** Text the admin must type before the action runs. */
    confirmationText: string | null;
  };

  /** An action on one row of a table. */
  export type TableRowAction = {
    name: string;
    label: string;
    method: "get" | "post";
    url: string;
    confirm: TableActionConfirmation | null;
    destructive: boolean;
    disabled: boolean;
    /** Icon case name, e.g. "DeleteBin"; shown in its line style before the label. */
    icon: string | null;
  };

  export type TableRow = {
    id: string | number;
    _actions: TableRowAction[];
    /** Where the row links to; null when it links nowhere. */
    _link: string | null;
    /** The badge variant of each badge column, by column name. */
    _variants: Record<string, BadgeVariant>;
  } & Record<string, string | number | null | TableRowAction[] | Record<string, BadgeVariant>>;

  /** An action for the whole table, shown in its header or empty state. */
  export type TableAction = {
    label: string;
    method: "get";
    url: string;
    /** Icon case name, e.g. "Add"; shown in its line style before the label. */
    icon: string | null;
  };

  export type TableEmptyState = {
    title: string;
    description: string | null;
    /** Icon case name, e.g. "Book"; @larasell-dev/ui/icons exports it with a Fill or Line suffix. */
    icon: string | null;
    actions: TableAction[];
  };

  /** The JSON shape of a Larasell\Admin\Platform\Tables\Table. */
  export type TableResource = {
    title: string;
    emptyState: TableEmptyState | null;
    headerActions: TableAction[];
    columns: TableColumn[];
    /** The rows on the current page. */
    rows: TableRow[];
    pagination: { page: number; pageSize: number; total: number };
  };

  export const ResourceTable: ComponentType<{ resource: TableResource; "aria-label"?: string }>;

  /** The building blocks of a page: a Header with its title, and the Body below it. */
  export const Page: {
    Header: ComponentType<{
      title: string;
      backLink?: { href: string; label: string; mobileOnly?: boolean };
      actions?: ReactNode;
    }>;
    Body: ComponentType<{ children: ReactNode; width?: "narrow" | "wide" }>;
  };
}
