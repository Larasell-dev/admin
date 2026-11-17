<?php

namespace Larasell\Admin\Manufacturer\Data;

use Larasell\Larasell\Catalog\Manufacturers\Models\Manufacturer;
use Larasell\Larasell\Catalog\Manufacturers\PostalAddress;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A manufacturer to pick, e.g. on the product form. */
#[TypeScript]
class ManufacturerOptionData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        /**
         * Its fields as its form names them, e.g. address[street], to change it on the product form.
         *
         * @var array<string, string>
         */
        public array $fields,
        /** How many products it's on, which a change on the product form changes too. */
        public int $products,
    ) {}

    /** @return array<int, self> Every manufacturer, by name. */
    public static function options(): array
    {
        return Manufacturer::query()->withCount('products')->orderBy('name')->get()
            ->map(fn (Manufacturer $manufacturer): self => new self($manufacturer->id, $manufacturer->name, self::fields($manufacturer), (int) $manufacturer->products_count))
            ->all();
    }

    /** @return array<string, string> */
    private static function fields(Manufacturer $manufacturer): array
    {
        $address = function (string $prefix, ?PostalAddress $address): array {
            $data = $address === null ? null : PostalAddressData::fromAddress($address);

            return [
                "{$prefix}[street]" => $data->street ?? '',
                "{$prefix}[postcode]" => $data->postcode ?? '',
                "{$prefix}[city]" => $data->city ?? '',
                "{$prefix}[state]" => $data->state ?? '',
                "{$prefix}[country]" => $data->country ?? '',
            ];
        };
        $person = $manufacturer->responsible_person;

        return [
            'name' => $manufacturer->name,
            'email' => $manufacturer->email ?? '',
            'url' => $manufacturer->url ?? '',
            ...$address('address', $manufacturer->address),
            'responsible_person[name]' => $person->name ?? '',
            'responsible_person[email]' => $person->email ?? '',
            ...$address('responsible_person[address]', $person?->address),
        ];
    }
}
