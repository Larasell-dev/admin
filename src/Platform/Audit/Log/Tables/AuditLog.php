<?php

namespace Larasell\Admin\Platform\Audit\Log\Tables;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Audit\Log\Models\AuditLogEntry;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Platform\Tables\Columns\TextColumn;
use Larasell\Admin\Platform\Tables\Table;

/**
 * @internal The audit log page in the settings.
 */
class AuditLog extends Table
{
    /**
     * Names of admins, and of the actors modules register, such as API tokens, by ID, looked up once per page.
     *
     * @var array<string, array<string, ?string>>
     */
    private array $names = [];

    /**
     * Validated by AuditLogFiltersRequest. Days are in the app's time zone.
     *
     * @param  array{event?: string, actor_type?: string, actor_id?: string, subject_type?: string, subject_id?: string, from?: string, until?: string}  $filters
     */
    public function __construct(private readonly array $filters = []) {}

    /**
     * @return Builder<AuditLogEntry>
     */
    protected function model(): Builder
    {
        return AuditLogEntry::query()->filtered($this->filters)->latest('occurred_at');
    }

    public function title(): string
    {
        return __('Audit log');
    }

    public function columns(): array
    {
        return [
            // With the zone: records are stored in UTC, and shown in the shop's time.
            TextColumn::make('occurred_at', __('Time (:timezone)', ['timezone' => config('app.timezone')]))
                ->map(fn (CarbonImmutable $occurredAt): string => $occurredAt->setTimezone(config('app.timezone'))->format('M j, Y H:i:s')),
            TextColumn::make('event', __('Event')),
            TextColumn::make('actor', __('Actor'))->map(fn (mixed $value, AuditLogEntry $entry): string => $this->actor($entry)),
            // The stable type, as the subject filter offers it.
            TextColumn::make('subject', __('Subject'))->map(fn (mixed $value, AuditLogEntry $entry): ?string => $entry->subject_type === null ? null : $entry->subject_type.' #'.$entry->subject_id),
            TextColumn::make('source', __('Source'))->map(fn (string $source): string => in_array($source, ['api', 'cli'], true) ? Str::upper($source) : Str::ucfirst($source)),
            TextColumn::make('details', __('Details'))->map(fn (mixed $value, AuditLogEntry $entry): ?string => is_array($entry->properties)
                ? $this->details($entry->properties)
                // Unreadable, so changed outside the app: shown as stored, for whoever looks into it.
                : (string) $entry->getRawOriginal('properties')),
        ];
    }

    public function emptyState(): array
    {
        return [
            'title' => __('No audit records yet'),
            'description' => __('Security- and business-critical changes, such as creating API tokens, show up here.'),
        ];
    }

    private function actor(AuditLogEntry $entry): string
    {
        return match ($entry->actor_type) {
            'admin' => $this->named(__('Admin'), Admin::class, $entry->actor_id),
            'system' => __('System: :id', ['id' => $entry->actor_id]),
            // The reason is stored with the record, so it stays as it was written.
            'unknown' => __('Unknown: :reason', ['reason' => $entry->unknown_actor_reason]),
            default => $this->registeredActor($entry->actor_type, $entry->actor_id),
        };
    }

    private function registeredActor(string $type, ?string $id): string
    {
        $actorType = app(Auditor::class)->findActorType($type);

        return $actorType === null
            ? Str::ucfirst(str_replace('_', ' ', $type)).' #'.$id
            // Registered while the app boots, so translated now, in the request's language.
            : $this->named(__($actorType['label']), $actorType['model'], $id);
    }

    /**
     * "Admin: Ada Lovelace", or "Admin #5" once the admin or token is deleted.
     *
     * @param  class-string<Model>  $model
     */
    private function named(string $label, string $model, ?string $id): string
    {
        $this->names[$model] ??= [];

        if (! array_key_exists((string) $id, $this->names[$model])) {
            $this->names[$model][(string) $id] = $model::query()->whereKey($id)->value('name');
        }

        $name = $this->names[$model][(string) $id];

        return $name === null
            ? __(':actor #:id', ['actor' => $label, 'id' => $id])
            : __(':actor: :name', ['actor' => $label, 'name' => $name]);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function details(array $properties): ?string
    {
        if ($properties === []) {
            return null;
        }

        return implode(', ', array_map(
            fn (string $key, mixed $value): string => $key.': '.(is_string($value) || is_int($value) || is_float($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            array_keys($properties),
            $properties,
        ));
    }
}
