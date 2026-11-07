# The product form no longer has images

Product images are removed, along with the core's, to make room for a media module.

## Who is affected

Apps or plugins that place sections with `ProductSection::Images`, call the
`larasell.admin.products.images.store` route, or read `ProductDetailData::$images`,
`ProductImageData` or the `uploadImagesUrl` prop of the product pages.

## How to upgrade

Place sections next to another of the form's own sections instead:

```php
// Before
Section::add(...)->after(ProductSection::Images);

// After
Section::add(...)->before(ProductSection::Categories);
```
