# The admin panel requires PHP 8.4

The admin panel now needs PHP 8.4 or newer, like Larasell and Chronicle, which it is built on.
Both require PHP 8.4 from their current versions, so the admin panel can't upgrade them while it
still allows PHP 8.3.

## Who is affected

Apps that run on PHP 8.3. Composer won't install this version of the admin panel there. Apps on
PHP 8.4 or 8.5 are not affected.

## How to upgrade

Upgrade PHP to 8.4 or newer on every server and in CI, then raise the PHP requirement in your
app's `composer.json`:

```json
// Before
"require": {
    "php": "^8.3"
}

// After
"require": {
    "php": "^8.4"
}
```
