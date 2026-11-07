<?php

namespace Larasell\Admin\SalesChannel\Actions;

use Illuminate\Validation\ValidationException;
use Larasell\Larasell\SalesChannels\Actions\CreateSalesChannel as CoreCreateSalesChannel;
use Larasell\Larasell\SalesChannels\Exceptions\InvalidSalesChannel;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;

class CreateSalesChannel
{
    public function __construct(private readonly CoreCreateSalesChannel $create) {}

    /**
     * Creates the channel through the core's action, which checks the locales, currencies and
     * defaults again and dispatches SalesChannelCreated.
     *
     * @throws ValidationException
     */
    public function handle(CreateSalesChannelPayload $payload): SalesChannel
    {
        try {
            return $this->create->handle([
                'key' => $payload->key,
                'name' => $payload->name,
                'type' => $payload->type,
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
