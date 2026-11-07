<?php

namespace Larasell\Admin\Category\Http\Requests;

use Illuminate\Validation\Rule;
use Larasell\Larasell\Catalog\Actions\CreateCategoryPayload;
use Larasell\Larasell\Catalog\CategoryId;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Translatable;

class CreateCategoryRequest extends CategoryRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'parent_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
        ];
    }

    /**
     * A new category with its name in the content locale, inside its parent when given.
     */
    public function payload(Locale $locale): CreateCategoryPayload
    {
        return new CreateCategoryPayload(
            name: Translatable::fromString($this->validated('name'), $locale->value),
            status: Visibility::from($this->validated('status')),
            parentId: $this->validated('parent_id') === null ? null : new CategoryId((int) $this->validated('parent_id')),
            attachments: $this->attachments(),
        );
    }
}
