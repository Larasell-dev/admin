<?php

namespace Workbench\App\ProductNotes;

use Illuminate\Validation\Validator;
use Larasell\Admin\Platform\Sections\SectionRequest;

/**
 * Validates the notes section when the product page is saved. Like a FormRequest, but it only
 * sees its own fields: "note" is the field the page posts as sections[workbench-notes][note].
 */
class ProductNotesRequest extends SectionRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['note' => 'internal note'];
    }

    /**
     * Spaces around the note don't count, so a note of only spaces removes it.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('note'))) {
            $this->merge(['note' => trim($this->input('note')) ?: null]);
        }
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (str_contains((string) $this->input('note'), '@')) {
                    $validator->errors()->add('note', 'Notes are for the team; keep customer emails out of them.');
                }
            },
        ];
    }

    /**
     * Null when the note isn't posted, e.g. while the section's component isn't built.
     */
    public function payload(): ?ProductNotePayload
    {
        return $this->has('note') ? new ProductNotePayload($this->validated('note')) : null;
    }
}
