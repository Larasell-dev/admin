import { expect, it } from "vitest";
import { abortPrevious } from "./abortPrevious";

it("aborts the previous call's signal but not the latest one's", async () => {
  const signals: AbortSignal[] = [];
  const run = abortPrevious(async (signal, query: string) => {
    signals.push(signal);
    return query;
  });

  await run("s");
  await expect(run("sh")).resolves.toBe("sh");

  expect(signals[0]!.aborted).toBe(true);
  expect(signals[1]!.aborted).toBe(false);
});

it("aborts the latest call when told to", async () => {
  let latest: AbortSignal | undefined;
  const run = abortPrevious(async (signal) => {
    latest = signal;
  });

  await run();
  run.abort();

  expect(latest!.aborted).toBe(true);
});
