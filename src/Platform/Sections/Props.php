<?php

namespace Larasell\Admin\Platform\Sections;

use Illuminate\Database\Eloquent\Model;

/**
 * What a section's component gets as `props` when its page is shown, e.g. what the section
 * saved before. Pass the class to SectionDefinition::props(); the container makes it, so its
 * constructor can ask for dependencies.
 *
 * @public
 */
interface Props
{
    /**
     * @param  Model|null  $resource  The resource the page edits, or null on its create page
     */
    public function resolve(?Model $resource, SectionContext $context): mixed;
}
