<?php

namespace Larasell\Admin\Platform\Sections\Facades;

use Illuminate\Support\Facades\Facade;
use Larasell\Admin\Platform\Sections\Sections;

/**
 * Adds sections to forms from a service provider's boot():
 * `Section::add(SectionForm::Product, 'acme-notes')->after(ProductSection::General)->props(NotesProps::class)->request(NotesRequest::class)`.
 *
 * @method static \Larasell\Admin\Platform\Sections\SectionDefinition add(\Larasell\Admin\Platform\Sections\SectionForm $form, string $key)
 * @method static void anchors(\Larasell\Admin\Platform\Sections\SectionForm $form, class-string<\BackedEnum> $sections)
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
