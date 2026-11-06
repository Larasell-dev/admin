<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Platform\Sections\Exceptions\InvalidSection;
use Larasell\Admin\Platform\Sections\Exceptions\SectionRequestNotReady;
use Larasell\Admin\Platform\Sections\Facades\Section;
use Larasell\Admin\Platform\Sections\SectionContext;
use Larasell\Admin\Platform\Sections\SectionDefinition;
use Larasell\Admin\Platform\Sections\SectionPage;
use Larasell\Admin\Platform\Sections\SectionRequest;
use Larasell\Admin\Product\Events\ProductSaved;
use Larasell\Admin\Tests\Fixtures\Sections\GiftWrapRequest;
use Larasell\Admin\Tests\Fixtures\Sections\NotesPayload;
use Larasell\Admin\Tests\Fixtures\Sections\NotesProps;
use Larasell\Admin\Tests\Fixtures\Sections\NotesRequest;
use Larasell\Admin\Tests\Fixtures\Sections\UnusedRequest;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Price;

beforeEach(function () {
    config(['larasell.locale.supported' => [Locale::ENGLISH, Locale::GERMAN], 'larasell.locale.default' => Locale::ENGLISH]);

    $this->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');

    $this->boots = Product::create(['name' => ['en' => 'Boots', 'de' => 'Stiefel'], 'slug' => ['en' => 'boots', 'de' => 'stiefel'], 'price' => Price::of(1000)]);

    config(['app.name' => 'Acme']);

    // As plugins' service providers would, in the opposite order of their keys: one with a Props
    // class, one with a closure.
    Section::add(SectionPage::Product, 'notes')
        ->props(NotesProps::class)
        ->request(NotesRequest::class);
    Section::add(SectionPage::Product, 'gift-wrap')
        ->props(fn (?Model $product, SectionContext $context): array => ['locale' => $context->locale->value])
        ->request(GiftWrapRequest::class);
});

/**
 * @param  array<string, mixed>  $sections
 */
function saveBoots(array $sections = [], string $name = 'Rain Boots'): TestResponse
{
    return test()->put(route('larasell.admin.products.update', test()->boots), ['name' => $name, 'slug' => 'boots', 'status' => 'visible', 'sections' => $sections]);
}

it('gives the product page its sections in the order they were added, with props for the product in the content locale', function () {
    $this->put(route('larasell.admin.content-locale.update'), ['locale' => 'de'])->assertSessionHasNoErrors();

    $this->get(route('larasell.admin.products.edit', $this->boots))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('sections', [
            ['page' => 'product', 'key' => 'notes', 'props' => ['name' => 'Stiefel', 'app' => 'Acme']],
            ['page' => 'product', 'key' => 'gift-wrap', 'props' => ['locale' => 'de']],
        ]));

    $this->get(route('larasell.admin.products.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sections.0.props', ['name' => null, 'app' => 'Acme'])
            ->count('sections', 2));
});

it('hands listeners each section\'s prepared, validated input and its typed payload', function () {
    $saved = [];
    Event::listen(ProductSaved::class, function (ProductSaved $event) use (&$saved): void {
        $saved[] = [$event->created, $event->section(NotesRequest::class)->payload(), $event->section(GiftWrapRequest::class)->validated()];
    });

    saveBoots([
        // Fields without rules are left out, like the page's own.
        'notes' => ['note' => '  Ships in March ', 'unchecked' => 'x'],
        'gift-wrap' => ['wrap' => '1', 'note' => 'Happy birthday'],
    ])->assertSessionHasNoErrors();

    $this->post(route('larasell.admin.products.store'), ['name' => 'Socks', 'slug' => 'socks', 'status' => 'visible'])
        ->assertSessionHasNoErrors();

    expect($saved)->toEqual([
        [false, new NotesPayload('Ships in March', $this->boots->id), ['wrap' => '1', 'note' => 'Happy birthday']],
        // Nothing posted: the notes section's own prepareForValidation() made the note empty.
        [true, new NotesPayload(null, null), []],
    ])->and($saved[1][1]->note)->toBeNull()
        ->and($saved[0][1]->note)->toBe('Ships in March');
});

it('validates each section on its own fields, with the product\'s, and reports errors on the right section', function () {
    Event::listen(ProductSaved::class, fn () => throw new LogicException('Not saved while a field is invalid.'));

    saveBoots([
        'notes' => ['note' => str_repeat('a', 21)],
        // "wrap" is the gift wrap's own: the notes section's note doesn't satisfy its required_if.
        'gift-wrap' => ['wrap' => '1'],
    ], name: '')->assertSessionHasErrors([
        'name',
        'sections.notes.note' => 'Keep the internal note short.',
        'sections.gift-wrap.note' => 'The note field is required when wrap is 1.',
    ]);

    saveBoots(['notes' => ['note' => 'secret']])
        ->assertSessionHasErrors(['sections.notes.note' => 'No secrets.'])
        ->assertSessionDoesntHaveErrors('sections.gift-wrap.note');

    expect($this->boots->fresh()->name->get('en'))->toBe('Boots');
});

it('saves nothing when a section fails while saving, so the product and its sections stay in step', function () {
    Event::listen(ProductSaved::class, fn () => throw ValidationException::withMessages(['sections.notes.note' => 'The note service is down.']));

    saveBoots()->assertSessionHasErrors(['sections.notes.note' => 'The note service is down.']);

    $this->post(route('larasell.admin.products.store'), ['name' => 'Socks', 'slug' => 'socks', 'status' => 'visible'])
        ->assertSessionHasErrors('sections.notes.note');

    expect($this->boots->fresh()->name->get('en'))->toBe('Boots')
        ->and(Product::query()->count())->toBe(1);
});

it('keeps the sections\' payloads for queued listeners, without the HTTP request', function () {
    $queued = null;
    Event::listen(ProductSaved::class, function (ProductSaved $event) use (&$queued): void {
        $queued = unserialize(serialize($event));
    });

    saveBoots(['notes' => ['note' => 'Ships in March']])->assertSessionHasNoErrors();

    expect($queued->section(NotesRequest::class)->payload())->toEqual(new NotesPayload('Ships in March', $this->boots->id))
        ->and($queued->section(NotesRequest::class)->input())->toBe([]);
});

it('refuses a section key that is taken or would nest its fields', function (string $key, string $code) {
    expect(fn () => Section::add(SectionPage::Product, $key))
        ->toThrow(fn (InvalidSection $exception) => expect($exception->errorCode())->toBe($code));
})->with([
    ['notes', 'larasell-admin.section.duplicate-key'],
    ['acme.notes', 'larasell-admin.section.invalid-key'],
    ['acme[notes]', 'larasell-admin.section.invalid-key'],
    ['', 'larasell-admin.section.invalid-key'],
]);

it('refuses a section request or props class of the wrong kind', function (Closure $configure, string $code) {
    expect(fn () => $configure(Section::add(SectionPage::Product, 'other')))
        ->toThrow(fn (InvalidSection $exception) => expect($exception->errorCode())->toBe($code));
})->with([
    'request' => [fn (SectionDefinition $section) => $section->request(Product::class), 'larasell-admin.section.invalid-request'],
    'props' => [fn (SectionDefinition $section) => $section->props(Product::class), 'larasell-admin.section.invalid-props'],
]);

it('says which request a listener asked for when the page has no section validated by it', function () {
    Event::listen(ProductSaved::class, fn (ProductSaved $event) => $event->section(UnusedRequest::class));

    $this->withoutExceptionHandling();

    expect(fn () => saveBoots())
        ->toThrow(fn (InvalidSection $exception) => expect($exception->errorCode())->toBe('larasell-admin.section.unknown-request'));
});

it('says a section request isn\'t validated yet when its input is read too early', function () {
    expect(fn () => (new NotesRequest)->validated())
        ->toThrow(fn (SectionRequestNotReady $exception) => expect($exception->errorCode())->toBe('larasell-admin.section.request-not-validated'));
});

class ProductNotesRequestStub extends SectionRequest {}
