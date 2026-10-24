import { EmptyState } from "@larasell-dev/ui";
import { FileUnknowLine } from "@larasell-dev/ui/icons";
import SidebarLayout from "../Layouts/SidebarLayout";

export default function NotFound() {
  return (
    <div className="flex min-h-[calc(100dvh-3.5rem)] items-center justify-center px-6 py-12 lg:min-h-dvh">
      <EmptyState
        icon={FileUnknowLine}
        title="Page not found"
        description="The page you're looking for doesn't exist. It may have been moved or deleted, or the link you followed is out of date."
      />
    </div>
  );
}

NotFound.layout = [SidebarLayout];
