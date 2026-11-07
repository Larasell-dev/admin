<?php

namespace Larasell\Admin\Category\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Platform\Sections\SectionContext;
use Larasell\Admin\Platform\Sections\SectionForm;
use Larasell\Admin\Platform\Sections\SectionRequests;
use Larasell\Admin\Platform\Sections\Sections;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Visibility;

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
     * The requests of the page's sections, which the category's saved event hands to them.
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
