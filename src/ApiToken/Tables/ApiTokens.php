<?php

namespace Larasell\Admin\ApiToken\Tables;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Larasell\Admin\ApiToken\Actions\RevokeApiToken;
use Larasell\Admin\ApiToken\Models\ApiToken;
use Larasell\Admin\Platform\Audit\Origins;
use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Tables\Actions\Action;
use Larasell\Admin\Platform\Tables\Columns\BadgeColumn;
use Larasell\Admin\Platform\Tables\Columns\TextColumn;
use Larasell\Admin\Platform\Tables\Enums\Variant;
use Larasell\Admin\Platform\Tables\Table;

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
        return __('API tokens');
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name', __('Name')),
            TextColumn::make('description', __('Description')),
            // Computed, so an expired token doesn't read like a working one.
            BadgeColumn::make('status', __('Status'))
                ->map(fn ($value, ApiToken $apiToken): string => $apiToken->isExpired() ? __('Expired') : __('Active'))
                ->variant(fn ($value, ApiToken $apiToken): Variant => $apiToken->isExpired() ? Variant::Danger : Variant::Success),
            // With the time and zone: a token ends at 23:59 in the app's time zone, which may not be the admin's.
            TextColumn::make('expires_at', __('Expires (:timezone)', ['timezone' => config('app.timezone')]))->map(fn (CarbonInterface $expiresAt): string => $expiresAt->format('M j, Y H:i')),
            TextColumn::make('last_used_at', __('Last used'))->map(fn (?CarbonInterface $lastUsedAt): string => $lastUsedAt?->diffForHumans() ?? __('Never')),
        ];
    }

    public function actions(): array
    {
        return [
            Action::make(Translate::later('Revoke'), handle: fn (ApiToken $apiToken) => app(RevokeApiToken::class)->handle($apiToken, app(Origins::class)->current()))
                ->confirm(
                    title: __('Revoke API token'),
                    message: __('Apps using it can no longer call the API. This can\'t be undone.'),
                    confirmButton: __('Revoke'),
                    confirmationText: fn (ApiToken $apiToken): string => $apiToken->name,
                )
                ->icon(Icon::DeleteBin)
                ->destructive(),
        ];
    }

    public function emptyState(): array
    {
        return [
            'title' => __('No API tokens yet'),
            'description' => __('Create a token for each app that calls the API.'),
            'icon' => Icon::Key,
        ];
    }
}
