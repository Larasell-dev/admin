<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Models\Media\Folder;

function mediaAdmin(): Admin
{
    return Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
}

it('tells the media page where to create folders, and inside which one', function () {
    $campaigns = Folder::create(['name' => 'Campaigns']);
    $this->actingAs(mediaAdmin(), 'larasell-admin');

    $this->get('/admin/media')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('createFolderUrl', url('/admin/media/folders'))
        ->where('folderId', null));

    $this->get("/admin/media/folders/{$campaigns->id}")->assertInertia(fn (AssertableInertia $page) => $page
        ->where('createFolderUrl', url('/admin/media/folders'))
        ->where('folderId', $campaigns->id));
});

it('creates a top-level folder and returns to the media page', function () {
    $this->actingAs(mediaAdmin(), 'larasell-admin')
        ->from('/admin/media')
        ->post('/admin/media/folders', ['name' => 'Banners', 'parent_id' => null])
        ->assertRedirect('/admin/media');

    expect(Folder::query()->sole())
        ->name->toBe('Banners')
        ->parent_id->toBeNull();
});

it('creates a folder inside another one and returns to that folder', function () {
    Folder::create(['name' => 'Other']);
    $campaigns = Folder::create(['name' => 'Campaigns']);

    $this->actingAs(mediaAdmin(), 'larasell-admin')
        ->post('/admin/media/folders', ['name' => 'Summer', 'parent_id' => $campaigns->id])
        ->assertRedirect("/admin/media/folders/{$campaigns->id}");

    expect(Folder::query()->where('name', 'Summer')->sole()->parent_id)->toBe($campaigns->id);
});

it('requires a name', function () {
    $this->actingAs(mediaAdmin(), 'larasell-admin')
        ->from('/admin/media')
        ->post('/admin/media/folders', ['name' => ''])
        ->assertRedirect('/admin/media')
        ->assertSessionHasErrors('name');

    expect(Folder::query()->count())->toBe(0);
});

it('rejects a parent folder that does not exist', function () {
    $this->actingAs(mediaAdmin(), 'larasell-admin')
        ->from('/admin/media')
        ->post('/admin/media/folders', ['name' => 'Summer', 'parent_id' => 999])
        ->assertRedirect('/admin/media')
        ->assertSessionHasErrors('parent_id');

    expect(Folder::query()->count())->toBe(0);
});

it('requires a signed-in admin to create a folder', function () {
    $this->post('/admin/media/folders', ['name' => 'Banners'])->assertRedirect('/admin/login');

    expect(Folder::query()->count())->toBe(0);
});
