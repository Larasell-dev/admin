# Breaking changes

Every breaking change gets its own Markdown file in this directory, written for
the developers who have to upgrade their app. When a release is cut, the
release workflow moves all files here into an **Upgrade guide** section of that
version in [`CHANGELOG.md`](../CHANGELOG.md) and deletes them.

## Adding one

1. Copy [`_template.md`](_template.md) to a new file with a short, descriptive
   name, e.g. `nav-icon-renamed-to-icon.md`.
2. Fill it in and delete the guidance comments you no longer need. Comments are
   stripped from the changelog anyway.
3. Mark the pull request title as breaking with `!`, e.g.
   `fix!: rename NavIcon enum to Icon`. While the version is below 1.0,
   this makes release-please bump the minor version instead of the patch
   version.

CI fails when a pull request adds a file here without a `!` title, or has a `!`
title without adding a file here.

## Writing a good one

- **Lead with the change.** The title states what is different now, e.g.
  "The `NavIcon` enum is now `Icon`", not "Refactor icons".
- **Say why.** One or two sentences, so readers know the change is deliberate
  and worth the effort.
- **Say who is affected.** Name the API, config key, route, or behavior, and
  say who can skip the entry.
- **Show before and after.** A short code sample for each side beats a
  paragraph of prose. Use real class names and imports.
- **Make it copy-pasteable.** Prefer exact commands, config, and code over
  instructions like "update your usages".
- **Mention deprecations and fallbacks.** If the old way still works for now,
  say until when.
- **One change per file.** Split unrelated breaking changes so each can be
  understood, and skipped, on its own.

## Format

Start the file with a single `#` title and use `##` for sections. Headings are
nested under the version when they are bundled into the changelog. Files whose
name starts with `_`, and this README, are never bundled.
