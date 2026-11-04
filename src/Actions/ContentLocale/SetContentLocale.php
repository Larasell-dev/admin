<?php

namespace Larasell\Admin\Actions\ContentLocale;

use Illuminate\Http\Request;
use Larasell\Admin\ContentLocale\ContentLocaleResolver;
use Larasell\Admin\Payloads\ContentLocale\SetContentLocalePayload;

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
        $this->resolver->remember($this->request, $payload->locale);
    }
}
