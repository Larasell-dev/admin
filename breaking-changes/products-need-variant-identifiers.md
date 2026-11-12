# The panel needs larasell-dev/larasell 1.29, with identifiers on variants

The product page now edits the SKU, barcode and MPN on the product's default variant, since
the core moved them from the product to its variants. The panel needs the core version that
made that move.

## Who is affected

Every app, since it has to update `larasell-dev/larasell` along with the panel. Apps and
extensions that read `sku` or `barcode` on a `Product`, or call the core's variant APIs, also
follow the core's upgrade guide: "Identifiers live on variants, and variants inherit fields
explicitly" and "Variants are made from options only".

## How to upgrade

Update both packages, then run the migrations. They move each product's SKU and barcode to its
default variant and its variant dimensions to options.

```sh
composer require larasell-dev/admin larasell-dev/larasell:^1.29 --with-all-dependencies
php artisan migrate
```
