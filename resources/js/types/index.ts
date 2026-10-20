export type NavigationItem = {
  id: string;
  label: string;
  url: string;
  /** Icon case name, e.g. "Home"; @larasell-dev/ui/icons exports it with a Fill or Line suffix. */
  icon: string;
};

export type AdminSummary = { name: string; email: string; avatarUrl: string | null };

export type SharedProps = {
  navigation: NavigationItem[];
  admin: AdminSummary | null;
  profileUrl: string;
  logoutUrl: string;
};

export type Page<TProps extends object = Record<string, never>> = SharedProps & TProps;

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
  disabled: boolean;
  /** Icon case name, e.g. "DeleteBin"; shown in its line style before the label. */
  icon: string | null;
};

export type TableRow = {
  id: string | number;
  _actions: TableAction[];
  /** Where the row links to; null when it links nowhere. */
  _link: string | null;
} & Record<string, string | number | null | TableAction[]>;

/** The JSON shape of a Larasell\Admin\Tables\Table. */
export type TableEmptyState = {
  title: string;
  description: string | null;
  /** Icon case name, e.g. "Book"; @larasell-dev/ui/icons exports it with a Fill or Line suffix. */
  icon: string | null;
};

export type TableResource = {
  title: string;
  /** Shown when there are no rows; null for the default. */
  emptyState: TableEmptyState | null;
  columns: TableColumn[];
  /** The rows on the current page. */
  rows: TableRow[];
  pagination: TablePagination;
};

export type TablePagination = {
  /** 1-based page number. */
  page: number;
  pageSize: number;
  /** Total number of rows across all pages. */
  total: number;
};
