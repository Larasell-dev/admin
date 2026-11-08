import { cn } from "cn";
import { Button, ButtonGroup, Icon, IconButton, Spinner } from "@larasell-dev/ui";
import {
  ArrowDownSLine,
  ArrowUpSLine,
  CheckboxCircleLine,
  CloseLine,
  ErrorWarningLine,
  RefreshLine,
  TimeLine,
  VideoLine,
} from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import { AnimatePresence, MotionConfig, motion } from "motion/react";
import { useState } from "react";
import { createPortal } from "react-dom";
import { uploadQueue, useUploads, type Upload } from "./uploadQueue";

/**
 * The uploads in progress, bottom center like the UnsavedChangesToast. Collapsed it shows one line
 * and the overall progress; expanded it lists every file with its own progress and error.
 */
export default function UploadToast() {
  const uploads = useUploads();
  const onRetry = (id: string) => uploadQueue.dispatch({ type: "RETRY", id });
  const onRetryFailed = () => uploadQueue.dispatch({ type: "RETRY_FAILED" });
  const onClose = () => uploadQueue.dispatch({ type: "DISMISSED" });
  const [expanded, setExpanded] = useState(false);
  const done = uploads.filter((upload) => upload.status === "done").length;
  const failed = uploads.filter((upload) => upload.status === "failed");
  const active = uploads.some(
    (upload) => upload.status === "queued" || upload.status === "uploading",
  );
  // Every file counts the same, so one large video doesn't hold the bar still.
  const progress =
    uploads.reduce(
      (sum, upload) =>
        sum +
        (upload.status === "uploading" ? upload.progress : upload.status === "queued" ? 0 : 1),
      0,
    ) / Math.max(1, uploads.length);

  const title = active
    ? fbs(
        `Uploading ${fbs.param("done", String(done))} of ${fbs.param("total", String(uploads.length))}`,
        "Upload toast: title while files upload, e.g. 'Uploading 2 of 5'",
      )
    : failed.length > 0
      ? fbs(
          `${fbs.param("done", String(done))} uploaded, ${fbs.param("failed", String(failed.length))} failed`,
          "Upload toast: title once uploads ended and some failed, e.g. '4 uploaded, 1 failed'",
        )
      : fbs(
          `${fbs.param("done", String(done))} uploaded`,
          "Upload toast: title once every file uploaded, e.g. '5 uploaded'",
        );

  return createPortal(
    <MotionConfig reducedMotion="user">
      <AnimatePresence>
        {uploads.length > 0 && (
          <motion.div
            role="region"
            aria-label={fbs("Uploads", "Upload toast: accessible name of the box listing uploads")}
            style={{ left: "50%", x: "-50%" }}
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: 12 }}
            transition={{ type: "spring", bounce: 0.05, visualDuration: 0.16 }}
            className="fixed bottom-6 z-30 w-[28rem] max-w-[calc(100vw-2rem)] border border-base-subtle bg-base-elevated bg-clip-padding shadow-elevated"
          >
            {expanded && (
              <ul className="scroll-mask-y max-h-[18.5rem] overflow-y-auto border-b border-base-subtle py-1">
                {uploads.map((upload) => (
                  <UploadRow key={upload.id} upload={upload} onRetry={() => onRetry(upload.id)} />
                ))}
              </ul>
            )}
            <div className="flex items-center gap-3 py-1.5 pr-1.5 pl-4">
              {!expanded && uploads.some((upload) => upload.previewUrl) ? (
                <PreviewStack uploads={uploads} />
              ) : (
                <span className="flex items-center text-base-secondary">
                  {active ? (
                    <Spinner aria-hidden size={4} />
                  ) : (
                    <Icon icon={failed.length > 0 ? ErrorWarningLine : CheckboxCircleLine} />
                  )}
                </span>
              )}
              <p
                aria-live="polite"
                className="type-body-md min-w-0 flex-1 truncate text-base-primary"
              >
                {title}
              </p>
              <ButtonGroup
                label={fbs(
                  "Upload actions",
                  "Upload toast: accessible name of the group of retry, show files and close buttons",
                )}
              >
                {!active &&
                  failed.some((upload) => upload.status === "failed" && upload.retryable) && (
                    <Button type="button" variant="tertiary" onClick={onRetryFailed}>
                      <fbt desc="Upload toast: button that uploads every failed file again">
                        Retry failed
                      </fbt>
                    </Button>
                  )}
                <IconButton
                  variant="tertiary"
                  aria-expanded={expanded}
                  aria-label={
                    expanded
                      ? fbs(
                          "Hide files",
                          "Upload toast: accessible name of the button that collapses the file list",
                        )
                      : fbs(
                          "Show files",
                          "Upload toast: accessible name of the button that expands the file list",
                        )
                  }
                  onClick={() => setExpanded((open) => !open)}
                >
                  <Icon icon={expanded ? ArrowDownSLine : ArrowUpSLine} />
                </IconButton>
                {!active && (
                  <IconButton
                    variant="tertiary"
                    aria-label={fbs(
                      "Close",
                      "Upload toast: accessible name of the button that closes it",
                    )}
                    onClick={onClose}
                  >
                    <Icon icon={CloseLine} />
                  </IconButton>
                )}
              </ButtonGroup>
            </div>
            <ProgressBar value={progress} failed={!active && failed.length > 0} />
          </motion.div>
        )}
      </AnimatePresence>
    </MotionConfig>,
    document.body,
  );
}

/**
 * One file. Every row is as tall as the others in every state, so the list doesn't jump when a
 * file goes from uploading to uploaded or failed.
 */
function UploadRow({ upload, onRetry }: { upload: Upload; onRetry: () => void }) {
  return (
    <li className="flex h-14 items-center gap-3 px-4">
      <Preview upload={upload} className="size-10" />
      <div className="flex min-w-0 flex-1 flex-col">
        <span className="type-body-sm truncate text-base-primary">{upload.file.name}</span>
        {upload.status === "failed" ? (
          <span title={upload.error} className="type-body-sm truncate text-intent-negative">
            {upload.error}
          </span>
        ) : (
          <span className="type-body-sm text-base-secondary tabular-nums">
            {upload.status === "queued" && (
              <fbt desc="Upload toast: status of a file waiting for its turn to upload">
                Waiting
              </fbt>
            )}
            {upload.status === "uploading" && `${Math.round(upload.progress * 100)}%`}
            {upload.status === "done" && (
              <fbt desc="Upload toast: status of a file that uploaded">Uploaded</fbt>
            )}
          </span>
        )}
      </div>
      <span className="flex size-8 shrink-0 items-center justify-center text-base-secondary">
        {upload.status === "done" && <Icon icon={CheckboxCircleLine} />}
        {upload.status === "queued" && <Icon icon={TimeLine} />}
        {upload.status === "uploading" && <Spinner aria-hidden size={4} />}
        {upload.status === "failed" &&
          (upload.retryable ? (
            <IconButton
              variant="tertiary"
              aria-label={fbs(
                `Retry ${fbs.param("file", upload.file.name)}`,
                "Upload toast: accessible name of the button that uploads one failed file again",
              )}
              onClick={onRetry}
            >
              <Icon icon={RefreshLine} />
            </IconButton>
          ) : (
            <Icon icon={ErrorWarningLine} />
          ))}
      </span>
    </li>
  );
}

/** A square of the file, or a video icon when there's no picture of it. */
function Preview({ upload, className }: { upload: Upload; className: string }) {
  return upload.previewUrl ? (
    <img
      src={upload.previewUrl}
      alt=""
      decoding="async"
      className={cn(className, "shrink-0 object-cover")}
    />
  ) : (
    <span
      className={cn(
        className,
        "flex shrink-0 items-center justify-center bg-base-muted text-base-secondary",
      )}
    >
      {/* Blank while an image's preview is being made. */}
      {!upload.file.type.startsWith("image/") && <Icon icon={VideoLine} />}
    </span>
  );
}

/**
 * Where the collapsed toast's tiny previews sit on their arc, tilted: the first in the middle,
 * the second on top at the left, the third underneath at the right.
 */
const fan = [
  { x: 0, y: -3, rotate: 0, z: "z-20" },
  { x: -12, y: 2, rotate: -14, z: "z-30" },
  { x: 12, y: 2, rotate: 14, z: "z-10" },
];

function PreviewStack({ uploads }: { uploads: Upload[] }) {
  return (
    <span aria-hidden className="relative flex h-8 w-12 shrink-0 items-center justify-center">
      {uploads.slice(0, 3).map((upload, i) => (
        <span
          key={upload.id}
          className={cn("absolute bg-base-elevated p-0.5", fan[i]!.z)}
          style={{
            transform: `translate(${fan[i]!.x}px, ${fan[i]!.y}px) rotate(${fan[i]!.rotate}deg)`,
          }}
        >
          <Preview upload={upload} className="size-5" />
        </span>
      ))}
    </span>
  );
}

function ProgressBar({ value, failed = false }: { value: number; failed?: boolean }) {
  return (
    <div
      role="progressbar"
      aria-valuemin={0}
      aria-valuemax={100}
      aria-valuenow={Math.round(value * 100)}
      className="h-1 w-full overflow-hidden bg-base-muted"
    >
      <div
        className={
          failed
            ? "h-full bg-action-negative-primary-default transition-[width] duration-300"
            : "h-full bg-action-neutral-primary-default transition-[width] duration-300"
        }
        style={{ width: `${value * 100}%` }}
      />
    </div>
  );
}
