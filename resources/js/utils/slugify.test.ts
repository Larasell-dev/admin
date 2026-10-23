import { describe, expect, it } from "vitest";
import { slugify } from "./slugify";

/** The rule the server validates slugs with, see `src/Rules/Slug.php`. */
const VALID_SLUG = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;

describe("slugify", () => {
  it("lowercases words and joins them with dashes", () => {
    expect(slugify("Running Shoes")).toBe("running-shoes");
    expect(slugify("SHOES")).toBe("shoes");
  });

  it("keeps numbers", () => {
    expect(slugify("Summer Sale 2026")).toBe("summer-sale-2026");
    expect(slugify("42")).toBe("42");
  });

  it("treats every kind of whitespace as one separator", () => {
    expect(slugify("Running   Shoes")).toBe("running-shoes");
    expect(slugify("Running\tShoes\nfor Kids")).toBe("running-shoes-for-kids");
    expect(slugify("Running\u00a0Shoes")).toBe("running-shoes");
    expect(slugify("Running\u2003Shoes")).toBe("running-shoes");
  });

  it("trims separators from both ends", () => {
    expect(slugify("  Shoes  ")).toBe("shoes");
    expect(slugify("--Shoes--")).toBe("shoes");
    expect(slugify("!Shoes?")).toBe("shoes");
  });

  it("collapses runs of separators into a single dash", () => {
    expect(slugify("Shoes -- Socks")).toBe("shoes-socks");
    expect(slugify("Shoes & Socks")).toBe("shoes-socks");
    expect(slugify("Shoes / Socks / Hats")).toBe("shoes-socks-hats");
    expect(slugify("shoes_and_socks")).toBe("shoes-and-socks");
  });

  it("treats punctuation and typographic dashes as separators", () => {
    expect(slugify("Size 42.5")).toBe("size-42-5");
    expect(slugify("Shoes, Socks; Hats: Gloves")).toBe("shoes-socks-hats-gloves");
    expect(slugify("Shoes (Kids)")).toBe("shoes-kids");
    expect(slugify("Shoes – Socks — Hats")).toBe("shoes-socks-hats");
    expect(slugify("Shoes+Socks")).toBe("shoes-socks");
  });

  it("drops apostrophes instead of splitting the word", () => {
    expect(slugify("Men's Shoes")).toBe("mens-shoes");
    expect(slugify("Men’s Shoes")).toBe("mens-shoes");
    expect(slugify("Rock 'n' Roll")).toBe("rock-n-roll");
  });

  it("strips accents from letters", () => {
    expect(slugify("Café Crème")).toBe("cafe-creme");
    expect(slugify("Ñandú")).toBe("nandu");
    expect(slugify("Čevapčići")).toBe("cevapcici");
    expect(slugify("Schuhe für Kinder")).toBe("schuhe-fur-kinder");
    expect(slugify("ÄÖÜ")).toBe("aou");
  });

  it("spells out letters that have no accent to strip", () => {
    expect(slugify("Straße")).toBe("strasse");
    expect(slugify("STRAẞE")).toBe("strasse");
    expect(slugify("Æble Œuvre")).toBe("aeble-oeuvre");
    expect(slugify("Smørrebrød")).toBe("smorrebrod");
    expect(slugify("Łódź")).toBe("lodz");
    expect(slugify("Đakovo")).toBe("dakovo");
    expect(slugify("Þór")).toBe("thor");
    expect(slugify("Ðe")).toBe("de");
    expect(slugify("Istanbul İzmir ılık")).toBe("istanbul-izmir-ilik");
  });

  it("folds compatibility characters to their plain form", () => {
    expect(slugify("ﬁne ﬂour")).toBe("fine-flour");
    expect(slugify("ＳＨＯＥＳ")).toBe("shoes");
    expect(slugify("Size ½")).toBe("size-1-2");
    expect(slugify("m²")).toBe("m2");
  });

  it("drops characters it cannot spell in ASCII", () => {
    expect(slugify("Shoes 👟")).toBe("shoes");
    expect(slugify("Обувь Shoes")).toBe("shoes");
    expect(slugify("靴 Shoes 靴")).toBe("shoes");
    expect(slugify("Shoes™ ©2026")).toBe("shoes-2026");
  });

  it("returns an empty slug when nothing can be kept", () => {
    expect(slugify("")).toBe("");
    expect(slugify("   ")).toBe("");
    expect(slugify("---")).toBe("");
    expect(slugify("!?&")).toBe("");
    expect(slugify("👟👟")).toBe("");
    expect(slugify("Обувь")).toBe("");
  });

  it("leaves a valid slug unchanged", () => {
    for (const slug of ["shoes", "running-shoes", "summer-sale-2026", "a-1-b-2"]) {
      expect(slugify(slug)).toBe(slug);
    }
  });

  it("gives the same slug when applied twice", () => {
    for (const name of ["Men's Shoes", "Straße & Café", " --Size ½-- ", "ﬁne 👟 Łódź"]) {
      expect(slugify(slugify(name))).toBe(slugify(name));
    }
  });

  it("only produces slugs the server accepts", () => {
    const names = [
      "Running Shoes",
      "  Men’s Shoes & Socks (Kids) – 2026!  ",
      "Straße Æble Þór İzmir",
      "ﬁne ＳＨＯＥＳ ½ m²",
      "a_b-c.d/e\\f|g",
      "-a-",
      "Shoes 👟 Обувь",
    ];

    for (const name of names) {
      expect(slugify(name)).toMatch(VALID_SLUG);
    }
  });
});
