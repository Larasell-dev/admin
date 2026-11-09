<?php

namespace Larasell\Admin\Platform\Audit\Log\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Laravel\Pennant\Feature;

/**
 * The audit log page's filters. Only admins who may view the log
 * get this far; filters are ignored while the audit-log-filters feature is off.
 *
 * @internal
 */
class AuditLogFiltersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user('larasell-admin'))->allows('larasell.admin.view-audit-log');
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        if (! $this->filtersEnabled()) {
            return [];
        }

        return [
            'event' => ['nullable', 'string', 'max:255'],
            'actor_type' => ['nullable', 'string', 'max:255', 'required_with:actor_id'],
            'actor_id' => ['nullable', 'string', 'max:255'],
            'subject_type' => ['nullable', 'string', 'max:255', 'required_with:subject_id'],
            'subject_id' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    public function filtersEnabled(): bool
    {
        return Feature::for($this->user('larasell-admin'))->active('audit-log-filters');
    }

    /**
     * The filters in use, once the larasell.admin.search-audit-log gate allows them.
     *
     * @return array{event?: string, actor_type?: string, actor_id?: string, subject_type?: string, subject_id?: string, from?: string, until?: string}
     */
    public function filters(): array
    {
        /** @var array{event?: string, actor_type?: string, actor_id?: string, subject_type?: string, subject_id?: string, from?: string, until?: string} $filters */
        $filters = array_filter($this->validated(), fn (mixed $value): bool => $value !== null);

        if ($filters !== []) {
            Gate::forUser($this->user('larasell-admin'))->authorize('larasell.admin.search-audit-log');
        }

        return $filters;
    }
}
