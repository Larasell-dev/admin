<?php

namespace Larasell\Admin\SalesChannel\Http\Requests;

use Illuminate\Validation\Rule;
use Larasell\Admin\SalesChannel\Actions\CreateSalesChannelPayload;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;
use Larasell\Larasell\SalesChannels\SalesChannelTypes;

/**
 * Adding a sales channel: what editing one takes, plus its key and type, which can't change later.
 */
class CreateSalesChannelRequest extends UpdateSalesChannelRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Lowercase words joined by - or _, e.g. "b2b-shop", since code names the channel by it.
            'key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/', Rule::unique(SalesChannel::class, 'key')],
            'type' => ['required', 'string', Rule::in(app(SalesChannelTypes::class)->all())],
            ...parent::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'key.regex' => __('Use lowercase letters and numbers, joined by - or _.'),
            'key.unique' => __('A sales channel with the key [:input] already exists.'),
        ];
    }

    public function createPayload(): CreateSalesChannelPayload
    {
        $channel = $this->payload();

        return new CreateSalesChannelPayload(
            key: $this->string('key')->value(),
            name: $channel->name,
            type: $this->string('type')->value(),
            locales: $channel->locales,
            currencies: $channel->currencies,
            defaultLocale: $channel->defaultLocale,
            defaultCurrency: $channel->defaultCurrency,
            active: $channel->active,
        );
    }
}
