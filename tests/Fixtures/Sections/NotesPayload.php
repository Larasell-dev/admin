<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures\Sections;

final readonly class NotesPayload
{
    public function __construct(
        public ?string $note,
        public int|string|null $productId,
    ) {}
}
