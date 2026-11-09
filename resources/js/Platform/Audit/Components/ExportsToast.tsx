import { Icon, IconButton, Spinner } from "@larasell-dev/ui";
import {
  ArrowDownSLine,
  ArrowUpSLine,
  DeleteBinLine,
  DownloadLine,
  ErrorWarningLine,
  FileCheckLine,
  FileDamageLine,
} from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import { AnimatePresence, MotionConfig, motion } from "motion/react";
import { router } from "@inertiajs/react";
import { useState } from "react";
import ConfirmActionDialog from "../../Panel/Components/ConfirmActionDialog";
import type { TableRowAction } from "../../../types";
import { createPortal } from "react-dom";

type Export = Larasell.Admin.Platform.Audit.Export.Data.AuditLogExportData;

/**
 * The admin's exports of the audit log, floating at the bottom center like the unsaved changes
 * toast. Collapsed, it sums them up; expanded, it lists each, newest first, above its header.
 * The page reloads the exports live when one finishes.
 */
export default function ExportsToast({ exports }: { exports: Export[] }) {
  const [expanded, setExpanded] = useState(false);

  return createPortal(
    <MotionConfig reducedMotion="user">
      <AnimatePresence>
        {exports.length > 0 && (
          <motion.section
            aria-label={fbs(
              "Exports",
              "Audit log exports toast: accessible name of the box at the bottom of the audit log page with the admin's exports of the log",
            )}
            style={{ left: "50%", x: "-50%" }}
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: 12 }}
            transition={{ type: "spring", bounce: 0.05, visualDuration: 0.16 }}
            // Above the page, but under dialogs, menus and popups (z-50).
            className="fixed bottom-6 z-30 w-[28rem] max-w-[calc(100vw-2rem)] border border-base-subtle bg-base-elevated bg-clip-padding shadow-elevated"
          >
            {/* The list grows out of the header, above it, and folds back into it. */}
            <AnimatePresence initial={false}>
              {expanded && (
                <motion.div
                  key="list"
                  initial={{ height: 0, opacity: 0 }}
                  animate={{ height: "auto", opacity: 1 }}
                  exit={{ height: 0, opacity: 0 }}
                  transition={{ type: "spring", bounce: 0.05, visualDuration: 0.2 }}
                  className="overflow-hidden"
                >
                  <ExportList exports={exports} />
                </motion.div>
              )}
            </AnimatePresence>
            <Header exports={exports} expanded={expanded} onToggle={() => setExpanded(!expanded)} />
          </motion.section>
        )}
      </AnimatePresence>
    </MotionConfig>,
    document.body,
  );
}

function Header({
  exports,
  expanded,
  onToggle,
}: {
  exports: Export[];
  expanded: boolean;
  onToggle: () => void;
}) {
  const pending = exports.filter((item) => item.status === "pending").length;
  const failed = exports.filter((item) => item.status === "failed").length;

  return (
    <div className="flex items-center gap-3 py-1.5 pr-1.5 pl-4">
      {pending > 0 ? (
        <Spinner aria-hidden size={4} />
      ) : (
        <Icon icon={failed > 0 ? FileDamageLine : FileCheckLine} />
      )}
      <p aria-live="polite" className="type-body-md flex-1 py-2 text-base-primary">
        {pending > 0 ? (
          <fbt desc="Audit log exports toast: summary while some exports are still being written, e.g. '2 of 3 exports in progress'">
            <fbt:param name="pending">{pending}</fbt:param> of{" "}
            <fbt:plural count={exports.length} many="exports" showCount="yes">
              export
            </fbt:plural>{" "}
            in progress
          </fbt>
        ) : failed > 0 ? (
          <fbt desc="Audit log exports toast: summary once no export is being written and some failed, e.g. '1 of 3 exports failed'">
            <fbt:param name="failed">{failed}</fbt:param> of{" "}
            <fbt:plural count={exports.length} many="exports" showCount="yes">
              export
            </fbt:plural>{" "}
            failed
          </fbt>
        ) : (
          <fbt desc="Audit log exports toast: summary once every export can be downloaded, e.g. '2 exports ready'">
            <fbt:plural count={exports.length} many="exports" showCount="yes">
              export
            </fbt:plural>{" "}
            ready
          </fbt>
        )}
      </p>
      <IconButton
        variant="tertiary"
        size="sm"
        aria-expanded={expanded}
        aria-label={
          expanded
            ? fbs(
                "Hide exports",
                "Audit log exports toast: button that collapses the list of exports",
              )
            : fbs(
                "Show exports",
                "Audit log exports toast: button that expands the list of exports",
              )
        }
        onClick={onToggle}
      >
        <Icon icon={expanded ? ArrowDownSLine : ArrowUpSLine} />
      </IconButton>
    </div>
  );
}

/** Newest first, as the page sends them; scrolls once there are more than five. */
function ExportList({ exports }: { exports: Export[] }) {
  const [deleting, setDeleting] = useState<Export | null>(null);
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState<string>();
  const onDelete = (item: Export) => {
    setError(undefined);
    setDeleting(item);
  };

  return (
    <>
      <ConfirmActionDialog
        action={deleting && deleteAction(deleting)}
        processing={processing}
        error={error}
        onCancel={() => setDeleting(null)}
        onConfirm={(typed) =>
          deleting &&
          router.delete(deleting.deleteUrl, {
            data: { confirmation: typed },
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onSuccess: () => setDeleting(null),
            onError: (errors) => setError(errors.confirmation),
          })
        }
      />
      <ul className="max-h-70 overflow-y-auto border-b border-base-subtle">
        {exports.map((item) => (
          <li key={item.id} className="flex h-14 items-center justify-between gap-3 pr-1.5 pl-4">
            <span className="type-body-md text-base-primary">
              {new Date(item.createdAt).toLocaleString()}
            </span>
            {item.status === "pending" && (
              <span className="flex size-9 items-center justify-center">
                <Spinner
                  size={4}
                  aria-label={fbs(
                    "Preparing export",
                    "Audit log exports toast: accessible name of the spinner shown for an export still being written",
                  )}
                />
              </span>
            )}
            {item.status === "failed" && (
              <span className="flex size-9 items-center justify-center text-intent-negative">
                <Icon
                  icon={ErrorWarningLine}
                  label={fbs(
                    "Export failed",
                    "Audit log exports toast: accessible name of the warning icon shown for an export that couldn't be written",
                  )}
                />
              </span>
            )}
            {item.status === "ready" && item.downloadUrl && (
              <span className="flex">
                <IconButton
                  variant="tertiary"
                  size="sm"
                  aria-label={fbs(
                    "Delete export",
                    "Audit log exports toast: accessible name of the icon button that deletes an export before it expires",
                  )}
                  onClick={() => onDelete(item)}
                >
                  <Icon icon={DeleteBinLine} />
                </IconButton>
                <IconButton
                  variant="tertiary"
                  size="sm"
                  render={<a href={item.downloadUrl} />}
                  aria-label={fbs(
                    ["Download ", fbs.param("count", String(item.records ?? 0)), " records"],
                    "Audit log exports toast: accessible name of the icon button that downloads an export, with how many records it holds",
                  )}
                >
                  <Icon icon={DownloadLine} />
                </IconButton>
              </span>
            )}
          </li>
        ))}
      </ul>
    </>
  );
}

/** The delete as a table action, so it uses the panel's type-to-confirm dialog. */
function deleteAction(item: Export): TableRowAction {
  const text = fbs(
    "Delete export",
    "Audit log exports toast: the text the admin types to confirm deleting an export; must match the server's 'Delete export'",
  );

  return {
    name: "delete",
    label: text,
    method: "post",
    url: item.deleteUrl,
    destructive: true,
    disabled: false,
    icon: null,
    confirm: {
      title: fbs(
        "Delete export?",
        "Audit log exports toast: title of the dialog that confirms deleting an export",
      ),
      message: fbs(
        "The file can't be downloaded anymore. The audit log keeps a record that it was deleted.",
        "Audit log exports toast: message of the dialog that confirms deleting an export",
      ),
      confirmationText: text,
      confirmButton: fbs(
        "Delete",
        "Audit log exports toast: button in the dialog that deletes the export",
      ),
      cancelButton: fbs(
        "Cancel",
        "Audit log exports toast: button in the dialog that keeps the export",
      ),
    },
  };
}
