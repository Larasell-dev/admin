<?php

namespace Larasell\Admin\Tables\Panel\Media;

use Illuminate\Database\Eloquent\Builder;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\Media\MediaType;
use Larasell\Admin\Models\Media\Folder;
use Larasell\Admin\Models\Media\MediaItem;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;

/**
 * The media in a folder, or at the top level without one: folders first, then product images, each by name.
 */
class Media extends Table
{
    public function __construct(
        public ?Folder $folder = null,
    ) {}

    public function title(): string
    {
        return $this->folder->name ?? 'Media';
    }

    public function backLink(): ?array
    {
        if ($this->folder === null) {
            return null;
        }

        $parent = $this->folder->parent;

        return [
            'url' => $parent === null
                ? route('larasell.admin.media.index')
                : route('larasell.admin.media.folders.show', $parent),
            'label' => 'Back to '.($parent->name ?? 'Media'),
        ];
    }

    /**
     * @return Builder<MediaItem>
     */
    protected function model(): Builder
    {
        // Lowercased so that "banners" sorts between "Archive" and "Zoo" on every database.
        return MediaItem::query()
            ->where('folder_id', $this->folder?->getKey())
            ->orderBy('type_order')->orderByRaw('lower(name)');
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name')->map(fn (string $name, MediaItem $item): string => $item->type === MediaType::ProductImage ? basename($name) : $name),
            TextColumn::make('type')->map(fn (MediaType $type): string => $type->label()),
        ];
    }

    public function rowLink(MediaItem $item): ?string
    {
        return $item->type === MediaType::Folder
            ? route('larasell.admin.media.folders.show', $item->id)
            : null;
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
