import { expect, it } from "vitest";
import { abortPrevious } from "./abortPrevious";

it("aborts the previous call's signal but not the latest one's", async () => {
  // Arrange
  const signals: AbortSignal[] = [];
  const run = abortPrevious(async (signal, query: string) => {
    signals.push(signal);
    return query;
  });

  // Act
  await run("s");

  // Assert
  await expect(run("sh")).resolves.toBe("sh");

  expect(signals[0]!.aborted).toBe(true);
  expect(signals[1]!.aborted).toBe(false);
});

it("aborts the latest call when told to", async () => {
  // Arrange
  let latest: AbortSignal | undefined;
  const run = abortPrevious(async (signal) => {
    latest = signal;
  });

  // Act
  await run();
  run.abort();

  // Assert
  expect(latest!.aborted).toBe(true);
});
