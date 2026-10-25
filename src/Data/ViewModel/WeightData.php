<?php

namespace Larasell\Admin\Data\ViewModel;

use Larasell\Larasell\Weight;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A weight in the unit it was saved in. The amount stays a string, so decimals reach the form exactly.
 */
#[TypeScript]
class WeightData extends Data
{
    public function __construct(
        public string $amount,
        public string $unit,
    ) {}

    public static function fromWeight(Weight $weight): self
    {
        return new self($weight->amount(), $weight->unit()->value);
    }
}
