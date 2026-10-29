import { describe, expect, it, vi } from "vitest";
import { createExtensions, type PageLoader } from "./extensions";

const Show = () => null;

describe("extensions", () => {
  it("runs booting callbacks when the panel boots, not before", () => {
    const extensions = createExtensions();
    const callback = vi.fn<() => void>();

    extensions.Larasell.booting(callback);

    expect(callback).not.toHaveBeenCalled();

    extensions.boot();

    expect(callback).toHaveBeenCalledOnce();
  });

  it("runs booting callbacks right away once the panel has booted", () => {
    const extensions = createExtensions();
    extensions.boot();
    const callback = vi.fn<() => void>();

    extensions.Larasell.booting(callback);

    expect(callback).toHaveBeenCalledOnce();
  });

  it("loads a registered page only when it is resolved", async () => {
    const extensions = createExtensions();
    const loader = vi.fn<PageLoader>(async () => ({ default: Show }));
    extensions.Larasell.booting(({ pages }) => {
      pages.register("acme-wishlists::Wishlist/Show", loader);
    });
    extensions.boot();

    expect(loader).not.toHaveBeenCalled();
    await expect(extensions.resolve("acme-wishlists::Wishlist/Show")).resolves.toBe(Show);
    expect(loader).toHaveBeenCalledOnce();
  });

  it("resolves nothing for pages no extension registered", async () => {
    const extensions = createExtensions();
    extensions.boot();

    await expect(extensions.resolve("acme-wishlists::Wishlist/Show")).resolves.toBeUndefined();
  });
});
