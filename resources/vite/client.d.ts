/**
 * Types for @larasell-dev/admin, which the panel's Vite plugins, larasell() for extensions and
 * admin() for an app's resources/js/admin directory, provide at build time. Reference this file
 * from the tsconfig.json "include" (or "files").
 */

declare module "@larasell-dev/admin" {
  import type { BadgeVariant } from "@larasell-dev/ui";
  import type { ComponentType, ReactNode } from "react";

  export type PageLoader = () => Promise<{ default: ComponentType<any> }>;

  export type BootingContext = {
    pages: {
      /**
       * Adds a page that controllers render with Inertia::render($name). Prefix the name with
       * the extension's name, e.g. "acme-wishlists::Wishlist/Show", so extensions can't clash.
       * The loader runs when the page is first opened: () => import("./Pages/Wishlist/Show").
       */
      register(name: string, loader: PageLoader): void;
    };
  };

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

  /** The JSON shape of a Larasell\Admin\Tables\Table. */
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
