<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Platform\Sections\Exceptions\InvalidSection;
use Larasell\Admin\Platform\Sections\Exceptions\SectionRequestNotReady;
use Larasell\Admin\Platform\Sections\Facades\Section;
use Larasell\Admin\Platform\Sections\SectionContext;
use Larasell\Admin\Platform\Sections\SectionDefinition;
use Larasell\Admin\Platform\Sections\SectionForm;
use Larasell\Admin\Platform\Sections\SectionRequest;
use Larasell\Admin\Platform\Sections\SectionRequests;
use Larasell\Admin\Product\Enums\ProductSection;
use Larasell\Admin\Product\Events\ProductSaved;
use Larasell\Admin\Product\Exceptions\ProductNotSavedAtomically;
use Larasell\Admin\Tests\Fixtures\Sections\GiftWrapRequest;
use Larasell\Admin\Tests\Fixtures\Sections\MeddlingRequest;
use Larasell\Admin\Tests\Fixtures\Sections\NotesPayload;
use Larasell\Admin\Tests\Fixtures\Sections\NotesProps;
use Larasell\Admin\Tests\Fixtures\Sections\NotesRequest;
use Larasell\Admin\Tests\Fixtures\Sections\UnusedRequest;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Price;

beforeEach(function () {
    config(['larasell.locale.supported' => [Locale::ENGLISH, Locale::GERMAN], 'larasell.locale.default' => Locale::ENGLISH]);

    $this->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');

    $this->boots = Product::create(['name' => ['en' => 'Boots', 'de' => 'Stiefel'], 'price' => Price::of(1000)]);

    config(['app.name' => 'Acme']);

    // As plugins' service providers would, in the opposite order of their keys: one with a Props
    // class, one with a closure.
    Section::add(SectionForm::Product, 'notes')
        ->props(NotesProps::class)
        ->request(NotesRequest::class);
    Section::add(SectionForm::Product, 'gift-wrap')
        ->props(fn (?Model $product, SectionContext $context): array => ['locale' => $context->locale->value])
        ->request(GiftWrapRequest::class);
});

/**
 * @param  array<string, mixed>  $sections
 */
function saveBoots(array $sections = [], string $name = 'Rain Boots'): TestResponse
{
    return test()->put(route('larasell.admin.products.update', test()->boots), ['name' => $name, 'status' => 'visible', 'sections' => $sections]);
}

it('gives the product page its sections, with props for the product in the content locale', function () {
    // Arrange
    $this->put(route('larasell.admin.content-locale.update'), ['locale' => 'de'])->assertSessionHasNoErrors();

    // Act & Assert
    $this->get(route('larasell.admin.products.edit', $this->boots))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('sections', [
            ['form' => 'product', 'key' => 'gift-wrap', 'props' => ['locale' => 'de'], 'before' => null, 'after' => null],
            ['form' => 'product', 'key' => 'notes', 'props' => ['name' => 'Stiefel', 'app' => 'Acme'], 'before' => null, 'after' => null],
            // The panel's own URLs section, added the same way; no storefront sells in German here.
            ['form' => 'product', 'key' => 'seo', 'props' => ['urls' => []], 'before' => null, 'after' => 'identifiers'],
        ]));

    $this->get(route('larasell.admin.products.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sections.1.props', ['name' => null, 'app' => 'Acme'])
            ->count('sections', 3));
});

it('places sections before or after the product form\'s own sections, ordered by position, then by key', function () {
    // Arrange
    // Registered against the order they end up in, so neither the order of registration nor
    // that of the calls on a section decides it.
    Section::add(SectionForm::Product, 'zeta')->after(ProductSection::Stock);
    Section::add(SectionForm::Product, 'alpha')->after(ProductSection::Stock);
    Section::add(SectionForm::Product, 'late')->position(10)->after(ProductSection::Stock);
    Section::add(SectionForm::Product, 'early')->after(ProductSection::Stock)->position(-5);
    Section::add(SectionForm::Product, 'moved')->after(ProductSection::Stock)->before(ProductSection::General);

    // Act & Assert
    $this->get(route('larasell.admin.products.edit', $this->boots))
        ->assertInertia(fn (AssertableInertia $page) => $page->where(
            'sections',
            fn (Collection $sections) => $sections->map(fn (array $section): array => [$section['key'], $section['before'], $section['after']])->all() === [
                ['early', null, 'stock'],
                ['alpha', null, 'stock'],
                ['gift-wrap', null, null],
                ['moved', 'general', null],
                ['notes', null, null],
                ['seo', null, 'identifiers'],
                ['zeta', null, 'stock'],
                ['late', null, 'stock'],
            ],
        ));
});

it('refuses to place a section next to something that isn\'t one of the product form\'s own sections', function () {
    // Arrange
    Section::add(SectionForm::Product, 'misplaced')->after(Locale::GERMAN);

    $this->withoutExceptionHandling();

    // Act & Assert
    expect(fn () => $this->get(route('larasell.admin.products.edit', $this->boots)))
        ->toThrow(fn (InvalidSection $exception) => expect($exception->errorCode())->toBe('larasell-admin.section.unknown-anchor')
            ->and($exception->getMessage())->toContain('Locale::GERMAN')
            ->and($exception->errorFix())->toContain(ProductSection::class));
});

it('hands listeners each section\'s prepared, validated input and its typed payload', function () {
    // Arrange
    $saved = [];
    Event::listen(ProductSaved::class, function (ProductSaved $event) use (&$saved): void {
        $saved[] = [$event->created, $event->section(NotesRequest::class)->payload(), $event->section(GiftWrapRequest::class)->validated()];
    });

    // Act
    saveBoots([
        // Fields without rules are left out, like the page's own.
        'notes' => ['note' => '  Ships in March ', 'unchecked' => 'x'],
        'gift-wrap' => ['wrap' => '1', 'note' => 'Happy birthday'],
    ])->assertSessionHasNoErrors();

    $this->post(route('larasell.admin.products.store'), ['name' => 'Socks', 'status' => 'visible'])
        ->assertSessionHasNoErrors();

    // Assert
    expect($saved)->toEqual([
        [false, new NotesPayload('Ships in March', $this->boots->id), ['wrap' => '1', 'note' => 'Happy birthday']],
        // Nothing posted: the notes section's own prepareForValidation() made the note empty.
        [true, new NotesPayload(null, null), []],
    ])->and($saved[1][1]->note)->toBeNull()
        ->and($saved[0][1]->note)->toBe('Ships in March');
});

it('validates each section on its own fields, with the product\'s, and reports errors on the right section', function () {
    // Arrange
    Event::listen(ProductSaved::class, fn () => throw new LogicException('Not saved while a field is invalid.'));

    // Act & Assert
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
    // Arrange
    Event::listen(ProductSaved::class, fn () => throw ValidationException::withMessages(['sections.notes.note' => 'The note service is down.']));

    // Act & Assert
    saveBoots()->assertSessionHasErrors(['sections.notes.note' => 'The note service is down.']);

    $this->post(route('larasell.admin.products.store'), ['name' => 'Socks', 'status' => 'visible'])
        ->assertSessionHasErrors('sections.notes.note');

    expect($this->boots->fresh()->name->get('en'))->toBe('Boots')
        ->and(Product::query()->count())->toBe(1);
});

it('keeps the sections\' payloads for queued listeners, without the HTTP request', function () {
    // Arrange
    $queued = null;
    Event::listen(ProductSaved::class, function (ProductSaved $event) use (&$queued): void {
        $queued = unserialize(serialize($event));
    });

    // Act
    saveBoots(['notes' => ['note' => 'Ships in March']])->assertSessionHasNoErrors();

    // Assert
    expect($queued->section(NotesRequest::class)->payload())->toEqual(new NotesPayload('Ships in March', $this->boots->id))
        ->and($queued->section(NotesRequest::class)->input())->toBe([]);
});

it('refuses a section key that is taken or would nest its fields', function (string $key, string $code) {
    // Act & Assert
    expect(fn () => Section::add(SectionForm::Product, $key))
        ->toThrow(fn (InvalidSection $exception) => expect($exception->errorCode())->toBe($code));
})->with([
    ['notes', 'larasell-admin.section.duplicate-key'],
    ['acme.notes', 'larasell-admin.section.invalid-key'],
    ['acme[notes]', 'larasell-admin.section.invalid-key'],
    ['', 'larasell-admin.section.invalid-key'],
]);

it('refuses a section request or props class of the wrong kind', function (Closure $configure, string $code) {
    // Act & Assert
    expect(fn () => $configure(Section::add(SectionForm::Product, 'other')))
        ->toThrow(fn (InvalidSection $exception) => expect($exception->errorCode())->toBe($code));
})->with([
    'request' => [fn (SectionDefinition $section) => $section->request(Product::class), 'larasell-admin.section.invalid-request'],
    'props' => [fn (SectionDefinition $section) => $section->props(Product::class), 'larasell-admin.section.invalid-props'],
]);

it('says which request a listener asked for when the page has no section validated by it', function () {
    // Arrange
    Event::listen(ProductSaved::class, fn (ProductSaved $event) => $event->section(UnusedRequest::class));

    $this->withoutExceptionHandling();

    // Act & Assert
    expect(fn () => saveBoots())
        ->toThrow(fn (InvalidSection $exception) => expect($exception->errorCode())->toBe('larasell-admin.section.unknown-request'));
});

it('says a section request isn\'t validated yet when its input is read too early', function () {
    // Act & Assert
    expect(fn () => (new NotesRequest)->validated())
        ->toThrow(fn (SectionRequestNotReady $exception) => expect($exception->errorCode())->toBe('larasell-admin.section.request-not-validated'));
});

class ProductNotesRequestStub extends SectionRequest {}

it('leaves a wrongly set up section off the form in production, and keeps the form working', function () {
    // Arrange
    $this->app['env'] = 'production';
    config(['app.debug' => false]);
    Exceptions::fake();

    Section::add(SectionForm::Product, 'notes'); // taken: the first one keeps the key
    Section::add(SectionForm::Product, 'acme.notes');
    Section::add(SectionForm::Product, 'bad-props')->props(Product::class);
    Section::add(SectionForm::Product, 'misplaced')->after(Locale::GERMAN);

    // Act & Assert
    $this->get(route('larasell.admin.products.edit', $this->boots))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where(
            'sections',
            fn (Collection $sections) => $sections->map(fn (array $section): array => [$section['key'], $section['after']])->all() === [
                ['gift-wrap', null],
                // Placed after the form's own sections instead.
                ['misplaced', null],
                ['notes', null],
                ['seo', 'identifiers'],
            ],
        ));

    saveBoots(['notes' => ['note' => 'Ships in March']])->assertSessionHasNoErrors();

    foreach (['duplicate-key', 'invalid-key', 'invalid-props', 'unknown-anchor'] as $code) {
        Exceptions::assertReported(fn (InvalidSection $exception) => $exception->errorCode() === "larasell-admin.section.{$code}");
    }
});

it('refuses to tell listeners a product was saved outside the save\'s transaction', function () {
    // Act & Assert
    expect(fn () => new ProductSaved($this->boots, false, new SectionContext(Locale::ENGLISH), new SectionRequests([])))
        ->toThrow(fn (ProductNotSavedAtomically $exception) => expect($exception->errorCode())->toBe('larasell-admin.product.saved-outside-transaction'));
});

it('catches a section that changes another section\'s input while preparing', function () {
    // Arrange
    Section::add(SectionForm::Product, 'meddler')->request(MeddlingRequest::class);

    $this->withoutExceptionHandling();

    // Act & Assert
    expect(fn () => saveBoots(['notes' => ['note' => 'Ships in March']]))
        ->toThrow(fn (InvalidSection $exception) => expect($exception->errorCode())->toBe('larasell-admin.section.changed-other-input')
            ->and($exception->getMessage())->toContain('[meddler]'));
});
