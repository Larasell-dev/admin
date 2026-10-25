<?php

namespace Larasell\Admin\Tables\Columns;

use Larasell\Admin\Enums\Icon;

/**
 * An icon shown as it is, in the text's colour, without a background or shape,
 * so unlike a PictureImage it has no style. Made with Image::icon().
 */
class IconImage extends CellImage
{
    /**
     * @internal Use Image::icon().
     */
    public function __construct(string $column, protected Icon $icon)
    {
        parent::__construct($column);
    }

    /**
     * The icon is sent as its case name, which @larasell-dev/ui/icons exports with a Fill or Line suffix.
     *
     * @return array{type: 'icon', icon: string, style: null, alt: ?string, title: ?string}
     */
    public function jsonSerialize(): array
    {
        return ['type' => 'icon', 'icon' => $this->icon->name, 'style' => null, 'alt' => $this->alt, 'title' => $this->title];
    }
}
