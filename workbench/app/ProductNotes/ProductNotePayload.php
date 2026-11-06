<?php

namespace Workbench\App\ProductNotes;

/**
 * The product's note as the page saved it; null removes it.
 */
final readonly class ProductNotePayload
{
    public function __construct(
        public ?string $note,
    ) {}
}
