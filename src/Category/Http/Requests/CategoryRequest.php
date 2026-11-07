<?php

namespace Larasell\Admin\Category\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Platform\Sections\SectionContext;
use Larasell\Admin\Platform\Sections\SectionForm;
use Larasell\Admin\Platform\Sections\SectionRequests;
use Larasell\Admin\Platform\Sections\Sections;
use Larasell\Larasell\Attachments\Attachments;
use Larasell\Larasell\Catalog\Actions\CreateCategoryPayload;
use Larasell\Larasell\Catalog\Actions\UpdateCategoryPayload;
use Larasell\Larasell\Catalog\CategoryId;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Translatable;

class CategoryRequest extends FormRequest
{
    private ?SectionRequests $sections = null;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(Visibility::class)],
            // Only creating reads it; a category keeps its place in the tree when it's updated.
            'parent_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
        ];
    }

    /**
     * A new category with its name in the content locale, inside its parent when given.
     */
    public function createPayload(Locale $locale): CreateCategoryPayload
    {
        return new CreateCategoryPayload(
            name: Translatable::fromString($this->validated('name'), $locale->value),
            status: Visibility::from($this->validated('status')),
            parentId: $this->validated('parent_id') === null ? null : new CategoryId((int) $this->validated('parent_id')),
            attachments: new Attachments(...$this->sections()->payloads()),
        );
    }

    /**
     * The category's name in the content locale, keeping its other translations.
     */
    public function updatePayload(Category $category, Locale $locale): UpdateCategoryPayload
    {
        return new UpdateCategoryPayload(
            name: $category->name->with($locale->value, $this->validated('name')),
            status: Visibility::from($this->validated('status')),
            attachments: new Attachments(...$this->sections()->payloads()),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->sections()->prepare($this);
    }

    /**
     * Validates the page's sections with the category's own fields.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [$this->sections()->validate()];
    }

    /**
     * The requests of the page's sections, whose payloads go along with the core's actions.
     */
    public function sections(): SectionRequests
    {
        return $this->sections ??= app()->call(fn (Sections $sections, #[CurrentContentLocale] Locale $locale): SectionRequests => $sections->requests(
            SectionForm::Category,
            $this,
            $this->route('category') instanceof Category ? $this->route('category') : null,
            new SectionContext($locale),
        ));
    }
}
