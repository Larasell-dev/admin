<?php

namespace Larasell\Admin\Tables\Panel\Media;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\Media\MediaType;
use Larasell\Admin\Models\Media\Folder;
use Larasell\Admin\Models\Media\MediaItem;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
use Larasell\Larasell\Models\ProductImage;

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
            // An ISO timestamp, so the browser shows it in the admin's own locale and timezone.
            TextColumn::make('added_at')->map(fn (?Carbon $addedAt): ?string => $addedAt?->toIso8601String()),
        ];
    }

    public function rowLink(MediaItem $item): ?string
    {
        return match ($item->type) {
            MediaType::Folder => route('larasell.admin.media.folders.show', $item->id),
            // Larasell's own url(), so the link follows its images disk; the row's name is the image's file.
            MediaType::ProductImage => (new ProductImage)->setRawAttributes(['file' => $item->name])->url(),
        };
    }

    /**
     * Each row also names its type and where to delete it, since the media page draws its own actions.
     */
    public function jsonSerialize(): array
    {
        $table = parent::jsonSerialize();

        $table['rows'] = array_map(function (array $row): array {
            // The key is the type and id, e.g. "product-image-12".
            $type = MediaType::from(Str::beforeLast($row['id'], '-'));
            $id = Str::afterLast($row['id'], '-');

            return [
                ...$row,
                '_type' => $type->value,
                '_deleteUrl' => match ($type) {
                    MediaType::Folder => route('larasell.admin.media.folders.destroy', $id),
                    MediaType::ProductImage => route('larasell.admin.media.product-images.destroy', $id),
                },
            ];
        }, $table['rows']);

        return $table;
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
