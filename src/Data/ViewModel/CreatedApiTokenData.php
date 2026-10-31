<?php

namespace Larasell\Admin\Data\ViewModel;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A token just created, flashed once to the API tokens page: its plaintext is never sent again.
 *
 * @internal
 */
#[TypeScript]
class CreatedApiTokenData extends Data
{
    public function __construct(
        public string $name,
        public string $plainTextToken,
    ) {}
}
