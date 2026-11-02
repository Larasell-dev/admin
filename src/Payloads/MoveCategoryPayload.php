<?php

namespace Larasell\Admin\Payloads;

/**
 * Where a category moves: under the parent, or to the root without one, at the position among
 * its new siblings, counted without itself. Without a position it goes last.
 */
final readonly class MoveCategoryPayload
{
    public function __construct(
        public ?int $parentId,
        public ?int $position,
    ) {}
}
