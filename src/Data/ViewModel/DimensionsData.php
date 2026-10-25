<?php

namespace Larasell\Admin\Data\ViewModel;

use Larasell\Larasell\Dimensions;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Dimensions in the unit they were saved in. The amounts stay strings, so decimals reach the form exactly.
 */
#[TypeScript]
class DimensionsData extends Data
{
    public function __construct(
        public string $length,
        public string $width,
        public string $height,
        public string $unit,
    ) {}

    public static function fromDimensions(Dimensions $dimensions): self
    {
        return new self(
            $dimensions->length()->amount(),
            $dimensions->width()->amount(),
            $dimensions->height()->amount(),
            $dimensions->unit()->value,
        );
    }
}
