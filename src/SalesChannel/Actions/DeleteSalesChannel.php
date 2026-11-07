<?php

namespace Larasell\Admin\SalesChannel\Actions;

use Illuminate\Validation\ValidationException;
use Larasell\Larasell\SalesChannels\Actions\DeleteSalesChannel as CoreDeleteSalesChannel;
use Larasell\Larasell\SalesChannels\InvalidSalesChannel;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;
use Larasell\Larasell\SalesChannels\SalesChannelId;
use Larasell\Larasell\SalesChannels\SalesChannelInUse;

class DeleteSalesChannel
{
    public function __construct(private readonly CoreDeleteSalesChannel $delete) {}

    /**
     * Deletes the channel through the core's action, once the admin typed its name. The core
     * refuses the last channel, and one that carts or orders were made in; the refusal comes back
     * as a validation error on the channel, in the interface's language.
     *
     * @throws ValidationException
     */
    public function handle(SalesChannel $channel, ?string $confirmation): void
    {
        if ($confirmation !== $channel->name) {
            throw ValidationException::withMessages([
                'confirmation' => __('Type ":text" to confirm.', ['text' => $channel->name]),
            ]);
        }

        try {
            $this->delete->handle(SalesChannelId::of($channel));
        } catch (InvalidSalesChannel) {
            throw ValidationException::withMessages(['channel' => __("The last sales channel can't be deleted.")]);
        } catch (SalesChannelInUse) {
            throw ValidationException::withMessages(['channel' => __('Orders or carts were made in this sales channel. Deactivate it instead.')]);
        }
    }
}
