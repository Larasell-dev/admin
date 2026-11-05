// Collects the panel's translatable text and compiles the frontend's for the browser. The frontend's
// is in the <fbt> and fbs() calls in resources/js, the server's in the __() and Translate::later()
// calls in src, routes and resources/views.
//
//   node scripts/translations.mjs extract          adds new text to each resources/translations/*.json
//                                                  and lang/*.json
//   node scripts/translations.mjs extract fr-FR    also starts both translation files for fr-FR
//   node scripts/translations.mjs compile          writes what Vite bundles to resources/js/translations
//
// Extract marks new frontend text with "status": "new"; translate it and remove the status. New
// server text gets an empty translation; fill it in. Until then the panel shows the English text. Compile also writes the pseudo-languages: en-XA, with accented and
// longer text, and ar-XB, right to left. They show text that isn't translatable yet and layouts
// that break with longer or right-to-left text, without waiting for real translations.
import { execFileSync } from "node:child_process";
import { existsSync, mkdirSync, readdirSync, readFileSync, rmSync, writeFileSync } from "node:fs";
import path from "node:path";

const root = path.resolve(import.meta.dirname, "..");
const translationsDir = "resources/translations";
const serverTranslationsDir = "lang";
const pseudoDir = ".fbtee/pseudo";
const sourceStrings = ".fbtee/source_strings.json";

// Placeholders, such as {name}, stay as they are: the runtime replaces them.
const placeholder = /(\{[^}]*\})/;

const letters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
const accentedLetters = Array.from("åƀçðéƒĝĥîĵķļɱñöþǫŕšţûṽŵẋýžÅƁÇÐÉƑĜĤÎĴĶĻṀÑÖÞǪŔŠŢÛṼŴẊÝŽ");

const fbtee = (...args) =>
  execFileSync(path.join(root, "node_modules/.bin/fbtee"), args, { cwd: root, stdio: "inherit" });

const [command, ...newLanguages] = process.argv.slice(2);

fbtee(
  "collect",
  "--src",
  "resources/js",
  "--out",
  sourceStrings,
  "--enum-manifest",
  ".fbtee/enum_manifest.json",
);

if (command === "extract") {
  const languages = readdirSync(path.join(root, translationsDir))
    .filter((file) => file.endsWith(".json"))
    .map((file) => file.slice(0, -".json".length));

  fbtee(
    "prepare-translations",
    "--source-strings",
    sourceStrings,
    "--output-dir",
    translationsDir,
    "--locales",
    ...new Set([...languages, ...newLanguages]),
  );
  extractServerText([...new Set([...languages, ...newLanguages])]);
} else if (command === "compile") {
  writePseudoTranslations();

  rmSync(path.join(root, "resources/js/translations"), { recursive: true, force: true });
  fbtee(
    "translate",
    "--source-strings",
    sourceStrings,
    "--translations",
    `${translationsDir}/*.json`,
    `${pseudoDir}/*.json`,
    "--output-dir",
    "resources/js/translations",
  );
} else {
  console.error("Usage: node scripts/translations.mjs extract [language...] | compile");
  process.exit(1);
}

/**
 * Writes lang/<language>.json with every text the server translates, keyed by its English text as
 * Laravel's JSON translations are, keeping the translations already there.
 */
function extractServerText(languages) {
  const call = /(?:\b__|Translate::later)\(\s*(?:'((?:[^'\\]|\\.)*)'|"((?:[^"\\]|\\.)*)")/g;
  // Lines of Laravel's own files, such as auth.failed, come from the app's lang/<language>/ folder.
  const fileKey = /^[\w-]+(\.[\w-]+)+$/;

  const texts = new Set();
  for (const dir of ["src", "routes", "resources/views"]) {
    for (const file of readdirSync(path.join(root, dir), { recursive: true })) {
      if (!file.endsWith(".php")) continue;
      for (const [, single, double] of readFileSync(path.join(root, dir, file), "utf8").matchAll(
        call,
      )) {
        const text = (single ?? double).replaceAll(/\\(.)/g, "$1");
        if (!fileKey.test(text)) texts.add(text);
      }
    }
  }

  mkdirSync(path.join(root, serverTranslationsDir), { recursive: true });

  for (const language of languages) {
    const file = path.join(root, serverTranslationsDir, `${language}.json`);
    const existing = existsSync(file) ? JSON.parse(readFileSync(file, "utf8")) : {};
    // An empty translation shows the English text, like a missing one.
    const translations = Object.fromEntries(
      [...texts]
        .sort((a, b) => a.localeCompare(b, "en"))
        .map((text) => [text, existing[text] ?? ""]),
    );

    writeFileSync(file, `${JSON.stringify(translations, null, 2)}\n`);
  }
}

function writePseudoTranslations() {
  const { phrases } = JSON.parse(readFileSync(path.join(root, sourceStrings), "utf8"));
  const leaves = phrases.flatMap((phrase) => Object.entries(phrase.hashToLeaf));

  mkdirSync(path.join(root, pseudoDir), { recursive: true });

  for (const [language, pseudo] of Object.entries({ "en-XA": accented, "ar-XB": rightToLeft })) {
    const translations = Object.fromEntries(
      leaves.map(([hash, { text }]) => [
        hash,
        {
          tokens: [],
          types: [],
          translations: [{ translation: pseudo(text), variations: {} }],
        },
      ]),
    );

    writeFileSync(
      path.join(root, pseudoDir, `${language}.json`),
      JSON.stringify({ "fb-locale": language, translations }, null, 2),
    );
  }
}

/**
 * "Switch theme" becomes "[Šŵîţçĥ ţĥéɱé ~~~~~]": accented, so text that skipped translation stands
 * out, about 40% longer, like many languages, and in brackets, so cut-off text shows.
 */
function accented(text) {
  const words = text
    .split(placeholder)
    .map((part, index) =>
      index % 2
        ? part
        : part.replace(/[a-z]/gi, (letter) => accentedLetters[letters.indexOf(letter)]),
    )
    .join("");
  const padding = "~".repeat(
    Math.max(1, Math.round(text.replaceAll(/\{[^}]*\}/g, "").length * 0.4)),
  );

  return `[${words} ${padding}]`;
}

/** Runs each piece of text right to left, with Unicode's override marks, around the placeholders. */
function rightToLeft(text) {
  return text
    .split(placeholder)
    .map((part, index) => (index % 2 || part === "" ? part : `\u202E${part}\u202C`))
    .join("");
}
