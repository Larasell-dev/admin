<?php

namespace Larasell\Admin\Tables\Columns;

use Illuminate\Support\Str;
use Larasell\Admin\Enums\Table\ImageStyle;
use Larasell\Admin\Exceptions\InvalidTableColumn;

/**
 * The picture at a url, round or square. Made with Image::url() or one of its route methods.
 */
class PictureImage extends CellImage
{
    protected ImageStyle $style = ImageStyle::Square;

    protected string $url;

    /**
     * @internal Use Image::url() or one of its route methods.
     */
    public function __construct(string $column, string $url)
    {
        parent::__construct($column);

        if (Str::trim($url) === '') {
            throw InvalidTableColumn::emptyImageUrl($column);
        }

        if (! self::showsAsPicture($url)) {
            throw InvalidTableColumn::unsupportedImageUrl($column, $url);
        }

        $this->url = $url;
    }

    /**
     * Show the picture fully rounded, e.g. for an avatar. Same as style(ImageStyle::Round).
     */
    public function rounded(): static
    {
        return $this->style(ImageStyle::Round);
    }

    /**
     * Show the picture round or square. Pictures are square unless this or rounded() says otherwise.
     */
    public function style(ImageStyle $style): static
    {
        $this->configureOnce('rounded() or style()');
        $this->style = $style;

        return $this;
    }

    /**
     * @return array{type: 'url', url: string, style: string, alt: ?string, title: ?string}
     */
    public function jsonSerialize(): array
    {
        return ['type' => 'url', 'url' => $this->url, 'style' => $this->style->value, 'alt' => $this->alt, 'title' => $this->title];
    }

    /**
     * Whether an <img> can show the url: an http(s) url, a relative one, or a data url of an image.
     */
    private static function showsAsPicture(string $url): bool
    {
        // Read the scheme the way browsers do: they drop leading spaces and control
        // characters, and tabs and newlines anywhere, so " java\tscript:" is javascript:.
        $normalized = Str::lower((string) preg_replace('/[\t\n\r]/', '', ltrim($url, "\x00..\x20")));

        if (preg_match('/^([a-z][a-z0-9+.\-]*):/', $normalized, $match) !== 1) {
            return true;
        }

        return match ($match[1]) {
            'http', 'https' => true,
            'data' => str_starts_with($normalized, 'data:image/'),
            default => false,
        };
    }
}
