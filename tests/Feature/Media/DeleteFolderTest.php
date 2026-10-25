<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Actions\Media\DeleteFolder;
use Larasell\Admin\Models\Media\Folder;
use Larasell\Larasell\Models\ProductImage;

function folder(string $name, ?Folder $parent = null): Folder
{
    return Folder::create(['name' => $name, 'parent_id' => $parent?->id]);
}

it('deletes the folder and everything below it', function () {
    $campaigns = folder('Campaigns');
    $summer = folder('Summer', $campaigns);
    $beach = folder('Beach', $summer);
    $june = folder('June', $beach);
    $winter = folder('Winter', $campaigns);
    $banners = folder('Banners');
    $spring = folder('Spring', $campaigns);

    app(DeleteFolder::class)->handle($summer);

    expect(Folder::query()->orderBy('id')->pluck('id')->all())
        ->toBe([$campaigns->id, $winter->id, $banners->id, $spring->id])
        ->and(Folder::query()->whereKey([$beach->id, $june->id])->exists())->toBeFalse();
});

it('deletes a folder without contents', function () {
    $campaigns = folder('Campaigns');
    $summer = folder('Summer', $campaigns);

    app(DeleteFolder::class)->handle($summer);

    expect(Folder::query()->pluck('id')->all())->toBe([$campaigns->id]);
});

it('fires deleting and deleted for every deleted folder and no other', function () {
    $campaigns = folder('Campaigns');
    $summer = folder('Summer', $campaigns);
    $beach = folder('Beach', $summer);
    $june = folder('June', $beach);
    $august = folder('August', $summer);
    folder('Winter', $campaigns);
    folder('Banners');

    $deleting = [];
    $deleted = [];
    Folder::deleting(function (Folder $folder) use (&$deleting) {
        $deleting[] = $folder->id;
    });
    Folder::deleted(function (Folder $folder) use (&$deleted) {
        $deleted[] = $folder->id;
    });

    app(DeleteFolder::class)->handle($summer);

    $expected = [$summer->id, $beach->id, $june->id, $august->id];

    expect($deleting)->toEqualCanonicalizing($expected)
        ->and($deleted)->toEqualCanonicalizing($expected);
});

it('fires the events for folders before their parent, while the parent still exists', function () {
    $campaigns = folder('Campaigns');
    $summer = folder('Summer', $campaigns);
    $beach = folder('Beach', $summer);
    $june = folder('June', $beach);
    $august = folder('August', $summer);

    $events = [];
    Folder::deleting(function (Folder $folder) use (&$events) {
        // The parent is still there, so listeners see the tree intact.
        $events[] = ['deleting', $folder->id, $folder->parent()->exists()];
    });
    Folder::deleted(function (Folder $folder) use (&$events) {
        $events[] = ['deleted', $folder->id];
    });

    app(DeleteFolder::class)->handle($summer);

    $position = fn (string $event, int $id): ?int => array_key_first(array_filter(
        $events,
        fn (array $entry): bool => $entry[0] === $event && $entry[1] === $id,
    ));

    // Each folder's deleting comes right before its own deleted.
    foreach ([$summer, $beach, $june, $august] as $folder) {
        expect($position('deleted', $folder->id))->toBe($position('deleting', $folder->id) + 1);
    }

    expect($position('deleted', $june->id))->toBeLessThan($position('deleting', $beach->id))
        ->and($position('deleted', $beach->id))->toBeLessThan($position('deleting', $summer->id))
        ->and($position('deleted', $august->id))->toBeLessThan($position('deleting', $summer->id))
        ->and(array_column(array_filter($events, fn (array $entry): bool => $entry[0] === 'deleting'), 2))
        ->each->toBeTrue();
});

it('deletes the product images in the folder and below it, with their files', function () {
    Storage::fake(config('larasell.images.disk'));
    $disk = Storage::disk(config('larasell.images.disk'));
    $image = fn (string $name): ProductImage => ProductImage::create(['file' => UploadedFile::fake()->image($name)]);

    $campaigns = folder('Campaigns');
    $summer = folder('Summer', $campaigns);
    $beach = folder('Beach', $summer);
    $summer->productImages()->attach($inSummer = $image('summer.jpg'));
    $beach->productImages()->attach($inBeach = $image('beach.jpg'));
    $campaigns->productImages()->attach($inCampaigns = $image('campaigns.jpg'));
    $topLevel = $image('top.jpg');

    app(DeleteFolder::class)->handle($summer);

    expect(ProductImage::query()->orderBy('id')->pluck('id')->all())->toBe([$inCampaigns->id, $topLevel->id]);
    $disk->assertMissing([$inSummer->file, $inBeach->file]);
    $disk->assertExists([$inCampaigns->file, $topLevel->file]);
});
