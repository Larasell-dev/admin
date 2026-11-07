/** Lowercase letters that have no accent to strip, spelled out in ASCII. */
const SPELLED_OUT: Record<string, string> = {
  ß: "ss",
  æ: "ae",
  œ: "oe",
  ø: "o",
  ł: "l",
  đ: "d",
  ð: "d",
  þ: "th",
  ı: "i",
};

/**
 * Turns text into a slug the server accepts: lowercase ASCII letters and numbers with single
 * dashes between them. Accents are stripped, apostrophes dropped, and everything else that
 * isn't a letter or number separates words. It can be empty when nothing is left.
 */
export function slugify(text: string): string {
  return (
    text
      .toLowerCase()
      .replace(/[ßæœøłđðþı]/g, (letter) => SPELLED_OUT[letter]!)
      // Symbols go before NFKD, which would otherwise spell "™" as "tm".
      .replace(/\p{S}/gu, "-")
      // Splits accented letters into letter and accent, and folds ligatures, full-width
      // letters, and fractions to plain characters.
      .normalize("NFKD")
      .replace(/\p{M}/gu, "")
      .replace(/['’ʼ]/g, "")
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-|-$/g, "")
  );
}
