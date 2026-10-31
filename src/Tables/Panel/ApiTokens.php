<?php

namespace Larasell\Admin\Tables\Panel;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Larasell\Admin\Actions\RevokeApiToken;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\Table\Variant;
use Larasell\Admin\Models\ApiToken;
use Larasell\Admin\Tables\Actions\Action;
use Larasell\Admin\Tables\Columns\BadgeColumn;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;

/**
 * @internal The API tokens table in the settings.
 */
class ApiTokens extends Table
{
    /**
     * @return Builder<ApiToken>
     */
    protected function model(): Builder
    {
        return ApiToken::query()->latest('id');
    }

    public function title(): string
    {
        return 'API tokens';
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name'),
            TextColumn::make('description'),
            // Computed, so an expired token doesn't read like a working one.
            BadgeColumn::make('status')
                ->map(fn ($value, ApiToken $apiToken): string => $apiToken->isExpired() ? 'Expired' : 'Active')
                ->variant(fn ($value, ApiToken $apiToken): Variant => $apiToken->isExpired() ? Variant::Danger : Variant::Success),
            // With the time and zone: a token ends at 23:59 in the app's time zone, which may not be the admin's.
            TextColumn::make('expires_at', 'Expires ('.config('app.timezone').')')->map(fn (Carbon $expiresAt): string => $expiresAt->format('M j, Y H:i')),
            TextColumn::make('last_used_at', 'Last used')->map(fn (?Carbon $lastUsedAt): string => $lastUsedAt?->diffForHumans() ?? 'Never'),
        ];
    }

    public function actions(): array
    {
        return [
            Action::make('Revoke', handle: fn (ApiToken $apiToken) => app(RevokeApiToken::class)->handle($apiToken))
                ->confirm(
                    title: 'Revoke API token',
                    message: 'Apps using it can no longer call the API. This can\'t be undone.',
                    confirmButton: 'Revoke',
                    confirmationText: fn (ApiToken $apiToken): string => $apiToken->name,
                )
                ->icon(Icon::DeleteBin)
                ->destructive(),
        ];
    }

    public function emptyState(): array
    {
        return [
            'title' => 'No API tokens yet',
            'description' => 'Create a token for each app that calls the API.',
            'icon' => Icon::Key,
        ];
    }
}
