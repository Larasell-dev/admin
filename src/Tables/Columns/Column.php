<?php

namespace Larasell\Admin\Tables\Columns;

use Closure;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JsonSerializable;
use Larasell\Admin\Exceptions\InvalidTableColumn;
use LogicException;

abstract class Column implements JsonSerializable
{
    /**
     * Takes the value and a record of the table's model, which map() lets callers type as that model.
     */
    protected ?Closure $map = null;

    /**
     * Takes a record of the table's model and an Image, and returns the IconImage or PictureImage it makes, or null.
     */
    protected ?Closure $image = null;

    final public function __construct(
        protected string $name,
        protected ?string $label = null,
    ) {}

    public static function make(string $name, ?string $label = null): static
    {
        return new static($name, $label);
    }

    abstract public function type(): string;

    public function name(): string
    {
        return $this->name;
    }

    public function label(): string
    {
        return $this->label ?? Str::ucfirst(str_replace('_', ' ', Str::snake($this->name)));
    }

    /**
     * Transform the column's value before it is shown. The callback receives
     * the attribute's (cast) value, or null when the model has no attribute
     * by the column's name, and the record, so it can compute a column:
     *
     *     TextColumn::make('name')->map(fn ($value, Contact $contact) => "{$contact->first_name} {$contact->last_name}")
     *
     * The record is always an instance of the table's model, so the callback
     * may type it as that model.
     *
     * @template TModel of Model
     *
     * @param  Closure(mixed, TModel): mixed  $callback
     */
    public function map(Closure $callback): static
    {
        if ($this->map !== null) {
            throw InvalidTableColumn::configuredTwice($this->name, 'map()');
        }

        $this->map = $callback;

        return $this;
    }

    public function value(Model $record): mixed
    {
        $value = $this->attribute($record);

        if ($this->map === null) {
            return $value;
        }

        $mapped = ($this->map)($value, $record);
        $this->assertUnmodified($record, 'map()');

        return $mapped;
    }

    /**
     * The (cast) attribute by the column's name, before map().
     */
    protected function attribute(Model $record): mixed
    {
        if ($this->map === null) {
            return $record->getAttribute($this->name);
        }

        try {
            return $record->getAttribute($this->name);
        } catch (MissingAttributeException) {
            // A computed column need not be an attribute, even on strict models.
            return null;
        }
    }

    /**
     * Show an image before the column's value: an icon, or a picture at a url.
     * The callback receives the record and an Image, and returns the image it
     * makes with it, or null to show none for the record:
     *
     *     TextColumn::make('title')->image(fn (Book $book, Image $image) => $image->url($book->cover_url))
     *     TextColumn::make('author')->image(fn (Book $book, Image $image) => $image->icon($book->author_id === null ? Icon::QuestionMark : Icon::QuillPen))
     *
     * The record is always an instance of the table's model, so the callback
     * may type it as that model.
     *
     * @template TModel of Model
     *
     * @param  Closure(TModel, Image): ?CellImage  $callback
     */
    public function image(Closure $callback): static
    {
        if ($this->image !== null) {
            throw InvalidTableColumn::configuredTwice($this->name, 'image()');
        }

        $this->image = $callback;

        return $this;
    }

    public function hasImage(): bool
    {
        return $this->image !== null;
    }

    /**
     * The image the column shows for the record, or null when the callback shows none for it.
     * Only for columns that have an image; see hasImage().
     */
    public function imageFor(Model $record): ?CellImage
    {
        // The table decides which columns have an image, with hasImage(), and only asks those.
        if ($this->image === null) {
            throw new LogicException("Column [{$this->name}] has no image(); check hasImage() before calling imageFor().");
        }

        $image = ($this->image)($record, new Image($this->name));
        $this->assertUnmodified($record, 'image()');

        if ($image !== null && ! $image instanceof CellImage) {
            throw InvalidTableColumn::invalidImage($this->name, get_debug_type($image));
        }

        return $image;
    }

    /**
     * Actions and rowLink() get the same record, so a change a callback makes would leak into them.
     */
    protected function assertUnmodified(Model $record, string $method): void
    {
        if ($record->isDirty()) {
            throw InvalidTableColumn::recordModified($this->name, array_keys($record->getDirty()), $method);
        }
    }

    /**
     * @return array{name: string, label: string, type: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name(),
            'label' => $this->label(),
            'type' => $this->type(),
        ];
    }
}
