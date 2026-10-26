import { Link } from "@inertiajs/react";
import { Button, EmptyState, Icon } from "@larasell-dev/ui";
import { AddLine, FoldersLine } from "@larasell-dev/ui/icons";
import CategoryLayout from "../../Layouts/CategoryLayout";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.CategoriesPageData;

/** Only shown when the store has no categories; otherwise the index redirects to the first one. */
export default function Index({ createUrl }: Props) {
  return (
    <div className="flex flex-1 items-center justify-center px-6 py-12">
      <EmptyState
        icon={FoldersLine}
        title="No categories yet"
        description="Add a category to start organizing your products."
      >
        <Button render={<Link href={createUrl} prefetch />}>
          <Icon icon={AddLine} />
          Add category
        </Button>
      </EmptyState>
    </div>
  );
}

Index.layout = [SidebarLayout, CategoryLayout];
