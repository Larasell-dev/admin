<?php

namespace Larasell\Admin\Enums\Media;

enum MediaType: string
{
    case Folder = 'folder';
    case ProductImage = 'product-image';

    public function label(): string
    {
        return match ($this) {
            self::Folder => 'Folder',
            self::ProductImage => 'Product image',
        };
    }
}
