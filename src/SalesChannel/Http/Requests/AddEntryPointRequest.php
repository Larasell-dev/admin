<?php

namespace Larasell\Admin\SalesChannel\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Admin\SalesChannel\Actions\AddEntryPointPayload;
use Larasell\Larasell\Enums\Currency;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;
use Larasell\Larasell\Storefront\Models\EntryPoint;

/**
 * Adding a domain to a storefront channel: its URL, one of the channel's languages, and optionally
 * one of its currencies that visits start in.
 */
class AddEntryPointRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $channel = $this->route('channel');
        assert($channel instanceof SalesChannel);

        return [
            'url' => ['required', 'string', 'max:255', 'url:http,https', $this->unused(...)],
            'locale' => ['required', 'string', Rule::in(array_map(fn (Locale $locale): string => $locale->value, $channel->locales()))],
            'default_currency' => ['nullable', 'string', Rule::in(array_map(fn (Currency $currency): string => $currency->value, $channel->currencies()))],
            'primary' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.url' => __('Enter a URL that starts with https:// or http://.'),
            'locale.in' => __('Pick one of the channel\'s languages.'),
            'default_currency.in' => __('Pick one of the channel\'s currencies.'),
        ];
    }

    public function payload(): AddEntryPointPayload
    {
        return new AddEntryPointPayload(
            url: $this->string('url')->trim()->value(),
            locale: $this->string('locale')->value(),
            defaultCurrency: $this->filled('default_currency') ? $this->string('default_currency')->value() : null,
            primary: $this->boolean('primary'),
        );
    }

    /** A host and prefix reach one domain only, across all channels; the core checks it too, in English. */
    private function unused(string $attribute, mixed $value, Closure $fail): void
    {
        $host = parse_url((string) $value, PHP_URL_HOST);

        if (is_string($host) && EntryPoint::query()
            ->where('host', strtolower($host))
            ->where('prefix', trim((string) parse_url((string) $value, PHP_URL_PATH), '/'))
            ->exists()) {
            $fail(__('A sales channel already uses this URL.'));
        }
    }
}
