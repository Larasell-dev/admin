<?php

namespace Larasell\Admin\Platform\Tables\Attributes;

use Attribute;
use Illuminate\Database\Eloquent\Model;

#[Attribute(Attribute::TARGET_CLASS)]
class UseModel
{
    /**
     * @param  class-string<Model>  $class
     */
    public function __construct(public string $class)
    {
        //
    }
}
