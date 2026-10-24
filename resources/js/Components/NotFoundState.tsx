import { EmptyState } from "@larasell-dev/ui";
import { FileUnknowLine } from "@larasell-dev/ui/icons";

export default function NotFoundState() {
  return (
    <EmptyState
      icon={FileUnknowLine}
      title="Page not found"
      description="The page you're looking for doesn't exist. It may have been moved or deleted, or the link you followed is out of date."
    />
  );
}
