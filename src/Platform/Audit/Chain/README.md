# The audit log's chain

Every audit record in `larasell_audit_log` is chained to the one before it, so a record changed,
deleted or slipped in outside the app shows when the log is verified. This file is the spec: with
it, anyone can check the chain from the stored rows, in any language, without this code.
`php artisan larasell:verify-audit-log` does the same check.

A chain only proves the log matches itself. Someone who can write to the database can change a
record and recompute every hash after it. To catch that, keep a copy of a recent record's `seq`
and `hash` somewhere they can't reach, and check later that it is still in the log, unchanged.

## Columns

| Column          | What it holds                                                  |
| --------------- | -------------------------------------------------------------- |
| `seq`           | The record's place in the chain: 1, 2, 3, … with no gaps.      |
| `previous_hash` | The `hash` of record `seq - 1`, or null for record 1.          |
| `hash`          | SHA-256 of the record, as 64 lowercase hexadecimal characters. |

`larasell_audit_log_head` holds one row with the newest record's `seq` and `hash`, so deleting the
newest records shows too.

## Hashing a record

```
hash = lowercase_hex(sha256(utf8(previous_hash ?? "") + canonical(record)))
```

`canonical(record)` is a JSON object with exactly these keys, written as below:

| Key                    | Value                                                                                 |
| ---------------------- | ------------------------------------------------------------------------------------- |
| `actor_id`             | string or null                                                                        |
| `actor_type`           | string                                                                                |
| `category`             | string                                                                                |
| `correlation_id`       | string or null                                                                        |
| `created_at`           | string, the stored UTC time as `YYYY-MM-DD HH:MM:SS.ffffff`, always 6 fraction digits |
| `event`                | string                                                                                |
| `event_version`        | integer                                                                               |
| `id`                   | string                                                                                |
| `occurred_at`          | string, as `created_at`                                                               |
| `properties`           | the stored JSON, parsed and written again as below                                    |
| `seq`                  | integer                                                                               |
| `source`               | string                                                                                |
| `subject_id`           | string or null                                                                        |
| `subject_type`         | string or null                                                                        |
| `unknown_actor_reason` | string or null                                                                        |

Databases hand values back in their own form: MySQL reorders the keys of a JSON column, Postgres
drops trailing zeros of a time. Parse them, then write them like this:

- **No whitespace** anywhere outside strings.
- **Objects:** keys sorted by their UTF-8 bytes, every level deep. `{}` stays an object, `[]` a
  list; lists keep their order.
- **Strings:** UTF-8, not escaped except `"` as `\"`, `\` as `\\`, and characters below U+0020:
  `\b`, `\t`, `\n`, `\f`, `\r`, or `\u00xx` with lowercase hexadecimal for the rest. `/` and
  non-ASCII characters, U+2028 and U+2029 included, are written as they are.
- **Numbers:** integers only, in decimal without leading zeros or `+`. Records never store
  fractions; the Auditor refuses them.
- `true`, `false` and `null` as such.

For example, record 1 of a log might be hashed from:

```json
{
  "actor_id": "librarian",
  "actor_type": "system",
  "category": "business_critical",
  "correlation_id": "9b7c…",
  "created_at": "2026-10-30 12:00:00.250000",
  "event": "book.shelved",
  "event_version": 1,
  "id": "01K8…",
  "occurred_at": "2026-10-30 12:00:00.250000",
  "properties": { "author": { "first": "Frank", "last": "Herbert" }, "tags": ["b", "a"] },
  "seq": 1,
  "source": "cli",
  "subject_id": "42",
  "subject_type": "book",
  "unknown_actor_reason": null
}
```

## Verifying

Walk the records in `seq` order and stop at the first failure:

1. **Where it starts.** The chain starts after the `starts_after` of the newest `audit_log.pruned`
   record, `{"seq": n, "sha256": "…"}`: the last record deleted by pruning. Without one, it starts
   after nothing, at `seq` 1 with a null `previous_hash`.
2. **`missing_event`:** a record's `seq` isn't the one after the previous record's, or after where
   the chain starts. A record was deleted.
3. **`hash_mismatch`:** the hash computed from the stored record isn't its `hash`. The record was
   changed.
4. **`link_mismatch`:** its `previous_hash` isn't the previous record's `hash`, or where the chain
   starts. The record is intact, but a record before it was replaced.
5. **`head_mismatch`:** after the last record, the head's `seq` and `hash` aren't the last
   record's. The newest records were deleted, or the head changed.

## Pruning

`larasell:prune-audit-log` only deletes from the start of the chain: the oldest records, up to the
first one still in the retention period. It then records `audit_log.pruned` with `starts_after`,
in the chain like any record, so verification knows where the chain now starts. A gap anywhere
else is tampering.

## Writing

Appending updates the head row first, inside the transaction of the change being audited. The
database holds that row's lock until the transaction commits or rolls back, so writers take turns
on MySQL, Postgres and SQLite alike, also behind a connection pooler.
