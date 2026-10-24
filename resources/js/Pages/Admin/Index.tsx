import ResourceTable from "../../Components/ResourceTable";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.AdminsPageData;

export default function Index({ admins }: Props) {
  return (
    <div className="flex h-[calc(100dvh-3.5rem)] flex-col lg:h-dvh">
      <header className="flex h-14 shrink-0 items-center gap-4 border-b border-border px-6">
        <h2 className="flex-1 truncate type-body-md text-mauve-950 dark:text-mauve-50">
          {admins.title}
        </h2>
      </header>
      <ResourceTable resource={admins} />
    </div>
  );
}

Index.layout = [SidebarLayout];
