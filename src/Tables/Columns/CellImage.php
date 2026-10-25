<?php

namespace Larasell\Admin\Tables\Columns;

use JsonSerializable;
use Larasell\Admin\Exceptions\InvalidTableColumn;

/**
 * An image a column shows before its value in one row: an IconImage or a
 * PictureImage, made with the Image that Column::image() passes to its callback.
 */
abstract class CellImage implements JsonSerializable
{
    protected ?string $alt = null;

    protected ?string $title = null;

    /** @var array<int, string> The configuration methods called so far, which may each be called only once. */
    private array $configured = [];

    /**
     * @param  string  $column  The name of the column the image belongs to, for error messages.
     */
    public function __construct(protected string $column) {}

    /**
     * Name the image for screen readers, e.g. the person an avatar shows. Leave
     * it out when the image only decorates the column's value, which already says the same.
     */
    public function alt(string $alt): static
    {
        $this->configureOnce('alt()');
        $this->alt = $alt;

        return $this;
    }

    /**
     * Show a tooltip when hovering the image.
     */
    public function title(string $title): static
    {
        $this->configureOnce('title()');
        $this->title = $title;

        return $this;
    }

    /**
     * Every key is always sent: an icon's style is null, as is an unset alt text or title.
     *
     * @return array{type: 'icon', icon: string, style: null, alt: ?string, title: ?string}|array{type: 'url', url: string, style: string, alt: ?string, title: ?string}
     */
    abstract public function jsonSerialize(): array;

    protected function configureOnce(string $method): void
    {
        if (in_array($method, $this->configured, true)) {
            throw InvalidTableColumn::imageConfiguredTwice($this->column, $method);
        }

        $this->configured[] = $method;
    }
}
