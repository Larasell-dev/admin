<?php

namespace Larasell\Admin\Tables\Panel\Media;

use Illuminate\Database\Eloquent\Builder;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\Media\MediaType;
use Larasell\Admin\Models\Media\MediaItem;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;

/**
 * The media library: folders first, then product images, each by name.
 */
class Media extends Table
{
    /**
     * @return Builder<MediaItem>
     */
    protected function model(): Builder
    {
        // Lowercased so that "banners" sorts between "Archive" and "Zoo" on every database.
        return MediaItem::query()->orderBy('type_order')->orderByRaw('lower(name)');
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name')->map(fn (string $name, MediaItem $item): string => $item->type === MediaType::ProductImage ? basename($name) : $name),
            TextColumn::make('type')->map(fn (MediaType $type): string => $type->label()),
        ];
    }

    public function emptyState(): array
    {
        return [
            'title' => 'No media yet',
            'description' => 'Folders and product images show up here.',
            'icon' => Icon::Image,
        ];
    }
}
