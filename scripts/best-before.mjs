// Fails once a "best before" marker has expired, like a to-do with a deadline.
//
// Write the marker in a comment in any file Git tracks, in any language:
//
//   BEST-BEFORE YYYY-MM-DD: what to decide or clean up by then
//
// The note can go on over the following comment lines. The marker passes through its date
// and fails from the next day on (UTC), so the work gets done or the date moved.
//
//   node scripts/best-before.mjs
import { execFileSync } from "node:child_process";
import path from "node:path";

const root = path.resolve(import.meta.dirname, "..");
const marker = /BEST-BEFORE (\d{4}-\d{2}-\d{2}):?\s*(.*)/;
const today = new Date().toISOString().slice(0, 10);

let output = "";
try {
  // Only tracked text files (-I), so dependencies and binaries are skipped.
  output = execFileSync(
    "git",
    ["grep", "-n", "-I", "-E", "BEST-BEFORE [0-9]{4}-[0-9]{2}-[0-9]{2}"],
    {
      cwd: root,
      encoding: "utf8",
    },
  );
} catch (error) {
  // git grep exits with 1 when nothing matches.
  if (error.status !== 1) throw error;
}

const markers = output
  .split("\n")
  .filter(Boolean)
  .map((line) => {
    const [, file, number, text] = line.match(/^(.+?):(\d+):(.*)$/);
    const [, date, note] = text.match(marker);

    return { file, number, date, note: note.replace(/\s*\*\/$/, "") };
  });

// Rejects dates such as 2027-02-30, which Date would roll over into March.
const isRealDate = (date) => {
  const parsed = new Date(`${date}T00:00:00Z`);
  return !Number.isNaN(parsed.getTime()) && parsed.toISOString().startsWith(date);
};
let failed = false;

for (const { file, number, date, note } of markers) {
  const where = `file=${file},line=${number}`;

  if (!isRealDate(date)) {
    console.error(`::error ${where}::${date} is not a real date.`);
    failed = true;
  } else if (date < today) {
    console.error(`::error ${where}::Expired on ${date}: ${note}`);
    failed = true;
  } else {
    console.log(`${file}:${number} is good until ${date}.`);
  }
}

if (failed) process.exit(1);
