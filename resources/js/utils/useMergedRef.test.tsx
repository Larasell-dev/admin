import { createRef, type Ref, useState } from "react";
import { expect, it, vi } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import { useMergedRef } from "./useMergedRef";

/** A div that gives its element to every ref, and can be taken away. */
function Element({
  refs,
  mounted = true,
}: {
  refs: (Ref<HTMLDivElement> | undefined)[];
  mounted?: boolean;
}) {
  const ref = useMergedRef(...refs);
  return mounted ? <div ref={ref} data-testid="element" /> : null;
}

const element = () => page.getByTestId("element").element();

it("gives the element to object and callback refs, and takes it back once it's gone", async () => {
  const object = createRef<HTMLDivElement>();
  const callback = vi.fn<(element: HTMLDivElement | null) => void>();
  const screen = await render(<Element refs={[object, callback, undefined]} />);
  const div = element();

  expect(object.current).toBe(div);
  expect(callback).toHaveBeenCalledExactlyOnceWith(div);

  await screen.rerender(<Element refs={[object, callback, undefined]} mounted={false} />);

  expect(object.current).toBeNull();
  expect(callback).toHaveBeenLastCalledWith(null);
});

it("runs the cleanup a callback ref returns instead of calling it with null", async () => {
  const cleanup = vi.fn<() => void>();
  const callback = vi.fn<(element: HTMLDivElement | null) => () => void>(() => cleanup);
  const object = createRef<HTMLDivElement>();
  const screen = await render(<Element refs={[callback, object]} />);

  await screen.rerender(<Element refs={[callback, object]} mounted={false} />);

  expect(cleanup).toHaveBeenCalledOnce();
  expect(callback).toHaveBeenCalledOnce();
  // Refs without a cleanup of their own still get null.
  expect(object.current).toBeNull();
});

it("keeps the element attached while the refs stay the same", async () => {
  const callback = vi.fn<(element: HTMLButtonElement | null) => void>();

  function Counter() {
    const [count, setCount] = useState(0);
    const ref = useMergedRef(callback);
    return (
      <button ref={ref} type="button" onClick={() => setCount(count + 1)}>
        {count}
      </button>
    );
  }
  await render(<Counter />);

  await page.getByRole("button").click();
  await expect.element(page.getByRole("button")).toHaveTextContent("1");

  expect(callback).toHaveBeenCalledOnce();
});
