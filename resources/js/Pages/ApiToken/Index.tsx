import { Link } from "@inertiajs/react";
import { Button, Dialog, EmptyState, Icon, Stack, useCopy } from "@larasell-dev/ui";
import { AddLine, CheckLine, FileCopyLine, KeyLine } from "@larasell-dev/ui/icons";
import { useEffect, useState } from "react";
import { Page } from "../../Components/Page";
import ResourceTable from "../../Components/ResourceTable";
import SidebarLayout from "../../Layouts/SidebarLayout";
import { useFlash } from "../../utils/useFlash";

type Props = Larasell.Admin.Data.View.ApiTokensPageData;

export default function Index({ apiTokens, createUrl }: Props) {
  const hasRows = apiTokens.pagination.total > 0;

  return (
    <>
      <Page.Header
        title={apiTokens.title}
        actions={
          // The empty state offers its own button instead.
          hasRows && (
            <Button size="sm" render={<Link href={createUrl} prefetch="hover" />}>
              <Icon icon={AddLine} />
              Create token
            </Button>
          )
        }
      />
      {hasRows ? (
        <ResourceTable resource={apiTokens} />
      ) : (
        <div className="flex flex-1 items-center justify-center px-6 py-12">
          <EmptyState
            icon={KeyLine}
            title={apiTokens.emptyState?.title ?? "No API tokens yet"}
            description={apiTokens.emptyState?.description ?? undefined}
          >
            <Button render={<Link href={createUrl} prefetch="hover" />}>
              <Icon icon={AddLine} />
              Create token
            </Button>
          </EmptyState>
        </div>
      )}
      <CreatedTokenDialog />
    </>
  );
}

function CreatedTokenDialog() {
  const token = useFlash<Larasell.Admin.Data.ViewModel.CreatedApiTokenData>("apiToken");
  const { open, dismiss } = useShowOnce(token?.plainTextToken);
  const { copied, copy } = useCopy();

  return (
    <Dialog.Root
      open={open}
      onOpenChange={(open) => {
        if (!open) {
          dismiss();
        }
      }}
    >
      <Dialog.Popup
        title="API token created"
        description="For security, the token is shown only this once. Store it somewhere safe; if it gets lost, revoke it and create a new one."
      >
        {token && (
          <Stack spacing={5}>
            <div className="flex flex-col gap-3">
              <div className="flex items-center gap-4">
                <div className="min-w-0 flex-1 font-medium text-mauve-950 dark:text-mauve-50">
                  {token.name}
                </div>
                <Button
                  size="sm"
                  variant="secondary"
                  onClick={() => void copy(token.plainTextToken)}
                >
                  {copied ? "Copied" : "Copy"}
                  <Icon icon={copied ? CheckLine : FileCopyLine} />
                </Button>
              </div>
              {/* Wraps rather than scrolls, so the whole token is visible to check a paste against. */}
              <code className="block border border-border bg-white p-3 font-mono text-sm break-all text-mauve-950 select-all dark:bg-mauve-950 dark:text-mauve-50">
                {token.plainTextToken}
              </code>
            </div>
            <Dialog.Actions>
              <Button variant="primary" onClick={dismiss}>
                Done
              </Button>
            </Dialog.Actions>
          </Stack>
        )}
      </Dialog.Popup>
    </Dialog.Root>
  );
}

/**
 * Whether to show something identified by `key` until it is dismissed: open while there is a
 * key, closed once dismissed, and open again for a new key.
 *
 * The page renders with the key already there. Opening on the next frame, rather than mounting
 * open, lets a dialog play its enter animation.
 */
function useShowOnce(key: string | undefined): { open: boolean; dismiss: () => void } {
  const [ready, setReady] = useState(false);
  const [dismissed, setDismissed] = useState<string | undefined>(undefined);

  useEffect(() => {
    const frame = requestAnimationFrame(() => setReady(true));
    return () => cancelAnimationFrame(frame);
  }, []);

  const open = (): boolean => {
    if (!ready) {
      return false;
    }
    if (key === undefined) {
      return false;
    }
    return key !== dismissed;
  };

  return { open: open(), dismiss: () => setDismissed(key) };
}

Index.layout = [SidebarLayout];
