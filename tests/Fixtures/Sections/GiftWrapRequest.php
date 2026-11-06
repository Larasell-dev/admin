<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures\Sections;

use Larasell\Admin\Platform\Sections\SectionRequest;

/** Has a "note" field too, which is its own and not the notes section's. */
class GiftWrapRequest extends SectionRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'wrap' => ['boolean'],
            'note' => ['nullable', 'string', 'required_if:wrap,1'],
        ];
    }
}
