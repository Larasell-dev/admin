<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures\Sections;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Larasell\Admin\Platform\Sections\Props;
use Larasell\Admin\Platform\Sections\SectionContext;
use Larasell\Larasell\Catalog\Models\Product;

/** Asks the container for a dependency, as Props classes may. */
class NotesProps implements Props
{
    public function __construct(private readonly Repository $config) {}

    /**
     * @return array{name: ?string, app: mixed}
     */
    public function resolve(?Model $resource, SectionContext $context): array
    {
        return ['name' => $resource instanceof Product ? $resource->name->get($context->locale->value) : null, 'app' => $this->config->get('app.name')];
    }
}
