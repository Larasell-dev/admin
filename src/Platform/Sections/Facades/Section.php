<?php

namespace Larasell\Admin\Platform\Sections\Facades;

use Illuminate\Support\Facades\Facade;
use Larasell\Admin\Platform\Sections\Sections;

/**
 * Adds sections to resource pages from a service provider's boot():
 * `Section::add(SectionPage::Product, 'acme-notes')->props(NotesProps::class)->request(NotesRequest::class)`.
 *
 * @method static \Larasell\Admin\Platform\Sections\SectionDefinition add(\Larasell\Admin\Platform\Sections\SectionPage $page, string $key)
 *
 * @see Sections
 */
class Section extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Sections::class;
    }
}
