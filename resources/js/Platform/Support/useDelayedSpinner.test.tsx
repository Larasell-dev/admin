import { act } from "react";
import { afterEach, beforeEach, expect, it, vi } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import { useDelayedSpinner } from "./useDelayedSpinner";

function Probe({ pending }: { pending: boolean }) {
  const spinner = useDelayedSpinner(pending, { delay: 200, minDuration: 400 });
  return <p>{spinner ? "spinner" : "no spinner"}</p>;
}

/** Whether the probe shows the spinner after `ms` more milliseconds. */
async function after(ms: number) {
  await act(() => vi.advanceTimersByTimeAsync(ms));
  return page.getByText("spinner", { exact: true }).query() !== null;
}

beforeEach(() => vi.useFakeTimers());
afterEach(() => vi.useRealTimers());

it("never shows the spinner when loading ends before the delay", async () => {
  // Arrange
  const screen = await render(<Probe pending />);

  // Act
  const before = await after(199);
  await screen.rerender(<Probe pending={false} />);

  // Assert
  expect(before).toBe(false);
  expect(await after(1000)).toBe(false);
});

it("keeps the spinner for its minimum time when loading ends just after it showed", async () => {
  // Arrange
  const screen = await render(<Probe pending />);

  // Act
  const shown = await after(200);
  await after(10);
  await screen.rerender(<Probe pending={false} />);

  // Assert
  expect(shown).toBe(true);
  expect(await after(389)).toBe(true);
  expect(await after(1)).toBe(false);
});

it("hides the spinner as soon as a long load ends, past its minimum time", async () => {
  // Arrange
  const screen = await render(<Probe pending />);

  // Act
  const during = await after(900);
  await screen.rerender(<Probe pending={false} />);

  // Assert
  expect(during).toBe(true);
  expect(await after(0)).toBe(false);
});

it("keeps the spinner when loading starts again during its minimum time", async () => {
  // Arrange
  const screen = await render(<Probe pending />);
  await after(200);
  await screen.rerender(<Probe pending={false} />);

  // Act
  await after(100);
  await screen.rerender(<Probe pending />);

  // Assert
  expect(await after(1000)).toBe(true);
});
