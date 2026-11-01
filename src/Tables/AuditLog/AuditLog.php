<?php

namespace Larasell\Admin\Tables\AuditLog;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Models\ApiToken;
use Larasell\Admin\Models\AuditLogEntry;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;

/**
 * @internal The audit log page in the settings.
 */
class AuditLog extends Table
{
    /**
     * Names of admins and API tokens by ID, looked up once per page.
     *
     * @var array<string, array<string, ?string>>
     */
    private array $names = [];

    /**
     * Validated by AuditLogController. Days are in the app's time zone.
     *
     * @param  array{event?: string, actor_type?: string, actor_id?: string, subject_type?: string, subject_id?: string, from?: string, until?: string}  $filters
     */
    public function __construct(private readonly array $filters = []) {}

    /**
     * @return Builder<AuditLogEntry>
     */
    protected function model(): Builder
    {
        $timezone = config('app.timezone');
        $day = fn (string $date): CarbonImmutable => CarbonImmutable::parse($date, $timezone)->startOfDay();
        $utc = fn (CarbonImmutable $time): string => $time->utc()->format('Y-m-d H:i:s.u');

        return AuditLogEntry::query()
            ->latest('occurred_at')
            ->when($this->filters['event'] ?? null, fn (Builder $query, string $event) => $query->where('event', $event))
            ->when($this->filters['actor_type'] ?? null, fn (Builder $query, string $type) => $query->where('actor_type', $type))
            ->when($this->filters['actor_id'] ?? null, fn (Builder $query, string $id) => $query->where('actor_id', $id))
            ->when($this->filters['subject_type'] ?? null, fn (Builder $query, string $type) => $query->where('subject_type', $type))
            ->when($this->filters['subject_id'] ?? null, fn (Builder $query, string $id) => $query->where('subject_id', $id))
            ->when($this->filters['from'] ?? null, fn (Builder $query, string $from) => $query->where('occurred_at', '>=', $utc($day($from))))
            ->when($this->filters['until'] ?? null, fn (Builder $query, string $until) => $query->where('occurred_at', '<', $utc($day($until)->addDay())));
    }

    public function title(): string
    {
        return 'Audit log';
    }

    public function columns(): array
    {
        return [
            // With the zone: records are stored in UTC, and shown in the shop's time.
            TextColumn::make('occurred_at', 'Time ('.config('app.timezone').')')
                ->map(fn (CarbonImmutable $occurredAt): string => $occurredAt->setTimezone(config('app.timezone'))->format('M j, Y H:i:s')),
            TextColumn::make('event'),
            TextColumn::make('actor')->map(fn (mixed $value, AuditLogEntry $entry): string => $this->actor($entry)),
            // The stable type, as the subject filter offers it.
            TextColumn::make('subject')->map(fn (mixed $value, AuditLogEntry $entry): ?string => $entry->subject_type === null ? null : $entry->subject_type.' #'.$entry->subject_id),
            TextColumn::make('source')->map(fn (string $source): string => in_array($source, ['api', 'cli'], true) ? Str::upper($source) : Str::ucfirst($source)),
            TextColumn::make('details')->map(fn (mixed $value, AuditLogEntry $entry): ?string => $this->details($entry->properties)),
        ];
    }

    public function emptyState(): array
    {
        return [
            'title' => 'No audit records yet',
            'description' => 'Security- and business-critical changes, such as creating API tokens, show up here.',
        ];
    }

    private function actor(AuditLogEntry $entry): string
    {
        return match ($entry->actor_type) {
            'admin' => 'Admin'.$this->name(Admin::class, $entry->actor_id),
            'api_token' => 'API token'.$this->name(ApiToken::class, $entry->actor_id),
            'system' => 'System: '.$entry->actor_id,
            'unknown' => 'Unknown: '.$entry->unknown_actor_reason,
            default => Str::ucfirst(str_replace('_', ' ', $entry->actor_type)).' #'.$entry->actor_id,
        };
    }

    /**
     * ": Ada Lovelace", or " #5" once the admin or token is deleted.
     *
     * @param  class-string<Admin|ApiToken>  $model
     */
    private function name(string $model, ?string $id): string
    {
        $this->names[$model] ??= [];

        if (! array_key_exists((string) $id, $this->names[$model])) {
            $this->names[$model][(string) $id] = $model::query()->whereKey($id)->value('name');
        }

        $name = $this->names[$model][(string) $id];

        return $name === null ? ' #'.$id : ': '.$name;
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
