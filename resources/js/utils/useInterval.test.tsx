import { afterEach, beforeEach, expect, it, vi } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import { useInterval } from "./useInterval";

function Ticker({ onTick, ms }: { onTick: () => void; ms: number }) {
  const clear = useInterval(onTick, ms);
  return <button onClick={clear}>Stop</button>;
}

beforeEach(() => vi.useFakeTimers());
afterEach(() => vi.useRealTimers());

it("calls the latest callback without restarting the timer, until ms changes", async () => {
  const first = vi.fn<() => void>();
  const second = vi.fn<() => void>();
  const screen = await render(<Ticker onTick={first} ms={1000} />);

  vi.advanceTimersByTime(600);
  await screen.rerender(<Ticker onTick={second} ms={1000} />);
  // The timer kept going, so it fires 400ms after the rerender, and calls the new callback.
  vi.advanceTimersByTime(400);
  expect(first).not.toHaveBeenCalled();
  expect(second).toHaveBeenCalledTimes(1);

  vi.advanceTimersByTime(600);
  await screen.rerender(<Ticker onTick={second} ms={500} />);
  // Restarted at 500ms, so the 1000ms tick due in 400ms doesn't come.
  vi.advanceTimersByTime(499);
  expect(second).toHaveBeenCalledTimes(1);
  vi.advanceTimersByTime(1);
  expect(second).toHaveBeenCalledTimes(2);
});

it("stops when cleared or unmounted", async () => {
  const onTick = vi.fn<() => void>();
  const screen = await render(<Ticker onTick={onTick} ms={1000} />);

  (page.getByRole("button", { name: "Stop" }).element() as HTMLButtonElement).click();
  vi.advanceTimersByTime(3000);
  expect(onTick).not.toHaveBeenCalled();

  await screen.rerender(<Ticker onTick={onTick} ms={2000} />);
  vi.advanceTimersByTime(2000);
  expect(onTick).toHaveBeenCalledTimes(1);

  await screen.unmount();
  vi.advanceTimersByTime(4000);
  expect(onTick).toHaveBeenCalledTimes(1);
});
