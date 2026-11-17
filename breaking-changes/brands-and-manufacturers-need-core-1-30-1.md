# The panel needs larasell-dev/larasell ^1.30.1 for brands and manufacturers

The panel now has **Brands** and **Manufacturers** pages under Products, and the
product form picks a product's brand and manufacturer. Both come from the core's
new `Catalog\Brands` and `Catalog\Manufacturers`, which need core 1.30.1 and its
migrations.

## Why

Product pages in the EU have to name the manufacturer with its postal and email
address under the General Product Safety Regulation (GPSR). Brands are kept
apart from manufacturers, since a brand is what a product is marketed under and
the manufacturer is who legally produces it.

## Who is affected

Every app that updates the panel. Update the core and run its migrations, which
add the `larasell_brands` and `larasell_manufacturers` tables, nullable
`brand_id` and `manufacturer_id` columns on `larasell_products`, and the
`brand_name` and `manufacturer` snapshots on `larasell_order_items`:

```bash
composer require larasell-dev/larasell:^1.30.1
php artisan migrate
```

Apps that post to `products.store` or `products.update` themselves can also send
`brand_id` and `manufacturer_id`. Sent empty, they remove the product's brand or
manufacturer; left out, an existing product keeps its own. Apps that build
`ProductDetailData`, `CreateProductPageData` or `EditProductPageData` themselves
now pass `brandId` and `manufacturerId`, or `brands` and `manufacturers`.

A manufacturer's address is the core's `Catalog\Manufacturers\PostalAddress`
(street, postcode, city, state, country), not the customer `Address`, so the two
can change independently.
