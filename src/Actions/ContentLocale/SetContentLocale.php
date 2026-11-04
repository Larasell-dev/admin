<?php

namespace Larasell\Admin\Actions\ContentLocale;

use Illuminate\Http\Request;
use Larasell\Admin\ContentLocale\ContentLocaleResolver;
use Larasell\Admin\Payloads\ContentLocale\SetContentLocalePayload;
use Larasell\Chronicle\Facades\Chronicle;

class SetContentLocale
{
    public function __construct(
        private readonly ContentLocaleResolver $resolver,
        private readonly Request $request,
    ) {}

    /**
     * Edits content in the payload's locale from the admin's next request on.
     */
    public function handle(SetContentLocalePayload $payload): void
    {
        Chronicle::add('content_locale', [
            'previous' => $this->resolver->resolve($this->request)->value,
            'selected' => $payload->locale->value,
        ]);

        $this->resolver->remember($this->request, $payload->locale);
    }
}
