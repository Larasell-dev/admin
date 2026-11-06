import { describe, expect, expectTypeOf, it, vi } from "vitest";
import { createExtensions, type PageLoader, SectionForm, type SectionLoader } from "./extensions";

const Show = () => null;

describe("SectionForm", () => {
  it("has a case for every value of the server's enum, and no others", () => {
    // Act & Assert
    expectTypeOf<
      (typeof SectionForm)[keyof typeof SectionForm]
    >().toEqualTypeOf<Larasell.Admin.Platform.Sections.SectionForm>();
  });
});

describe("extensions", () => {
  it("runs booting callbacks when the panel boots, not before", () => {
    // Arrange
    const extensions = createExtensions();
    const callback = vi.fn<() => void>();

    // Act
    extensions.Larasell.booting(callback);

    // Assert
    expect(callback).not.toHaveBeenCalled();

    extensions.boot();

    expect(callback).toHaveBeenCalledOnce();
  });

  it("runs booting callbacks right away once the panel has booted", () => {
    // Arrange
    const extensions = createExtensions();
    extensions.boot();
    const callback = vi.fn<() => void>();

    // Act
    extensions.Larasell.booting(callback);

    // Assert
    expect(callback).toHaveBeenCalledOnce();
  });

  it("loads a registered page only when it is resolved", async () => {
    // Arrange
    const extensions = createExtensions();
    const loader = vi.fn<PageLoader>(async () => ({ default: Show }));
    extensions.Larasell.booting(({ pages }) => {
      pages.register("acme-wishlists::Wishlist/Show", loader);
    });
    extensions.boot();

    expect(loader).not.toHaveBeenCalled();

    // Act & Assert
    await expect(extensions.resolve("acme-wishlists::Wishlist/Show")).resolves.toBe(Show);
    expect(loader).toHaveBeenCalledOnce();
  });

  it("loads a page's section components only once a page with them is shown", async () => {
    // Arrange
    const extensions = createExtensions();
    const Notes = () => null;
    const loader = vi.fn<SectionLoader>(async () => ({ default: Notes }));
    extensions.Larasell.booting(({ sections }) => {
      sections.register(SectionForm.Product, "notes", loader);
    });
    extensions.boot();

    expect(loader).not.toHaveBeenCalled();
    expect(extensions.section("product", "notes")).toBeUndefined();

    // Act
    // Another page's section of the same key, and one no extension registered, load nothing.
    await extensions.loadSections([
      { form: "category", key: "notes" },
      { form: "product", key: "unknown" },
    ]);

    // Assert
    expect(loader).not.toHaveBeenCalled();

    await extensions.loadSections([{ form: "product", key: "notes" }]);
    await extensions.loadSections([{ form: "product", key: "notes" }]);

    expect(extensions.section("product", "notes")).toBe(Notes);
    expect(loader).toHaveBeenCalledOnce();
  });

  it("resolves nothing for pages no extension registered", async () => {
    // Arrange
    const extensions = createExtensions();
    extensions.boot();

    // Act & Assert
    await expect(extensions.resolve("acme-wishlists::Wishlist/Show")).resolves.toBeUndefined();
  });
});
