<?php

namespace Larasell\Admin\Platform\Sections;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator;
use Larasell\Admin\Platform\Sections\Exceptions\InvalidSection;
use Larasell\Admin\Platform\Support\Assert;

/**
 * The requests of a page's sections while the page is saved. The page's own request prepares
 * and validates them with its own fields, and its saved event hands them to listeners.
 */
final class SectionRequests
{
    /**
     * @param  array<string, SectionRequest>  $requests  By section key
     */
    public function __construct(private readonly array $requests) {}

    /**
     * Runs each section's prepareForValidation(); call it from the page request's own, with that
     * request.
     */
    public function prepare(Request $page): void
    {
        foreach ($this->requests as $key => $request) {
            $others = fn (): array => array_diff_key((array) $page->input('sections', []), [$key => true]);
            $before = $others();

            $request->prepare();

            // SectionRequest::merge() promises to change only the section's own input, which the
            // other sections' validation relies on.
            foreach (array_keys($before + $others()) as $other) {
                if (! Assert::that(($before[$other] ?? null) === ($others()[$other] ?? null), fn () => InvalidSection::changedOtherInput($key, (string) $other))) {
                    $page->merge(['sections' => [...(array) $page->input('sections', []), ...$before]]);

                    break;
                }
            }
        }
    }

    /**
     * Validates each section on its own input, with its own rules, messages, attributes and
     * after() hooks, and adds its errors to the page's validator under "sections.{key}.". Return
     * it from the page request's after().
     *
     * @return callable(Validator): void
     */
    public function validate(): callable
    {
        return function (Validator $page): void {
            foreach ($this->requests as $key => $request) {
                $validator = app(Factory::class)->make(
                    $request->input(),
                    method_exists($request, 'rules') ? app()->call([$request, 'rules']) : [],
                    $request->messages(),
                    $request->attributes(),
                );

                if (method_exists($request, 'after')) {
                    $validator->after(app()->call([$request, 'after'], ['validator' => $validator]));
                }

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $field => $messages) {
                        foreach ($messages as $message) {
                            $page->errors()->add("sections.{$key}.{$field}", $message);
                        }
                    }
                } else {
                    $request->passed($validator->validated());
                }
            }
        };
    }

    /**
     * The validated sections' payloads that are objects, to pass along with a core action as its
     * Extensions; sections without one are left out.
     *
     * @return list<object>
     */
    public function payloads(): array
    {
        return array_values(array_filter(
            array_map(fn (SectionRequest $request): mixed => $request->payload(), $this->requests),
            is_object(...),
        ));
    }

    /**
     * A section's validated request, by its class, e.g. in a listener of the page's saved event.
     *
     * @template T of SectionRequest
     *
     * @param  class-string<T>  $class
     * @return T
     */
    public function get(string $class): SectionRequest
    {
        foreach ($this->requests as $request) {
            if ($request instanceof $class) {
                return $request;
            }
        }

        throw InvalidSection::unknownRequest($class);
    }
}
