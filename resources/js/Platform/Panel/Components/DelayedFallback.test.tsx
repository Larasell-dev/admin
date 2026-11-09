import "../../../../css/app.css";
import { act } from "react";
import { afterEach, beforeEach, expect, it, vi } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import DelayedFallback from "./DelayedFallback";

function Preview({ pending }: { pending: boolean }) {
  return (
    <DelayedFallback pending={pending} fallback={<p>Loading</p>} delay={200} minDuration={400}>
      <p>Picture</p>
    </DelayedFallback>
  );
}

beforeEach(() => vi.useFakeTimers());
afterEach(() => vi.useRealTimers());

it("keeps the content mounted but hidden, shows the fallback after the delay, then the content", async () => {
  // Arrange
  const screen = await render(<Preview pending />);

  const visible = (text: string) => {
    const element = page.getByText(text).query();
    return element !== null && getComputedStyle(element).visibility === "visible";
  };
  // Polling assertions would wait on the faked timers, so these check right away.
  const advance = (ms: number) => act(() => vi.advanceTimersByTimeAsync(ms));

  // Act & Assert
  expect(page.getByText("Picture").query()).not.toBeNull();
  expect(visible("Picture")).toBe(false);
  expect(visible("Loading")).toBe(false);
  await advance(200);
  expect(visible("Loading")).toBe(true);
  await screen.rerender(<Preview pending={false} />);
  expect(visible("Picture")).toBe(false);
  await advance(400);
  expect(visible("Loading")).toBe(false);
  expect(visible("Picture")).toBe(true);
});
