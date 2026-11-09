<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Settings\Exceptions\InvalidSettingsPage;
use Larasell\Admin\Platform\Settings\Facades\Settings;

beforeEach(function () {
    $this->ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
});

/**
 * Boots the panel with an extension's settings page, as its service provider would add it.
 */
function bootWithWishlists(Closure $place): void
{
    test()->bootingWith(function () use ($place): void {
        Route::get('acme/wishlists', fn () => '')->name('acme.wishlists.settings');
        $place(Settings::add('acme-wishlists')
            ->label('Wishlists')
            ->icon(Icon::Heart)
            ->route('acme.wishlists.settings')
            ->description('Who can share wishlists'));
    });
    test()->ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
}

it('lists the settings pages on the settings index', function () {
    // Act & Assert
    $this->actingAs($this->ada, 'larasell-admin')
        ->get(route('larasell.admin.settings'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Platform/Panel/Pages/Settings')
            ->where('settingsPages.0', [
                'id' => 'team',
                'label' => 'Team',
                'description' => 'The admins who can sign in to the panel',
                'icon' => 'Team',
                'url' => route('larasell.admin.settings.team.index'),
            ])
            ->where('settingsPages', fn ($pages) => collect($pages)->pluck('id')->all() === ['team', 'api-tokens', 'audit-log', 'mux']));
});

it('places an extension\'s settings page relative to the panel\'s own', function () {
    // Arrange
    bootWithWishlists(fn ($page) => $page->before('api-tokens'));

    // Act & Assert
    $this->actingAs($this->ada, 'larasell-admin')
        ->get(route('larasell.admin.settings'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('settingsPages', fn ($pages) => collect($pages)->pluck('id')->all() === ['team', 'acme-wishlists', 'api-tokens', 'audit-log', 'mux'])
            ->where('settingsPages.1.url', route('acme.wishlists.settings')));
});

it('puts an unplaced settings page last', function () {
    // Arrange
    // After the panel's providers, as an app's providers boot.
    Route::get('acme/wishlists', fn () => '')->name('acme.wishlists.settings');
    Settings::add('acme-wishlists')->label('Wishlists')->icon(Icon::Heart)->route('acme.wishlists.settings');

    // Act & Assert
    $this->actingAs($this->ada, 'larasell-admin')
        ->get(route('larasell.admin.settings'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('settingsPages', fn ($pages) => collect($pages)->pluck('id')->all() === ['team', 'api-tokens', 'audit-log', 'mux', 'acme-wishlists']));
});

it('leaves API tokens off the settings when they are turned off', function () {
    // Arrange
    $this->bootingWith(fn () => config(['larasell.admin.api_tokens.enabled' => false]));
    $this->ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);

    // Act & Assert
    $this->actingAs($this->ada, 'larasell-admin')
        ->get(route('larasell.admin.settings'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('settingsPages', fn ($pages) => collect($pages)->pluck('id')->all() === ['team', 'mux', 'audit-log']));
});

it('shows Settings in the sidebar without nested items', function () {
    // Act & Assert
    $this->actingAs($this->ada, 'larasell-admin')
        ->get(route('larasell.admin.home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('navigation', fn ($items) => collect($items)->firstWhere('label', 'Settings')['children'] === []));
});

it('shares the settings pages on every page for the command menu', function () {
    // Act & Assert
    $this->actingAs($this->ada, 'larasell-admin')
        ->get(route('larasell.admin.home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('settingsPages', fn ($pages) => collect($pages)->pluck('label')->all() === ['Team', 'API tokens', 'Audit log', 'Mux'])
            ->where('settingsUrl', route('larasell.admin.settings')));
});

it('rejects a settings page id that is taken or malformed', function (string $id) {
    // Act & Assert
    expect(fn () => Settings::add($id))->toThrow(InvalidSettingsPage::class);
})->with(['team', 'Acme Wishlists', '']);

it('rejects a settings page without a label, icon or route', function () {
    // Arrange
    $this->bootingWith(fn () => Settings::add('acme-wishlists')->icon(Icon::Heart));

    // Act & Assert
    $this->withoutExceptionHandling();
    expect(fn () => $this->actingAs(Admin::first() ?? Admin::create(['name' => 'Ada', 'email' => 'a@example.com', 'password' => 'password']), 'larasell-admin')
        ->get(route('larasell.admin.settings')))->toThrow(InvalidSettingsPage::class);
});
