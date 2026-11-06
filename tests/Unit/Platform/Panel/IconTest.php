<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Larasell\Admin\Platform\Panel\Icon;

// Icon names are rejected for a -line or -fill suffix before they are looked up,
// so a case with such a value could never be passed by name.
it('has no case whose value ends in a style suffix', function () {
    // Act
    $styled = array_filter(
        array_map(fn (Icon $icon): string => $icon->value, Icon::cases()),
        fn (string $value): bool => Str::endsWith($value, ['-line', '-fill']),
    );

    // Assert
    expect($styled)->toBe([]);
});
