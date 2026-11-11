<?php

declare(strict_types=1);
use Larasell\Admin\Tests\TestCase;
use Larasell\Larasell\Audit\Actors\SystemActor;
use Larasell\Larasell\Audit\Origin;

uses(TestCase::class)->in('Feature', 'Unit');

/*
| Who the tests make their audited changes as, when the test isn't about who made them.
*/
function origin(): Origin
{
    return Origin::cli(new SystemActor('tests'));
}
