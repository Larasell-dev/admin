<?php

namespace Larasell\Admin\SalesChannel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Larasell\Admin\Platform\Panel\Rules\Confirmation;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;

/** Deleting a sales channel, once the admin typed its name. */
class DeleteSalesChannelRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $channel = $this->route('channel');
        assert($channel instanceof SalesChannel);

        return ['confirmation' => [new Confirmation($channel->name)]];
    }
}
