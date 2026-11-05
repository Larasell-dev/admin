import { Link } from "@inertiajs/react";
import { Button, EmptyState, Icon } from "@larasell-dev/ui";
import { AddLine, FoldersLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import CategoryLayout from "../../Layouts/Category/CategoryLayout";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Category.Data.CategoriesPageData;

/** Only shown when the store has no categories; otherwise the index redirects to the first one. */
export default function Index({ createUrl }: Props) {
  return (
    <div className="flex flex-1 items-center justify-center px-6 py-12">
      <EmptyState
        icon={FoldersLine}
        title={fbs(
          "No categories yet",
          "Categories page: shown when the store has no categories at all",
        )}
        description={fbs(
          "Add a category to start organizing your products.",
          "Categories page: below 'No categories yet', suggests creating the first category",
        )}
      >
        <Button render={<Link href={createUrl} prefetch />}>
          <Icon icon={AddLine} />
          <fbt desc="Categories page, while there are no categories: button that opens the form to create the first category">
            Add category
          </fbt>
        </Button>
      </EmptyState>
    </div>
  );
}

Index.layout = [SidebarLayout, CategoryLayout];
