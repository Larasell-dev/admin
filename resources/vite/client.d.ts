/**
 * Types for the modules the admin() Vite plugin provides to pages in an
 * application's resources/js/admin directory. Reference this file from the
 * application's tsconfig.json "include" (or "files").
 */

declare module "larasell-admin/register" {
  import type { ComponentType } from "react";

  export const page: {
    add(pages: Record<string, ComponentType<any>>): void;
    add(name: string, component: ComponentType<any>): void;
  };
}

declare module "larasell-admin/layouts" {
  import type { ComponentType, ReactNode } from "react";

  export const SidebarLayout: ComponentType<{ children: ReactNode }>;
}

declare module "larasell-admin/components" {
  import type { ComponentType } from "react";

  export type TableColumn = { name: string; label: string; type: "text" };

  export type TableActionConfirmation = {
    title: string;
    message: string;
    confirmButton: string;
    cancelButton: string;
    /** Text the admin must type before the action runs. */
    confirmationText: string | null;
  };

  export type TableAction = {
    name: string;
    label: string;
    method: "get" | "post";
    url: string;
    confirm: TableActionConfirmation | null;
    destructive: boolean;
  };

  export type TableRow = { id: string | number; _actions: TableAction[] } & Record<
    string,
    string | number | null | TableAction[]
  >;

  /** The JSON shape of a Larasell\Admin\Tables\Table. */
  export type TableResource = { title: string; columns: TableColumn[]; rows: TableRow[] };

  export const ResourceTable: ComponentType<{ resource: TableResource; "aria-label"?: string }>;
}
