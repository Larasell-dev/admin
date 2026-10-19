export type NavigationItem = {
  id: string;
  label: string;
  url: string;
  /** NavIcon case name, e.g. "Home"; @larasell-dev/ui/icons exports it with a Fill or Line suffix. */
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

export type TableRow = { id: string | number } & Record<string, string | number | null>;

/** The JSON shape of a Larasell\Admin\Tables\Table. */
export type TableResource = {
  title: string;
  columns: TableColumn[];
  rows: TableRow[];
};
