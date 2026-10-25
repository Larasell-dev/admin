<?php

namespace Larasell\Admin\Exceptions;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Larasell\Chronicle\HasStructuredError;
use Larasell\Chronicle\StructuredError;

class InvalidTableColumn extends InvalidArgumentException implements StructuredError
{
    use HasStructuredError;

    public static function duplicateName(string $table, string $column): static
    {
        return static::structured(
            code: 'larasell-admin.table.column.duplicate-name',
            message: "Table [{$table}] defines more than one column named [{$column}].",
            why: "Each row stores a column's value under its name, so the last column named [{$column}] would overwrite the others.",
            fix: "Give each column a unique name. To show [{$column}] twice, name the second column differently and read the attribute in map(), e.g. ->map(fn (\$value, \$record) => \$record->{$column}).",
        );
    }

    public static function reservedName(string $table, string $column): static
    {
        return static::structured(
            code: 'larasell-admin.table.column.reserved-name',
            message: "Table [{$table}] defines a column named [{$column}], which is reserved.",
            why: "Every row uses [{$column}] for its own data, so a column by that name would overwrite it, or be overwritten.",
            fix: "Rename the column and compute its value with map() if it needs the attribute [{$column}].",
        );
    }

    public static function configuredTwice(string $column, string $method): static
    {
        return static::structured(
            code: 'larasell-admin.table.column.configured-twice',
            message: "Column [{$column}] calls {$method} more than once.",
            why: "A second call to {$method} would silently replace the first, so only one of them would take effect.",
            fix: "Call {$method} once on the column, and combine both callbacks into one if you need both.",
        );
    }

    /**
     * @param  array<int, string>  $attributes
     */
    public static function recordModified(string $column, array $attributes, string $method): static
    {
        $changed = implode(', ', $attributes);

        return static::structured(
            code: 'larasell-admin.table.column.record-modified',
            message: "Column [{$column}] {$method} changed the record's [{$changed}].",
            why: "The {$method} callback of [{$column}] assigned attributes on the record, which the row's other columns, actions and link then see.",
            fix: "Only read from the record in {$method} and return what to show, without assigning attributes.",
        );
    }

    public static function invalidMappedValue(string $column, string $type): static
    {
        return static::structured(
            code: 'larasell-admin.table.column.invalid-mapped-value',
            message: "Text column [{$column}] map() returned {$type}, not text.",
            why: "A text column shows its value as text, but the map() callback of [{$column}] returned {$type}, which has no clear text form.",
            fix: 'Return a string, a number, a Stringable or null from map(), e.g. $value ? \'Yes\' : \'No\' for a bool.',
        );
    }

    public static function invalidImage(string $column, string $type): static
    {
        return static::structured(
            code: 'larasell-admin.table.column.invalid-image',
            message: "Column [{$column}] image() returned {$type}, not an Image.",
            why: "The image() callback of [{$column}] must return the Image it receives, configured, or null for no image, but it returned {$type}.",
            fix: 'Configure the Image the callback receives and return it, e.g. fn ($record, Image $image) => $image->url($record->cover_url), or return null.',
        );
    }

    public static function imageWithoutSource(string $column): static
    {
        return static::structured(
            code: 'larasell-admin.table.column.image-without-source',
            message: "Column [{$column}] image() returned an Image without an icon or a url.",
            why: "The image() callback of [{$column}] returned an Image, but never said what it shows, so there is nothing to render.",
            fix: 'Call icon(), url(), to() or a route method on the Image, or return null to show no image for the record.',
        );
    }

    public static function imageConfiguredTwice(string $column, string $method): static
    {
        return static::structured(
            code: 'larasell-admin.table.column.configured-twice',
            message: "Column [{$column}] image calls {$method} more than once.",
            why: "An image shows one icon or picture in one style, so a second call to {$method} would silently replace the first.",
            fix: "Call {$method} once on the Image, choosing between the options in the callback, e.g. \$record->cover_url ? \$image->url(\$record->cover_url) : \$image->icon(Icon::Book).",
        );
    }

    public static function emptyImageUrl(string $column): static
    {
        return static::structured(
            code: 'larasell-admin.table.column.empty-image-url',
            message: "Column [{$column}] image url cannot be empty.",
            why: 'An image without a url shows as a broken picture.',
            fix: 'Return null from the image() callback for records without a picture, or show an icon for them instead.',
        );
    }

    public static function emptyImageIcon(string $column): static
    {
        return static::structured(
            code: 'larasell-admin.table.column.empty-image-icon',
            message: "Column [{$column}] image icon cannot be empty.",
            why: 'An empty icon name names no icon, so there is nothing to show.',
            fix: 'Pass an Icon case or a Remix icon name, e.g. Icon::BookOpen or "book-open", or return null from the image() callback.',
        );
    }

    public static function imageIconStyleVariant(string $column, string $icon): static
    {
        return static::structured(
            code: 'larasell-admin.table.column.image-icon-style-variant',
            message: "Column [{$column}] image icon [{$icon}] must not end in -line or -fill.",
            why: 'Table cells always show the line style of an icon, so the name must not choose one.',
            fix: 'Pass the icon name without its -line or -fill suffix, e.g. "book-open" instead of "book-open-line".',
        );
    }

    public static function unknownImageIcon(string $column, string $icon): static
    {
        return static::structured(
            code: 'larasell-admin.table.column.unknown-image-icon',
            message: "Column [{$column}] image icon [{$icon}] is not a Remix icon.",
            why: 'Only the Remix icons @larasell-dev/ui ships can be shown, and they are named in lowercase kebab case.',
            fix: 'Pass an Icon case, or a lowercase Remix icon name from https://remixicon.com such as "book-open".',
        );
    }

    public static function styledImageIcon(string $column): static
    {
        return static::structured(
            code: 'larasell-admin.table.column.styled-image-icon',
            message: "Column [{$column}] image shows an icon, which has no style.",
            why: 'Icons show as they are, without a background, so rounded() and style() have nothing to shape. Only pictures at a url are round or square.',
            fix: 'Remove rounded() or style() from the image, or show a picture with url() instead of an icon.',
        );
    }

    public static function unsupportedImageUrl(string $column, string $url): static
    {
        $scheme = Str::before(trim($url), ':');

        return static::structured(
            code: 'larasell-admin.table.column.unsupported-image-url',
            message: "Column [{$column}] image url uses the [{$scheme}:] scheme, which cannot show a picture.",
            why: 'Table cells show the url in an <img>, which only loads http(s) urls, relative urls and data urls of images. Anything else would show as a broken picture.',
            fix: 'Pass an http(s) url, a relative url or a data:image/... url, e.g. with route() or Storage::url(), or return null for records without a picture.',
        );
    }
}
