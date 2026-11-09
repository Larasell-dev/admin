# Audit record properties can no longer be fractions

`Auditor::record()` now throws `AuditFailed` when a record's `properties()` contain a float.
Properties are hashed into the audit log's chain, and a fraction has no single way to be written,
so the same record could hash differently when it is verified.

## Who is affected

Audit records of apps and extensions that return floats from `properties()`, e.g. a price. Records
with only strings, integers, booleans, null and arrays of them are not affected.

## How to upgrade

Store the amount in its smallest unit, or as a string:

```php
// Before
public function properties(): array
{
    return ['price' => 9.99];
}

// After
public function properties(): array
{
    return ['price_cents' => 999];
}
```
