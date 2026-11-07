import { afterEach, beforeEach, expect, it, vi } from "vitest";
import { debounce } from "./debounce";

beforeEach(() => vi.useFakeTimers());
afterEach(() => vi.useRealTimers());

it("calls once with the last arguments after calls stop for the delay", () => {
  // Arrange
  const fn = vi.fn<(query: string) => void>();
  const debounced = debounce(fn, 200);

  // Act
  debounced("s");
  vi.advanceTimersByTime(150);
  debounced("sh");
  vi.advanceTimersByTime(150);

  // Assert
  expect(fn).not.toHaveBeenCalled();

  vi.advanceTimersByTime(50);
  expect(fn).toHaveBeenCalledExactlyOnceWith("sh");
});

it("drops a pending call when cancelled", () => {
  // Arrange
  const fn = vi.fn<(query: string) => void>();
  const debounced = debounce(fn, 200);

  // Act
  debounced("s");
  debounced.cancel();
  vi.advanceTimersByTime(200);

  // Assert
  expect(fn).not.toHaveBeenCalled();
});
