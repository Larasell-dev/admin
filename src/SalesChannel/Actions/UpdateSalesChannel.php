<?php

namespace Larasell\Admin\SalesChannel\Actions;

use Illuminate\Validation\ValidationException;
use Larasell\Larasell\SalesChannels\Actions\UpdateSalesChannel as CoreUpdateSalesChannel;
use Larasell\Larasell\SalesChannels\InvalidSalesChannel;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;
use Larasell\Larasell\SalesChannels\SalesChannelId;

class UpdateSalesChannel
{
    public function __construct(private readonly CoreUpdateSalesChannel $update) {}

    /**
     * Saves the channel through the core's action. Its listeners may refuse the change, e.g. the
     * storefront when an entry point still serves a locale or currency the admin removed; the
     * refusal comes back as a validation error.
     *
     * @throws ValidationException
     */
    public function handle(SalesChannel $channel, UpdateSalesChannelPayload $payload): SalesChannel
    {
        try {
            return $this->update->handle(SalesChannelId::of($channel), [
                'name' => $payload->name,
                'locales' => $payload->locales,
                'currencies' => $payload->currencies,
                'default_locale' => $payload->defaultLocale,
                'default_currency' => $payload->defaultCurrency,
                'active' => $payload->active,
            ]);
        } catch (InvalidSalesChannel $exception) {
            throw ValidationException::withMessages(['channel' => $exception->getMessage()]);
        }
    }
}
