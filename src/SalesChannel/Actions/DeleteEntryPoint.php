<?php

namespace Larasell\Admin\SalesChannel\Actions;

use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Storefront\Actions\DeleteEntryPoint as CoreDeleteEntryPoint;
use Larasell\Larasell\Storefront\EntryPointId;
use Larasell\Larasell\Storefront\Models\EntryPoint;

class DeleteEntryPoint
{
    public function __construct(private readonly CoreDeleteEntryPoint $delete) {}

    /**
     * Deletes the domain through the core's action. When it was primary, the oldest other one of
     * its language takes over.
     */
    public function handle(EntryPoint $entryPoint, Origin $origin): void
    {
        $this->delete->handle(EntryPointId::of($entryPoint), $origin);
    }
}
