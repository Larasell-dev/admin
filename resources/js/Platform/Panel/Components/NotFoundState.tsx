import { EmptyState } from "@larasell-dev/ui";
import { FileUnknowLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";

export default function NotFoundState() {
  return (
    <EmptyState
      icon={FileUnknowLine}
      title={fbs("Page not found", "Heading shown when the page the admin opened doesn't exist")}
      description={fbs(
        "The page you're looking for doesn't exist. It may have been moved or deleted, or the link you followed is out of date.",
        "Text below the 'Page not found' heading, shown when the page the admin opened doesn't exist",
      )}
    />
  );
}
