<?php

namespace Larasell\Admin\Platform\Audit\Export\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Larasell\Admin\Platform\Audit\Export\Models\AuditLogExport;
use Larasell\Admin\Platform\Panel\Rules\Confirmation;

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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ['confirmation' => [new Confirmation(__('Delete export'))]];
    }
}
