<?php

namespace Larasell\Admin\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Larasell\Admin\Payloads\MoveCategoryPayload;
use Larasell\Larasell\Models\Category;

class MoveCategoryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists(Category::class, 'id'),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->isWithinMovedCategory((int) $value)) {
                        $fail('A category cannot be moved into itself or a category below it.');
                    }
                },
            ],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function payload(): MoveCategoryPayload
    {
        return new MoveCategoryPayload(
            parentId: $this->filled('parent_id') ? $this->integer('parent_id') : null,
            position: $this->filled('position') ? $this->integer('position') : null,
        );
    }

    /**
     * Whether the given category is the moved category itself or one below it, by walking up its parents.
     */
    protected function isWithinMovedCategory(int $id): bool
    {
        /** @var Category $category */
        $category = $this->route('category');

        return DB::query()->fromRaw(
            '(with recursive ancestors (id, parent_id) as (
                select id, parent_id from larasell_categories where id = ?
                union all
                select parent.id, parent.parent_id from larasell_categories as parent join ancestors on parent.id = ancestors.parent_id
            ) select id from ancestors) as ancestors',
            [$id],
        )->where('id', $category->getKey())->exists();
    }
}
