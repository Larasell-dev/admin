<?php

namespace Larasell\Admin\SalesChannel\Actions;

use Illuminate\Validation\ValidationException;
use Larasell\Admin\SalesChannel\Data\EntryPointData;
use Larasell\Larasell\Storefront\Actions\DeleteEntryPoint as CoreDeleteEntryPoint;
use Larasell\Larasell\Storefront\EntryPointId;
use Larasell\Larasell\Storefront\Models\EntryPoint;

class DeleteEntryPoint
{
    public function __construct(private readonly CoreDeleteEntryPoint $delete) {}

    /**
     * Deletes the domain through the core's action, once the admin typed it without its scheme,
     * e.g. shop.example/de. When it was primary, the oldest other one of its language takes over.
     *
     * @throws ValidationException
     */
    public function handle(EntryPoint $entryPoint, ?string $confirmation): void
    {
        $domain = EntryPointData::domain($entryPoint);

        if (trim((string) $confirmation) !== $domain) {
            throw ValidationException::withMessages([
                'confirmation' => __('Type ":text" to confirm.', ['text' => $domain]),
            ]);
        }

        $this->delete->handle(EntryPointId::of($entryPoint));
    }
}
