import { fbs } from "fbtee";
import { useSyncExternalStore } from "react";

/** `url` is where the file goes, with the folder it was picked in, so switching folders mid-queue changes nothing. */
type Base = { id: string; url: string; file: File; previewUrl: string | null };

/** One file on its way to the server. Each state carries only the fields it has. */
export type Upload =
  | (Base & { status: "queued" })
  | (Base & { status: "uploading"; progress: number })
  | (Base & { status: "done"; mediaId: number })
  | (Base & { status: "failed"; error: string; retryable: boolean });

export type UploadEvent =
  | { type: "ADDED"; uploads: Base[] }
  | { type: "START"; id: string }
  | { type: "PROGRESS"; id: string; progress: number }
  | { type: "PREVIEWED"; id: string; previewUrl: string }
  | { type: "SUCCEEDED"; id: string; mediaId: number }
  | { type: "FAILED"; id: string; error: string; retryable: boolean }
  | { type: "RETRY"; id: string }
  | { type: "RETRY_FAILED" }
  | { type: "DISMISSED" };

/**
 * The upload queue's state machine. Pure: the same events always give the same state, and an
 * event that doesn't fit a file's state, like a progress report arriving after it failed, leaves
 * the state as it is.
 */
export function reduce(state: Upload[], event: UploadEvent): Upload[] {
  switch (event.type) {
    case "ADDED":
      return [
        ...state,
        ...event.uploads.map((upload) => ({ ...upload, status: "queued" as const })),
      ];
    case "RETRY_FAILED":
      return update(
        state,
        () => true,
        (upload) => transition(upload, { type: "RETRY", id: upload.id }),
      );
    case "DISMISSED":
      return summary(state).active ? state : [];
    case "PREVIEWED":
      // A preview fits whatever state the file is in.
      return update(
        state,
        (upload) => upload.id === event.id && upload.previewUrl === null,
        (upload) => ({ ...upload, previewUrl: event.previewUrl }),
      );
    default:
      return update(
        state,
        (upload) => upload.id === event.id,
        (upload) => transition(upload, event),
      );
  }
}

function transition(upload: Upload, event: UploadEvent): Upload {
  if (upload.status === "queued" && event.type === "START") {
    return { ...base(upload), status: "uploading", progress: 0 };
  }
  if (upload.status === "uploading" && event.type === "PROGRESS") {
    return { ...upload, progress: event.progress };
  }
  if (upload.status === "uploading" && event.type === "SUCCEEDED") {
    return { ...base(upload), status: "done", mediaId: event.mediaId };
  }
  if (upload.status === "uploading" && event.type === "FAILED") {
    return { ...base(upload), status: "failed", error: event.error, retryable: event.retryable };
  }
  if (upload.status === "failed" && upload.retryable && event.type === "RETRY") {
    return { ...base(upload), status: "queued" };
  }
  return upload;
}

function base({ id, url, file, previewUrl }: Upload): Base {
  return { id, url, file, previewUrl };
}

/** Applies `change` to the matching uploads, keeping the same array when nothing changed. */
function update(
  state: Upload[],
  matches: (upload: Upload) => boolean,
  change: (upload: Upload) => Upload,
) {
  let changed = false;
  const next = state.map((upload) => {
    if (!matches(upload)) return upload;
    const updated = change(upload);
    changed ||= updated !== upload;
    return updated;
  });
  return changed ? next : state;
}

/** The queued files to start now, in order, so that at most `limit` upload at once. */
export function toStart(state: Upload[], limit: number): string[] {
  const free = limit - state.filter((upload) => upload.status === "uploading").length;
  return state
    .filter((upload) => upload.status === "queued")
    .slice(0, Math.max(0, free))
    .map((upload) => upload.id);
}

/** What the toast shows; derived from the files, never stored next to them. */
export function summary(state: Upload[]) {
  return {
    done: state.filter((upload) => upload.status === "done").length,
    failed: state.filter((upload) => upload.status === "failed").length,
    active: state.some((upload) => upload.status === "queued" || upload.status === "uploading"),
    // Every file counts the same, so one large video doesn't hold the bar still.
    progress:
      state.reduce(
        (sum, upload) =>
          sum +
          (upload.status === "uploading" ? upload.progress : upload.status === "queued" ? 0 : 1),
        0,
      ) / Math.max(1, state.length),
  };
}

export type UploadResult =
  | { ok: true; mediaId: number }
  | { ok: false; error: string; retryable: boolean };

type Send = (
  url: string,
  upload: Upload,
  onProgress: (progress: number) => void,
) => Promise<UploadResult>;

/** How many files upload at once. */
const LIMIT = 3;

/**
 * Keeps the uploads outside React, so they go on while the admin moves to another page. The
 * reducer above is the only thing that changes them; this runs the requests it asks for.
 */
export const uploadQueue = {
  state: [] as Upload[],
  listeners: new Set<() => void>(),
  /** Replaced in tests. */
  send: sendWithXhr as Send,
  onSucceeded: () => {},

  dispatch(event: UploadEvent) {
    const previous = uploadQueue.state;
    uploadQueue.state = reduce(previous, event);
    if (uploadQueue.state === previous) return;
    // Files dropped by DISMISSED no longer need their previews.
    if (uploadQueue.state.length === 0)
      previous.forEach((upload) => upload.previewUrl && URL.revokeObjectURL(upload.previewUrl));
    uploadQueue.listeners.forEach((listener) => listener());
    if (event.type === "SUCCEEDED") uploadQueue.onSucceeded();
    uploadQueue.schedule();
  },

  /** Picked files join the queue; images get a small preview once it's made. */
  add(url: string, files: File[]) {
    const uploads = files.map((file) => ({
      // Also the request's Idempotency-Key, so a retry can't upload the file twice.
      id: crypto.randomUUID(),
      url,
      file,
      previewUrl: null,
    }));
    uploadQueue.dispatch({ type: "ADDED", uploads });
    uploads
      .filter((upload) => upload.file.type.startsWith("image/"))
      .forEach((upload) => void uploadQueue.preview(upload.id, upload.file));
  },

  /**
   * Shows a thumbnail instead of the original: decoding a large photo in full for a 40px square
   * stalls the page for hundreds of milliseconds per image.
   */
  async preview(id: string, file: File) {
    const previewUrl = await thumbnail(file).catch(() => null);
    if (!previewUrl) return;
    uploadQueue.dispatch({ type: "PREVIEWED", id, previewUrl });
    // Dismissed in the meantime, or already previewed: nothing shows it.
    const upload = uploadQueue.state.find((candidate) => candidate.id === id);
    if (upload?.previewUrl !== previewUrl) URL.revokeObjectURL(previewUrl);
  },

  scheduling: false,

  schedule() {
    // Starting a file dispatches, which schedules again; the outer call does all the starting,
    // and each file is picked from the current state, so none is sent twice.
    if (uploadQueue.scheduling) return;
    uploadQueue.scheduling = true;
    let id: string | undefined;
    while ((id = toStart(uploadQueue.state, LIMIT)[0])) {
      uploadQueue.dispatch({ type: "START", id });
      const upload = uploadQueue.state.find((candidate) => candidate.id === id);
      if (!upload) continue;
      const started = id;
      void uploadQueue
        .send(upload.url, upload, (progress) =>
          uploadQueue.dispatch({ type: "PROGRESS", id: started, progress }),
        )
        .then((result) =>
          uploadQueue.dispatch(
            result.ok
              ? { type: "SUCCEEDED", id: started, mediaId: result.mediaId }
              : { type: "FAILED", id: started, error: result.error, retryable: result.retryable },
          ),
        );
    }
    uploadQueue.scheduling = false;
  },

  subscribe: (listener: () => void) => {
    uploadQueue.listeners.add(listener);
    return () => uploadQueue.listeners.delete(listener);
  },
};

/** A blob URL of the image scaled to 96px wide, twice the largest preview, for sharp screens. */
async function thumbnail(file: File): Promise<string | null> {
  const bitmap = await createImageBitmap(file, { resizeWidth: 96, resizeQuality: "medium" });
  const canvas = document.createElement("canvas");
  canvas.width = bitmap.width;
  canvas.height = bitmap.height;
  canvas.getContext("2d")?.drawImage(bitmap, 0, 0);
  bitmap.close();
  const blob = await new Promise<Blob | null>((resolve) =>
    canvas.toBlob(resolve, "image/jpeg", 0.85),
  );
  return blob ? URL.createObjectURL(blob) : null;
}

export function useUploads(): Upload[] {
  return useSyncExternalStore(uploadQueue.subscribe, () => uploadQueue.state);
}

/** XMLHttpRequest, since fetch can't report upload progress. */
function sendWithXhr(
  url: string,
  upload: Upload,
  onProgress: (progress: number) => void,
): Promise<UploadResult> {
  return new Promise((resolve) => {
    const request = new XMLHttpRequest();
    const data = new FormData();
    data.append("files[]", upload.file);
    request.open("POST", url);
    request.setRequestHeader("Accept", "application/json");
    request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    request.setRequestHeader("Idempotency-Key", upload.id);
    const token = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)?.[1];
    if (token) request.setRequestHeader("X-XSRF-TOKEN", decodeURIComponent(token));
    request.upload.onprogress = (event) =>
      event.lengthComputable && onProgress(event.loaded / event.total);
    request.onload = () => {
      const body = parse(request.responseText);
      if (request.status >= 200 && request.status < 300 && typeof body?.id === "number") {
        resolve({ ok: true, mediaId: body.id });
      } else if (request.status === 413) {
        // PHP refused the request before Laravel saw it: larger than post_max_size.
        resolve({ ok: false, error: messages.tooLarge(), retryable: false });
      } else if (request.status === 422) {
        // The file itself was refused, e.g. too large: sending it again fails the same way.
        const errors = (body?.errors ?? {}) as Record<string, string[]>;
        resolve({
          ok: false,
          error:
            Object.values(errors)[0]?.[0] ??
            (typeof body?.message === "string" ? body.message : ""),
          retryable: false,
        });
      } else {
        resolve({ ok: false, error: messages.server(), retryable: true });
      }
    };
    request.onerror = () => {
      resolve({ ok: false, error: messages.interrupted(), retryable: true });
    };
    request.send(data);
  });
}

function parse(text: string): Record<string, unknown> | null {
  try {
    return JSON.parse(text) as Record<string, unknown>;
  } catch {
    return null;
  }
}

/** Translated when an upload fails, never at module level, before the translations load. */
const messages = {
  interrupted: () =>
    fbs(
      "The upload was interrupted. Check your connection and try again.",
      "Upload toast: error of a file whose upload stopped because the connection dropped",
    ),
  tooLarge: () =>
    fbs(
      "The file is too large to upload.",
      "Upload toast: error of a file larger than the server takes",
    ),
  server: () =>
    fbs(
      "Something went wrong while uploading. Try again.",
      "Upload toast: error of a file the server failed to store",
    ),
};
