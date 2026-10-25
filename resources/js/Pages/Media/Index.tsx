import { Link } from "@inertiajs/react";
import { EmptyState, Icon, IconButton } from "@larasell-dev/ui";
import { ArrowLeftLine, ImageLine } from "@larasell-dev/ui/icons";
import MediaTable, { type MediaTableResource } from "../../Components/Media/MediaTable";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = { table: MediaTableResource };

/** The media at the top level or in a folder. */
export default function Index({ table }: Props) {
  return (
    <div className="flex h-[calc(100dvh-3.5rem)] flex-col lg:h-dvh">
      <header className="border-border flex h-14 shrink-0 items-center gap-2 border-b px-6">
        {table.backLink && (
          <IconButton
            variant="tertiary"
            size="sm"
            aria-label={table.backLink.label}
            nativeButton={false}
            // Base UI marks non-native buttons as buttons; this navigates, so it stays a link.
            role="link"
            render={<Link href={table.backLink.url} prefetch="hover" />}
          >
            <Icon icon={ArrowLeftLine} />
          </IconButton>
        )}
        <h1 className="flex-1 truncate type-body-md text-mauve-950 dark:text-mauve-50">
          {table.title}
        </h1>
      </header>

      {table.pagination.total === 0 ? (
        <div className="flex flex-1 items-center justify-center px-6 py-12">
          <EmptyState
            icon={ImageLine}
            title={table.emptyState?.title ?? "No media yet"}
            description={table.emptyState?.description ?? undefined}
          />
        </div>
      ) : (
        <MediaTable table={table} />
      )}
    </div>
  );
}

Index.layout = [SidebarLayout];
