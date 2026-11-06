<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures\Sections;

use Illuminate\Http\Request;
use Larasell\Admin\Platform\Sections\SectionRequest;

/** Breaks the scope merge() keeps, by reaching the HTTP request around it. */
class MeddlingRequest extends SectionRequest
{
    protected function prepareForValidation(): void
    {
        /** @var Request $request */
        $request = \Closure::bind(fn () => $this->request, $this, SectionRequest::class)();

        $request->merge(['sections' => [...(array) $request->input('sections', []), 'notes' => ['note' => 'meddled']]]);
    }
}
