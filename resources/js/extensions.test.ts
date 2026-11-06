import { describe, expect, it, vi } from "vitest";
import { createExtensions, type PageLoader, type SectionLoader } from "./extensions";

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

  it("loads a page's section components only once a page with them is shown", async () => {
    const extensions = createExtensions();
    const Notes = () => null;
    const loader = vi.fn<SectionLoader>(async () => ({ default: Notes }));
    extensions.Larasell.booting(({ sections }) => {
      sections.register("product", "notes", loader);
    });
    extensions.boot();

    expect(loader).not.toHaveBeenCalled();
    expect(extensions.section("product", "notes")).toBeUndefined();

    // Another page's section of the same key, and one no extension registered, load nothing.
    await extensions.loadSections([
      { page: "category", key: "notes" },
      { page: "product", key: "unknown" },
    ]);
    expect(loader).not.toHaveBeenCalled();

    await extensions.loadSections([{ page: "product", key: "notes" }]);
    await extensions.loadSections([{ page: "product", key: "notes" }]);

    expect(extensions.section("product", "notes")).toBe(Notes);
    expect(loader).toHaveBeenCalledOnce();
  });

  it("resolves nothing for pages no extension registered", async () => {
    const extensions = createExtensions();
    extensions.boot();

    await expect(extensions.resolve("acme-wishlists::Wishlist/Show")).resolves.toBeUndefined();
  });
});
