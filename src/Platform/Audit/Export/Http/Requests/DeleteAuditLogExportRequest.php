<?php

namespace Larasell\Admin\Platform\Audit\Export\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Larasell\Admin\Platform\Audit\Export\Models\AuditLogExport;

/**
 * Only the admin who asked for an export may delete it, once they typed the confirmation.
 *
 * @internal
 */
class DeleteAuditLogExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var AuditLogExport $export */
        $export = $this->route('export');

        return $export->admin_id === $this->user('larasell-admin')?->getKey();
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['confirmation' => ['nullable', 'string']];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $expected = __('Delete export');

            if ($this->string('confirmation')->trim()->value() !== $expected) {
                $validator->errors()->add('confirmation', __('Type ":text" to confirm.', ['text' => $expected]));
            }
        }];
    }
}
