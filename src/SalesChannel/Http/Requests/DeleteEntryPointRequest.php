<?php

namespace Larasell\Admin\SalesChannel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Larasell\Admin\Platform\Panel\Rules\Confirmation;
use Larasell\Admin\SalesChannel\Data\EntryPointData;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;
use Larasell\Larasell\Storefront\Models\EntryPoint;

/** Deleting a domain of a storefront channel, once the admin typed it without its scheme, e.g. shop.example/de. */
class DeleteEntryPointRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ['confirmation' => [new Confirmation(EntryPointData::domain($this->entryPoint()))]];
    }

    public function entryPoint(): EntryPoint
    {
        $channel = $this->route('channel');
        assert($channel instanceof SalesChannel);

        return EntryPoint::query()->where('sales_channel_id', $channel->id)->findOrFail((int) $this->route('entryPoint'));
    }
}
