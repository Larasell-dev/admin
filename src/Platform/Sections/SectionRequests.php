<?php

namespace Larasell\Admin\Platform\Sections;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

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
     * Runs each section's prepareForValidation(); call it from the page request's own.
     */
    public function prepare(): void
    {
        foreach ($this->requests as $request) {
            $request->prepare();
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

        throw new InvalidArgumentException("No section of this page is validated by [{$class}].");
    }
}
