import { EmptyState } from "@larasell-dev/ui";
import { ShoppingBagLine } from "@larasell-dev/ui/icons";
import ResourceTable from "../../Components/ResourceTable";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Product.Data.View.ProductsPageData;

export default function Index({ products }: Props) {
  return (
    <div className="flex h-[calc(100dvh-3.5rem)] flex-col lg:h-dvh">
      <header className="flex h-14 shrink-0 items-center border-b border-border px-6">
        <h1 className="type-body-md text-mauve-950 dark:text-mauve-50">{products.title}</h1>
      </header>

      {products.pagination.total === 0 ? (
        <div className="flex flex-1 items-center justify-center px-6 py-12">
          <EmptyState
            icon={ShoppingBagLine}
            title="No products yet"
            description="Products you add to the store show up here."
          />
        </div>
      ) : (
        <ResourceTable resource={products} />
      )}
    </div>
  );
}

Index.layout = [SidebarLayout];
