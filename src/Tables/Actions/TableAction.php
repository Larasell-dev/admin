<?php

namespace Larasell\Admin\Tables\Actions;

use Illuminate\Support\Str;
use JsonSerializable;
use Larasell\Admin\Tables\Actions\Concerns\ConfiguresAction;
use LogicException;

/**
 * An action for the whole table rather than one row, such as a link to add a
 * record. Tables show these in their header, or in their empty state.
 *
 * It only links for now. Its JSON names the request method, so actions that
 * run a handler can be added later without changing what links send.
 */
final class TableAction implements JsonSerializable
{
    use ConfiguresAction;

    private ?string $url = null;

    private function __construct(private string $label) {}

    public static function make(string $label): self
    {
        return new self($label);
    }

    public function to(string $url): self
    {
        $this->configureOnce('to() or route()');
        $this->url = $url;

        return $this;
    }

    /**
     * @param  array<array-key, mixed>  $parameters
     */
    public function route(string $name, array $parameters = []): self
    {
        return $this->to(route($name, $parameters));
    }

    public function name(): string
    {
        return Str::slug($this->label);
    }

    /**
     * The icon is sent as its case name, which @larasell-dev/ui/icons exports with a Fill or Line suffix.
     *
     * @return array{label: string, method: 'get', url: string, icon: ?string}
     */
    public function jsonSerialize(): array
    {
        return [
            'label' => $this->label,
            'method' => 'get',
            'url' => $this->url ?? throw new LogicException("Action [{$this->name()}] needs a link: call to() or route()."),
            'icon' => $this->icon?->name,
        ];
    }
}
