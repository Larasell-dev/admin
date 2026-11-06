<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures\Sections;

use Illuminate\Validation\Validator;
use Larasell\Admin\Platform\Sections\SectionRequest;

class NotesRequest extends SectionRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ['note' => ['nullable', 'string', 'max:20']];
    }

    public function messages(): array
    {
        return ['note.max' => 'Keep the :attribute short.'];
    }

    public function attributes(): array
    {
        return ['note' => 'internal note'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['note' => trim((string) $this->input('note')) ?: null]);
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('note') === 'secret') {
                $validator->errors()->add('note', 'No secrets.');
            }
        }];
    }

    public function payload(): NotesPayload
    {
        return new NotesPayload($this->validated('note'), $this->resource()?->getKey());
    }
}
