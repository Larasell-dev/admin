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
use Larasell\Larasell\Catalog\Actions\UpdateCategoryPayload;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Visibility;

/**
 * Updates a category's name and visibility; it keeps its place in the tree, see MoveCategoryRequest.
 */
class UpdateCategoryRequest extends FormRequest
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
        ];
    }

    /**
     * The category's name in the content locale, keeping its other translations.
     */
    public function payload(Category $category, Locale $locale): UpdateCategoryPayload
    {
        return new UpdateCategoryPayload(
            name: $category->name->with($locale->value, $this->validated('name')),
            status: Visibility::from($this->validated('status')),
            attachments: $this->attachments(),
        );
    }

    /**
     * The payloads of the page's sections, passed along with the core's action.
     */
    protected function attachments(): Attachments
    {
        return new Attachments(...$this->sections()->payloads());
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
