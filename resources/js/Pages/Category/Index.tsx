import { EmptyState } from "@larasell-dev/ui";
import { FoldersLine } from "@larasell-dev/ui/icons";
import CategoryLayout from "../../Layouts/CategoryLayout";
import SidebarLayout from "../../Layouts/SidebarLayout";

/** Only shown when the store has no categories; otherwise the index redirects to the first one. */
export default function Index() {
  return (
    <div className="flex flex-1 items-center justify-center px-6 py-12">
      <EmptyState
        icon={FoldersLine}
        title="No categories yet"
        description="Categories appear here once your store has some."
      />
    </div>
  );
}

Index.layout = [SidebarLayout, CategoryLayout];
