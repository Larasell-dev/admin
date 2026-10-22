import { afterEach, beforeEach, expect, it, vi } from "vitest";
import { debounce } from "./debounce";

beforeEach(() => vi.useFakeTimers());
afterEach(() => vi.useRealTimers());

it("calls once with the last arguments after calls stop for the delay", () => {
  const fn = vi.fn();
  const debounced = debounce(fn, 200);

  debounced("s");
  vi.advanceTimersByTime(150);
  debounced("sh");
  vi.advanceTimersByTime(150);
  expect(fn).not.toHaveBeenCalled();

  vi.advanceTimersByTime(50);
  expect(fn).toHaveBeenCalledExactlyOnceWith("sh");
});

it("drops a pending call when cancelled", () => {
  const fn = vi.fn();
  const debounced = debounce(fn, 200);

  debounced("s");
  debounced.cancel();
  vi.advanceTimersByTime(200);

  expect(fn).not.toHaveBeenCalled();
});
