import { EmptyState } from "@larasell-dev/ui";
import { ErrorWarningLine, FileUnknowLine, ForbidLine, ToolsLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";

/** The HTTP statuses the server shows the panel's error page for. */
export type ErrorStatus = 403 | 404 | 500 | 503;

export default function ErrorState({ status }: { status: ErrorStatus }) {
  const { icon, title, description } = {
    403: {
      icon: ForbidLine,
      title: fbs("Access denied", "Heading shown when the admin isn't allowed to open the page"),
      description: fbs(
        "You don't have permission to open this page. Ask an admin who can grant you access.",
        "Text below the 'Access denied' heading, shown when the admin isn't allowed to open the page",
      ),
    },
    404: {
      icon: FileUnknowLine,
      title: fbs("Page not found", "Heading shown when the page the admin opened doesn't exist"),
      description: fbs(
        "The page you're looking for doesn't exist. It may have been moved or deleted, or the link you followed is out of date.",
        "Text below the 'Page not found' heading, shown when the page the admin opened doesn't exist",
      ),
    },
    500: {
      icon: ErrorWarningLine,
      title: fbs("Something went wrong", "Heading shown when the server failed to load the page"),
      description: fbs(
        "The server ran into a problem loading this page. Try again in a moment.",
        "Text below the 'Something went wrong' heading, shown when the server failed to load the page",
      ),
    },
    503: {
      icon: ToolsLine,
      title: fbs(
        "Temporarily unavailable",
        "Heading shown when the server can't serve the page right now, e.g. during maintenance",
      ),
      description: fbs(
        "We're doing some maintenance. Please check back soon.",
        "Text below the 'Temporarily unavailable' heading, shown when the server can't serve the page right now",
      ),
    },
  }[status];

  return <EmptyState icon={icon} title={title} description={description} />;
}
