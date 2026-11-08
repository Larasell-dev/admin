<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Media\Models\MediaPlaceholder;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Media\Actions\UploadMedia;
use Larasell\Larasell\Media\Actions\UploadMediaPayload;
use Larasell\Larasell\Media\AssetVisibility;
use Larasell\Larasell\Media\Models\Media;
use Larasell\Larasell\Translatable;

beforeEach(function () {
    Storage::fake('media');
    Storage::fake('private-media');
    config([
        'larasell.media.disks' => ['public' => 'media', 'private' => 'private-media'],
        'larasell.locale.supported' => [Locale::ENGLISH, Locale::GERMAN],
        'larasell.locale.default' => Locale::ENGLISH,
    ]);
});

function signInMediaAdmin(): void
{
    test()->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');
}

/** @param  array<string, string>|null  $alt */
function mediaFile(UploadedFile $file, ?array $alt = null, AssetVisibility $visibility = AssetVisibility::Public): Media
{
    return app(UploadMedia::class)->handle(new UploadMediaPayload($file, $alt === null ? null : new Translatable($alt), $visibility));
}

it('sends guests to the sign-in page', function () {
    // Act & Assert
    $this->get(route('larasell.admin.media.index'))
        ->assertRedirect(route('larasell.admin.login'));
});

it('lists the media newest first, with what the browser needs to show each', function () {
    // Arrange
    signInMediaAdmin();
    $boots = mediaFile(UploadedFile::fake()->image('boots.png'), ['en' => 'Boots', 'de' => 'Stiefel']);
    $unboxing = mediaFile(UploadedFile::fake()->create('unboxing.mp4', 100, 'video/mp4'));

    // Act & Assert
    $this->get(route('larasell.admin.media.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Media/Pages/Index')
            ->where('storeUrl', route('larasell.admin.media.store'))
            ->where('media', [
                [
                    'id' => $unboxing->id,
                    'type' => 'video',
                    'fileName' => 'unboxing.mp4',
                    'url' => $unboxing->original->url(),
                    'thumbnailUrl' => null,
                    'thumbHash' => null,
                    'title' => null,
                    'alt' => null,
                    'visibility' => 'public',
                    'showUrl' => route('larasell.admin.media.show', $unboxing),
                    'moveUrl' => route('larasell.admin.media.move', $unboxing),
                    'moveToNewFolderUrl' => route('larasell.admin.media.move-to-new-folder', $unboxing),
                ],
                [
                    'id' => $boots->id,
                    'type' => 'image',
                    'fileName' => 'boots.png',
                    'url' => $boots->original->url(),
                    // Made right away, as the tests' queue is sync.
                    'thumbnailUrl' => $boots->thumbnail()->sole()->url(),
                    'thumbHash' => MediaPlaceholder::query()->where('media_id', $boots->id)->value('thumbhash'),
                    'title' => null,
                    'alt' => 'Boots',
                    'visibility' => 'public',
                    'showUrl' => route('larasell.admin.media.show', $boots),
                    'moveUrl' => route('larasell.admin.media.move', $boots),
                    'moveToNewFolderUrl' => route('larasell.admin.media.move-to-new-folder', $boots),
                ],
            ])
            ->where('pagination', ['page' => 1, 'pageSize' => 24, 'total' => 2]));
});

it('pages through the media, newest first, with the total for the page numbers', function () {
    // Arrange
    signInMediaAdmin();
    $oldest = mediaFile(UploadedFile::fake()->image('oldest.png'));
    foreach (range(1, 24) as $number) {
        mediaFile(UploadedFile::fake()->image("{$number}.png"));
    }

    // Act & Assert
    $this->get(route('larasell.admin.media.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('media', 24)
            ->where('media.0.fileName', '24.png')
            ->where('pagination', ['page' => 1, 'pageSize' => 24, 'total' => 25]));

    $this->get(route('larasell.admin.media.index', ['page' => 2]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('media', fn ($media) => collect($media)->pluck('id')->all() === [$oldest->id])
            ->where('pagination', ['page' => 2, 'pageSize' => 24, 'total' => 25]));
});

it('shows as many per page as asked, out of the sizes it offers', function (int $asked, int $shown) {
    // Arrange
    signInMediaAdmin();
    foreach (range(1, 50) as $number) {
        mediaFile(UploadedFile::fake()->image("{$number}.png"));
    }

    // Act & Assert
    $this->get(route('larasell.admin.media.index', ['per_page' => $asked]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('media', $shown)
            ->where('pagination.pageSize', $shown));
})->with([
    'an offered size' => [48, 48],
    'one it does not offer' => [1000, 24],
]);

it('shows the last page when asked for one past it', function () {
    // Arrange
    signInMediaAdmin();
    $only = mediaFile(UploadedFile::fake()->image('only.png'));

    // Act & Assert
    $this->get(route('larasell.admin.media.index', ['page' => 5]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('media.0.id', $only->id)
            ->where('pagination.page', 1));
});

it('uploads images and videos into the library, privately', function () {
    // Arrange
    signInMediaAdmin();

    // Act & Assert
    $this->post(route('larasell.admin.media.store'), [
        'files' => [
            UploadedFile::fake()->image('boots.jpg', 300, 200),
            UploadedFile::fake()->create('unboxing.webm', 100, 'video/webm'),
        ],
    ])->assertRedirect(route('larasell.admin.media.index'));

    $media = Media::query()->with('original')->orderBy('id')->get();
    expect($media->map(fn (Media $media): array => [$media->type->value, $media->original->file_name])->all())
        ->toBe([['image', 'boots.jpg'], ['video', 'unboxing.webm']]);
    expect($media->map(fn (Media $media): AssetVisibility => $media->original->visibility)->all())
        ->toBe([AssetVisibility::Private, AssetVisibility::Private]);
    Storage::disk('private-media')->assertExists($media[0]->original->path);
    expect(Storage::disk('media')->allFiles())->toBe([]);
});

it('refuses files that are not images or videos', function (UploadedFile $file) {
    // Arrange
    signInMediaAdmin();

    // Act & Assert
    $this->from(route('larasell.admin.media.index'))
        ->post(route('larasell.admin.media.store'), ['files' => [UploadedFile::fake()->image('boots.png'), $file]])
        ->assertSessionHasErrors('files.1');
    expect(Media::query()->count())->toBe(0);
})->with([
    'a PDF' => fn () => UploadedFile::fake()->create('manual.pdf', 10, 'application/pdf'),
    'an SVG' => fn () => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
]);

it('requires at least one file', function () {
    // Arrange
    signInMediaAdmin();

    // Act & Assert
    $this->from(route('larasell.admin.media.index'))
        ->post(route('larasell.admin.media.store'), ['files' => []])
        ->assertSessionHasErrors('files');
});

it('shows a media item with its alt text in the content locale and its assets', function () {
    // Arrange
    signInMediaAdmin();
    $boots = mediaFile(UploadedFile::fake()->image('boots.png', 640, 480), ['en' => 'Boots', 'de' => 'Stiefel']);
    $original = $boots->original;
    $thumbnail = $boots->thumbnail()->sole();

    // Act & Assert
    $this->get(route('larasell.admin.media.show', $boots))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Media/Pages/Show')
            ->where('media', [
                'id' => $boots->id,
                'type' => 'image',
                'fileName' => 'boots.png',
                'url' => $original->url(),
                'title' => null,
                'description' => null,
                'alt' => 'Boots',
                'visibility' => 'public',
                'assets' => [
                    [
                        'role' => 'original',
                        'visibility' => 'public',
                        'fileName' => 'boots.png',
                        'mimeType' => 'image/png',
                        'size' => $original->size,
                        'width' => 640,
                        'height' => 480,
                        'url' => $original->url(),
                    ],
                    [
                        'role' => 'thumbnail',
                        'visibility' => 'public',
                        'fileName' => 'boots.webp',
                        'mimeType' => 'image/webp',
                        'size' => $thumbnail->size,
                        'width' => 400,
                        'height' => 300,
                        'url' => $thumbnail->url(),
                    ],
                ],
            ])
            ->where('updateUrl', route('larasell.admin.media.update', $boots))
            ->where('destroyUrl', route('larasell.admin.media.destroy', $boots))
            ->where('indexUrl', route('larasell.admin.media.index')));
});

it('changes the alt text in the content locale and keeps its other translations', function () {
    // Arrange
    signInMediaAdmin();
    $boots = mediaFile(UploadedFile::fake()->image('boots.png'), ['en' => 'Boots']);
    $this->put(route('larasell.admin.content-locale.update'), ['locale' => 'de']);

    // Act & Assert
    $this->put(route('larasell.admin.media.update', $boots), ['alt' => 'Stiefel'])
        ->assertRedirect(route('larasell.admin.media.show', $boots));
    expect($boots->fresh()?->alt?->all())->toBe(['en' => 'Boots', 'de' => 'Stiefel']);
});

it('removes the alt text of the content locale when it is emptied', function (?array $before, ?array $after) {
    // Arrange
    signInMediaAdmin();
    $boots = mediaFile(UploadedFile::fake()->image('boots.png'), $before);

    // Act
    $this->put(route('larasell.admin.media.update', $boots), ['alt' => ''])->assertSessionHasNoErrors();

    // Assert
    expect($boots->fresh()?->alt?->all())->toBe($after);
})->with([
    'its only one' => [['en' => 'Boots'], null],
    'one of several' => [['en' => 'Boots', 'de' => 'Stiefel'], ['de' => 'Stiefel']],
    'none yet' => [null, null],
]);

it('deletes a media item and its file', function () {
    // Arrange
    signInMediaAdmin();
    $boots = mediaFile(UploadedFile::fake()->image('boots.png'));
    $path = $boots->original->path;

    // Act & Assert
    $this->delete(route('larasell.admin.media.destroy', $boots), ['confirmation' => 'Delete media'])
        ->assertRedirect(route('larasell.admin.media.index'));
    expect(Media::query()->count())->toBe(0);
    Storage::disk('media')->assertMissing($path);
});

it('adds the media library to the navigation', function () {
    // Arrange
    signInMediaAdmin();

    // Act & Assert
    $this->get(route('larasell.admin.media.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('navigation', fn ($items) => collect($items)->contains(fn ($item) => $item['label'] === 'Media' && $item['url'] === route('larasell.admin.media.index'))));
});

it('refuses to delete a media item until "Delete media" is typed', function (?string $confirmation) {
    // Arrange
    signInMediaAdmin();
    $boots = mediaFile(UploadedFile::fake()->image('boots.png'));

    // Act & Assert
    $this->from(route('larasell.admin.media.show', $boots))
        ->delete(route('larasell.admin.media.destroy', $boots), ['confirmation' => $confirmation])
        ->assertSessionHasErrors('confirmation');
    expect(Media::query()->count())->toBe(1);
})->with([
    'nothing typed' => [null],
    'something else' => ['delete'],
    'in other case' => ['delete media'],
]);

it('shows private media through the panel, never by the file\'s own URL', function () {
    // Arrange
    signInMediaAdmin();
    $invoice = mediaFile(UploadedFile::fake()->image('invoice.png'), visibility: AssetVisibility::Private);
    $fileUrl = route('larasell.admin.media.file', $invoice);

    // Act & Assert
    $this->get(route('larasell.admin.media.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('media.0.url', $fileUrl)
            ->where('media.0.thumbnailUrl', route('larasell.admin.media.file', [$invoice, 'thumbnail']))
            ->where('media.0.visibility', 'private'));
    $this->get(route('larasell.admin.media.show', $invoice))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('media.url', $fileUrl)
            ->where('media.visibility', 'private')
            ->where('media.assets.0.url', $fileUrl)
            ->where('media.assets.0.visibility', 'private'));
});

it('streams a media item\'s file to admins, without letting anything cache it', function () {
    // Arrange
    signInMediaAdmin();
    $file = UploadedFile::fake()->image('invoice.png', 20, 10);
    $invoice = mediaFile($file, visibility: AssetVisibility::Private);

    // Act
    $response = $this->get(route('larasell.admin.media.file', $invoice));

    // Assert
    $response->assertOk()->assertHeader('Content-Type', 'image/png');
    expect($response->headers->get('Cache-Control'))->toContain('private')->toContain('no-store')
        ->and($response->streamedContent())->toBe(Storage::disk('private-media')->get($invoice->original->path));
});

it('streams the part of a file the browser asks for, so videos play and seek', function (string $range, int $start, int $end) {
    // Arrange
    signInMediaAdmin();
    $media = mediaFile(UploadedFile::fake()->image('boots.png', 40, 40), visibility: AssetVisibility::Private);
    $contents = Storage::disk('private-media')->get($media->original->path);
    $size = strlen($contents);
    [$start, $end] = [$start < 0 ? $size + $start : $start, $end < 0 ? $size + $end : $end];

    // Act
    $response = $this->get(route('larasell.admin.media.file', $media), ['Range' => $range]);

    // Assert
    $response->assertStatus(206)
        ->assertHeader('Accept-Ranges', 'bytes')
        ->assertHeader('Content-Range', "bytes {$start}-{$end}/{$size}")
        ->assertHeader('Content-Length', (string) ($end - $start + 1));
    expect($response->streamedContent())->toBe(substr($contents, $start, $end - $start + 1));
})->with([
    // Negative positions count from the end of the file, which the test doesn't know in advance.
    'a middle part' => ['bytes=4-9', 4, 9],
    'from a byte to the end' => ['bytes=15-', 15, -1],
    'the last bytes' => ['bytes=-3', -3, -1],
    'past the end' => ['bytes=10-999999', 10, -1],
]);

it('refuses a range outside the file', function () {
    // Arrange
    signInMediaAdmin();
    $media = mediaFile(UploadedFile::fake()->image('boots.png', 40, 40), visibility: AssetVisibility::Private);
    $size = $media->original->size;

    // Act & Assert
    $this->get(route('larasell.admin.media.file', $media), ['Range' => 'bytes='.($size + 10).'-'])
        ->assertStatus(416)
        ->assertHeader('Content-Range', "bytes */{$size}");
});

it('answers a script\'s upload with the new media\'s id', function () {
    // Arrange
    signInMediaAdmin();

    // Act
    $response = $this->postJson(route('larasell.admin.media.store'), ['files' => [UploadedFile::fake()->image('boots.png')]], ['Idempotency-Key' => (string) Str::uuid()]);

    // Assert
    $response->assertCreated()->assertJson(['id' => Media::query()->sole()->id]);
});

it('uploads a file once, however often the same upload is retried', function () {
    // Arrange
    signInMediaAdmin();
    $key = (string) Str::uuid();
    $send = fn () => $this->postJson(route('larasell.admin.media.store'), ['files' => [UploadedFile::fake()->image('boots.png')]], ['Idempotency-Key' => $key]);
    $first = $send()->json('id');

    // Act
    $retry = $send();

    // Assert
    $retry->assertCreated()->assertJson(['id' => $first]);
    expect(Media::query()->count())->toBe(1);
});

it('uploads files with different keys separately', function () {
    // Arrange
    signInMediaAdmin();
    $send = fn () => $this->postJson(route('larasell.admin.media.store'), ['files' => [UploadedFile::fake()->image('boots.png')]], ['Idempotency-Key' => (string) Str::uuid()]);

    // Act
    $send();
    $send();

    // Assert
    expect(Media::query()->count())->toBe(2);
});

it('refuses a key that is not a UUID', function () {
    // Arrange
    signInMediaAdmin();

    // Act & Assert
    $this->postJson(route('larasell.admin.media.store'), ['files' => [UploadedFile::fake()->image('boots.png')]], ['Idempotency-Key' => 'nope'])
        ->assertStatus(400);
    expect(Media::query()->count())->toBe(0);
});

it('refuses a script\'s upload that holds more than one file', function () {
    // Arrange
    signInMediaAdmin();

    // Act & Assert
    $this->postJson(route('larasell.admin.media.store'), ['files' => [UploadedFile::fake()->image('a.png'), UploadedFile::fake()->image('b.png')]], ['Idempotency-Key' => (string) Str::uuid()])
        ->assertUnprocessable();
    expect(Media::query()->count())->toBe(0);
});

it('keeps private files from guests', function () {
    // Arrange
    $invoice = mediaFile(UploadedFile::fake()->image('invoice.png'), visibility: AssetVisibility::Private);

    // Act & Assert
    $this->get(route('larasell.admin.media.file', $invoice))
        ->assertRedirect(route('larasell.admin.login'));
});

it('makes a media item public or private', function () {
    // Arrange
    signInMediaAdmin();
    $boots = mediaFile(UploadedFile::fake()->image('boots.png'), ['en' => 'Boots'], AssetVisibility::Private);

    // Act & Assert
    $this->put(route('larasell.admin.media.update', $boots), ['alt' => 'Boots', 'visibility' => 'public'])
        ->assertRedirect(route('larasell.admin.media.show', $boots));
    $original = $boots->fresh()?->original;
    expect($original?->visibility)->toBe(AssetVisibility::Public)
        ->and($boots->fresh()?->alt?->all())->toBe(['en' => 'Boots']);
    Storage::disk('media')->assertExists((string) $original?->path);
});

it('rejects visibilities that do not exist', function () {
    // Arrange
    signInMediaAdmin();
    $boots = mediaFile(UploadedFile::fake()->image('boots.png'));

    // Act & Assert
    $this->from(route('larasell.admin.media.show', $boots))
        ->put(route('larasell.admin.media.update', $boots), ['alt' => '', 'visibility' => 'secret'])
        ->assertSessionHasErrors('visibility');
});

it('streams a private thumbnail to admins', function () {
    // Arrange
    signInMediaAdmin();
    $invoice = mediaFile(UploadedFile::fake()->image('invoice.png', 800, 600), visibility: AssetVisibility::Private);
    $thumbnail = $invoice->thumbnail()->sole();

    // Act
    $response = $this->get(route('larasell.admin.media.file', [$invoice, 'thumbnail']));

    // Assert
    $response->assertOk()->assertHeader('Content-Type', 'image/webp');
    expect($response->streamedContent())->toBe(Storage::disk('private-media')->get($thumbnail->path));
});

it('answers 404 for a thumbnail not made yet', function () {
    // Arrange
    signInMediaAdmin();
    $unboxing = mediaFile(UploadedFile::fake()->create('unboxing.mp4', 100, 'video/mp4'), visibility: AssetVisibility::Private);

    // Act & Assert
    $this->get(route('larasell.admin.media.file', [$unboxing, 'thumbnail']))->assertNotFound();
});

it('says a file is too large instead of naming the rule', function () {
    // Arrange
    signInMediaAdmin();

    // Act & Assert
    $this->from(route('larasell.admin.media.index'))
        ->post(route('larasell.admin.media.store'), ['files' => [UploadedFile::fake()->create('huge.mp4', 102_401, 'video/mp4')]])
        ->assertSessionHasErrors(['files.0' => 'The file is too large to upload.']);
});

it('shows the title and description in the content locale, in the grid and on the item', function () {
    // Arrange
    signInMediaAdmin();
    $boots = app(UploadMedia::class)->handle(new UploadMediaPayload(
        UploadedFile::fake()->image('boots.png'),
        visibility: AssetVisibility::Public,
        title: new Translatable(['en' => 'Front', 'de' => 'Vorne']),
        description: new Translatable(['en' => 'Shot by Studio X.']),
    ));
    $this->put(route('larasell.admin.content-locale.update'), ['locale' => 'de']);

    // Act & Assert
    $this->get(route('larasell.admin.media.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('media.0.title', 'Vorne'));
    $this->get(route('larasell.admin.media.show', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('media.title', 'Vorne')
            // Not translated into German yet, so the field starts empty rather than in English.
            ->where('media.description', null));
});

it('saves the title and description in the content locale and keeps their other translations', function () {
    // Arrange
    signInMediaAdmin();
    $boots = app(UploadMedia::class)->handle(new UploadMediaPayload(
        UploadedFile::fake()->image('boots.png'),
        alt: new Translatable(['en' => 'Boots']),
        title: new Translatable(['en' => 'Front']),
        description: new Translatable(['en' => 'Shot by Studio X.']),
    ));
    $this->put(route('larasell.admin.content-locale.update'), ['locale' => 'de']);

    // Act
    $this->put(route('larasell.admin.media.update', $boots), ['alt' => '', 'title' => 'Vorne', 'description' => ''])
        ->assertSessionHasNoErrors();

    // Assert
    $fresh = $boots->fresh();
    expect($fresh?->title?->all())->toBe(['en' => 'Front', 'de' => 'Vorne'])
        ->and($fresh?->description?->all())->toBe(['en' => 'Shot by Studio X.'])
        ->and($fresh?->alt?->all())->toBe(['en' => 'Boots']);
});

it('limits the title and description', function (string $field, int $length) {
    // Arrange
    signInMediaAdmin();
    $boots = mediaFile(UploadedFile::fake()->image('boots.png'));

    // Act & Assert
    $this->from(route('larasell.admin.media.show', $boots))
        ->put(route('larasell.admin.media.update', $boots), [$field => str_repeat('a', $length)])
        ->assertSessionHasErrors($field);
})->with([
    'title' => ['title', 256],
    'description' => ['description', 2001],
]);
