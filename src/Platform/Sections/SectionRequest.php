<?php

namespace Larasell\Admin\Platform\Sections;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesAndRestoresModelIdentifiers;
use LogicException;

/**
 * Validates a section's fields when its page is saved, the way a FormRequest validates a page,
 * but scoped to the section: input(), merge() and validated() see only its own fields, and its
 * rules, messages, attributes and after() hooks name them without a prefix, e.g. "note" for
 * "sections[acme-notes][note]". Each section is validated on its own, together with the page,
 * so the page only saves when every section is valid, and sections can't see each other.
 *
 * Like a FormRequest, it may define these methods; the container calls rules() and after(), so
 * they can ask for dependencies:
 *
 * - rules(): array, the rules of its fields
 * - messages(): array and attributes(): array
 * - prepareForValidation(): void, to normalize input with merge()
 * - after(): array, callables that get its Validator for checks across fields
 * - payload(): the validated input as a typed object, read by the page's saved event
 *
 * @public
 */
abstract class SectionRequest
{
    use SerializesAndRestoresModelIdentifiers;

    private string $key;

    private ?Request $request = null;

    private ?Model $resource = null;

    private SectionContext $context;

    /** @var array<string, mixed>|null */
    private ?array $validated = null;

    /**
     * The resource the page edits, or null on its create page.
     */
    public function resource(): ?Model
    {
        return $this->resource;
    }

    public function context(): SectionContext
    {
        return $this->context;
    }

    /**
     * The section's own input as posted, or one of its fields: input('note').
     */
    public function input(?string $key = null, mixed $default = null): mixed
    {
        $input = $this->request?->input("sections.{$this->key}", []);
        $input = is_array($input) ? $input : [];

        return $key === null ? $input : data_get($input, $key, $default);
    }

    /**
     * Whether the section posted the field, even if empty.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->input());
    }

    /**
     * Adds to or replaces the section's own input, e.g. in prepareForValidation().
     *
     * @param  array<string, mixed>  $input
     */
    public function merge(array $input): void
    {
        $request = $this->request ?? throw new LogicException('The ['.static::class.'] section has no request to change.');

        $request->merge(['sections' => [
            ...(array) $request->input('sections', []),
            $this->key => array_replace($this->input(), $input),
        ]]);
    }

    /**
     * The section's validated input, or one of its fields. Fields without rules are left out.
     */
    public function validated(?string $key = null, mixed $default = null): mixed
    {
        $validated = $this->validated ?? throw new LogicException('The ['.static::class.'] section has not been validated yet.');

        return $key === null ? $validated : data_get($validated, $key, $default);
    }

    /**
     * The validated input as the section saves it. Override it to return a typed payload,
     * e.g. `new ProductNotePayload($this->validated('note'))`.
     */
    public function payload(): mixed
    {
        return $this->validated();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [];
    }

    protected function prepareForValidation(): void {}

    /**
     * @internal Sections::requests() sets up each section's request.
     */
    final public function setUp(string $key, Request $request, ?Model $resource, SectionContext $context): void
    {
        [$this->key, $this->request, $this->resource, $this->context] = [$key, $request, $resource, $context];
    }

    /**
     * @internal SectionRequests prepares the input before the page is validated.
     */
    final public function prepare(): void
    {
        $this->prepareForValidation();
    }

    /**
     * @internal SectionRequests keeps the validated input once the section passes.
     *
     * @param  array<string, mixed>  $validated
     */
    final public function passed(array $validated): void
    {
        $this->validated = $validated;
    }

    /**
     * A queued listener gets the section without its HTTP request: validated(), payload(),
     * resource() and context() still answer, input() is empty.
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'key' => $this->key,
            'resource' => $this->getSerializedPropertyValue($this->resource),
            'context' => $this->context,
            'validated' => $this->validated,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function __unserialize(array $data): void
    {
        $this->key = $data['key'];
        $this->resource = $this->getRestoredPropertyValue($data['resource']);
        $this->context = $data['context'];
        $this->validated = $data['validated'];
    }
}
