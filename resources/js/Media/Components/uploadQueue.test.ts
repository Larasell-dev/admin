import { expect, it } from "vitest";
import { reduce, toStart, summary, uploadQueue } from "./uploadQueue";

const file = (name: string) => new File(["x"], name, { type: "image/png" });
const added = (...names: string[]) =>
  reduce([], {
    type: "ADDED",
    uploads: names.map((name) => ({
      id: name,
      url: "/admin/media",
      file: file(name),
      previewUrl: null,
    })),
  });

it("queues added files in the order they were picked", () => {
  // Act
  const state = added("a.png", "b.png");

  // Assert
  expect(state.map((upload) => [upload.id, upload.status])).toEqual([
    ["a.png", "queued"],
    ["b.png", "queued"],
  ]);
});

it("moves a file from queued through uploading to done", () => {
  // Arrange
  let state = added("a.png");

  // Act
  state = reduce(state, { type: "START", id: "a.png" });
  state = reduce(state, { type: "PROGRESS", id: "a.png", progress: 0.4 });
  const midway = state[0];
  state = reduce(state, { type: "SUCCEEDED", id: "a.png", mediaId: 7 });

  // Assert
  expect(midway).toMatchObject({ status: "uploading", progress: 0.4 });
  expect(state[0]).toMatchObject({ status: "done", mediaId: 7 });
});

it("ignores events that don't fit the file's state", () => {
  // Arrange
  let state = added("a.png");
  state = reduce(state, { type: "START", id: "a.png" });
  state = reduce(state, { type: "FAILED", id: "a.png", error: "Offline", retryable: true });
  const failed = state;

  // A late progress report, a success for a file that never restarted, a second start.
  // Act
  state = reduce(state, { type: "PROGRESS", id: "a.png", progress: 0.9 });
  state = reduce(state, { type: "SUCCEEDED", id: "a.png", mediaId: 1 });
  state = reduce(state, { type: "START", id: "a.png" });

  // Assert
  expect(state).toBe(failed);
});

it("never starts a queued file's success before it started", () => {
  // Arrange
  const state = added("a.png");

  // Act
  const next = reduce(state, { type: "SUCCEEDED", id: "a.png", mediaId: 1 });

  // Assert
  expect(next[0]?.status).toBe("queued");
});

it("queues a failed file again on retry, but only when retrying can help", () => {
  // Arrange
  let state = added("offline.png", "huge.png");
  state = reduce(state, { type: "START", id: "offline.png" });
  state = reduce(state, { type: "START", id: "huge.png" });
  state = reduce(state, { type: "FAILED", id: "offline.png", error: "Offline", retryable: true });
  state = reduce(state, { type: "FAILED", id: "huge.png", error: "Too large", retryable: false });

  // Act
  state = reduce(state, { type: "RETRY", id: "offline.png" });
  state = reduce(state, { type: "RETRY", id: "huge.png" });

  // Assert
  expect(state.map((upload) => upload.status)).toEqual(["queued", "failed"]);
});

it("retries every retryable failure at once", () => {
  // Arrange
  let state = added("a.png", "b.png", "c.png");
  for (const id of ["a.png", "b.png", "c.png"]) state = reduce(state, { type: "START", id });
  state = reduce(state, { type: "FAILED", id: "a.png", error: "Offline", retryable: true });
  state = reduce(state, { type: "SUCCEEDED", id: "b.png", mediaId: 2 });
  state = reduce(state, { type: "FAILED", id: "c.png", error: "Too large", retryable: false });

  // Act
  state = reduce(state, { type: "RETRY_FAILED" });

  // Assert
  expect(state.map((upload) => upload.status)).toEqual(["queued", "done", "failed"]);
});

it("can only be dismissed once nothing is queued or uploading", () => {
  // Arrange
  let state = added("a.png", "b.png");
  state = reduce(state, { type: "START", id: "a.png" });
  state = reduce(state, { type: "SUCCEEDED", id: "a.png", mediaId: 1 });

  // Act
  const early = reduce(state, { type: "DISMISSED" });
  state = reduce(state, { type: "START", id: "b.png" });
  state = reduce(state, { type: "FAILED", id: "b.png", error: "Offline", retryable: true });
  const late = reduce(state, { type: "DISMISSED" });

  // Assert
  expect(early).toHaveLength(2);
  expect(late).toEqual([]);
});

it("starts queued files in order while fewer than the limit are uploading", () => {
  // Arrange
  let state = added("a.png", "b.png", "c.png", "d.png", "e.png");
  state = reduce(state, { type: "START", id: "a.png" });
  state = reduce(state, { type: "START", id: "b.png" });
  state = reduce(state, { type: "SUCCEEDED", id: "b.png", mediaId: 1 });

  // Act
  const next = toStart(state, 3);

  // Assert
  // a is still uploading, b is done, so two of the three slots are free.
  expect(next).toEqual(["c.png", "d.png"]);
});

it("sums up a mixed queue, counting every file the same", () => {
  // Arrange
  let state = added("a.png", "b.png", "c.png", "d.png");
  for (const id of ["a.png", "b.png", "c.png"]) state = reduce(state, { type: "START", id });
  state = reduce(state, { type: "SUCCEEDED", id: "a.png", mediaId: 1 });
  state = reduce(state, { type: "FAILED", id: "b.png", error: "Offline", retryable: true });
  state = reduce(state, { type: "PROGRESS", id: "c.png", progress: 0.5 });

  // Act
  const result = summary(state);

  // Assert
  // done 1 + failed 1 + half of c + nothing of queued d, out of 4.
  expect(result).toEqual({ done: 1, failed: 1, active: true, progress: 2.5 / 4 });
});

it("sends each file once, even when starting one schedules again", async () => {
  // Arrange
  const sent: string[] = [];
  uploadQueue.send = (_url, upload) => {
    sent.push(upload.file.name);
    return new Promise(() => {});
  };

  // Act
  uploadQueue.add("/admin/media", ["a.png", "b.png", "c.png", "d.png"].map(file));

  // Assert
  expect(sent).toEqual(["a.png", "b.png", "c.png"]);
  await Promise.resolve();
  uploadQueue.state = [];
});

it("sends each file to the folder it was picked in, even after the admin moved on", async () => {
  // Arrange
  const sent: string[] = [];
  const pending: (() => void)[] = [];
  uploadQueue.send = (url, upload) => {
    sent.push(`${upload.file.name} -> ${url}`);
    return new Promise((resolve) => pending.push(() => resolve({ ok: true, mediaId: 1 })));
  };
  uploadQueue.add("/admin/media?folder=1", ["a.png", "b.png", "c.png", "d.png"].map(file));

  // Act
  uploadQueue.add("/admin/media?folder=2", [file("e.png")]);
  pending.splice(0).forEach((finish) => finish());
  await new Promise((resolve) => setTimeout(resolve));

  // Assert
  expect(sent).toEqual([
    "a.png -> /admin/media?folder=1",
    "b.png -> /admin/media?folder=1",
    "c.png -> /admin/media?folder=1",
    "d.png -> /admin/media?folder=1",
    "e.png -> /admin/media?folder=2",
  ]);
  pending.splice(0).forEach((finish) => finish());
  await new Promise((resolve) => setTimeout(resolve));
  uploadQueue.state = [];
});

it("adds a file's preview once it's made, in any state, but only once", () => {
  // Arrange
  let state = added("a.png");
  state = reduce(state, { type: "START", id: "a.png" });

  // Act
  state = reduce(state, { type: "PREVIEWED", id: "a.png", previewUrl: "blob:small" });
  const previewed = state;
  state = reduce(state, { type: "PREVIEWED", id: "a.png", previewUrl: "blob:other" });

  // Assert
  expect(previewed[0]).toMatchObject({ status: "uploading", previewUrl: "blob:small" });
  expect(state).toBe(previewed);
});
