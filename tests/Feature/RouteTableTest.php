<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;

class TeamTable extends Table
{
    protected ?string $model = Admin::class;

    public function columns(): array
    {
        return [TextColumn::make('name')];
    }
}

beforeEach(function () {
    AdminPanel::resolve()->routes(function () {
        Route::table('settings/team', TeamTable::class);
    });

    Route::getRoutes()->refreshNameLookups();
});

it('renders the table page for a table route', function () {
    $admin = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);

    expect(route('larasell.admin.settings.team.index', absolute: false))->toBe('/admin/settings/team');

    $this->actingAs($admin, 'larasell-admin')
        ->get('/admin/settings/team')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('TablePage')
            ->where('table.title', 'Team table')
            ->where('table.columns', [['name' => 'name', 'label' => 'Name', 'type' => 'text']])
            ->where('table.rows', [['id' => $admin->id, 'name' => 'Ada Lovelace']]));
});

it('requires a signed-in admin', function () {
    $this->get('/admin/settings/team')->assertRedirect('/admin/login');
});
