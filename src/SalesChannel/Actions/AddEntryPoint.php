<?php

namespace Larasell\Admin\SalesChannel\Actions;

use Illuminate\Validation\ValidationException;
use Larasell\Larasell\SalesChannels\InvalidSalesChannel;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;
use Larasell\Larasell\SalesChannels\SalesChannelId;
use Larasell\Larasell\Storefront\Actions\AddEntryPoint as CoreAddEntryPoint;
use Larasell\Larasell\Storefront\Models\EntryPoint;

class AddEntryPoint
{
    public function __construct(private readonly CoreAddEntryPoint $add) {}

    /**
     * Adds the domain through the core's action, which refuses a URL another domain already uses;
     * the refusal comes back as a validation error on the URL.
     *
     * @throws ValidationException
     */
    public function handle(SalesChannel $channel, AddEntryPointPayload $payload): EntryPoint
    {
        try {
            return $this->add->handle(SalesChannelId::of($channel), [
                'url' => $payload->url,
                'locale' => $payload->locale,
                'default_currency' => $payload->defaultCurrency,
                'primary' => $payload->primary,
            ]);
        } catch (InvalidSalesChannel $exception) {
            throw ValidationException::withMessages(['url' => $exception->getMessage()]);
        }
    }
}
