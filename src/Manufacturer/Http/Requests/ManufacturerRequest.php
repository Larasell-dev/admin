<?php

namespace Larasell\Admin\Manufacturer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Larasell\Catalog\Manufacturers\Actions\CreateManufacturerPayload;
use Larasell\Larasell\Catalog\Manufacturers\Actions\UpdateManufacturerPayload;
use Larasell\Larasell\Catalog\Manufacturers\PostalAddress;
use Larasell\Larasell\Catalog\Manufacturers\ResponsiblePerson;
use Larasell\Larasell\Enums\Country;

/**
 * Creates or changes a manufacturer. The EU responsible person is optional and saved only with a
 * name; with one, its address is required.
 */
class ManufacturerRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return self::newRules();
    }

    /**
     * A manufacturer's fields, under $prefix, e.g. new_manufacturer. for one added on the product form.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function newRules(string $prefix = ''): array
    {
        $person = "required_with:{$prefix}responsible_person.name";

        return [
            "{$prefix}name" => ['required', 'string', 'max:255'],
            "{$prefix}email" => ['nullable', 'email', 'max:255'],
            "{$prefix}url" => ['nullable', 'url', 'max:255'],
            ...self::addressRules("{$prefix}address", 'required'),
            "{$prefix}responsible_person" => ['nullable', 'array'],
            "{$prefix}responsible_person.name" => ['nullable', 'string', 'max:255'],
            "{$prefix}responsible_person.email" => ['nullable', 'email', 'max:255'],
            ...self::addressRules("{$prefix}responsible_person.address", $person),
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private static function addressRules(string $prefix, string $required): array
    {
        return [
            "{$prefix}.street" => [$required, 'nullable', 'string', 'max:1000'],
            "{$prefix}.postcode" => [$required, 'nullable', 'string', 'max:32'],
            "{$prefix}.city" => [$required, 'nullable', 'string', 'max:255'],
            "{$prefix}.state" => ['nullable', 'string', 'max:255'],
            "{$prefix}.country" => [$required, 'nullable', Rule::enum(Country::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return self::newAttributes();
    }

    /**
     * How errors name the fields, under the same $prefix as newRules().
     *
     * @return array<string, string>
     */
    public static function newAttributes(string $prefix = ''): array
    {
        return array_combine(array_map(fn (string $key): string => $prefix.$key, array_keys($names = [
            'name' => __('name'),
            'email' => __('email'),
            'url' => __('website'),
            'address.street' => __('street'),
            'address.postcode' => __('postcode'),
            'address.city' => __('city'),
            'address.country' => __('country'),
            'responsible_person.name' => __('name'),
            'responsible_person.email' => __('email'),
            'responsible_person.address.street' => __('street'),
            'responsible_person.address.postcode' => __('postcode'),
            'responsible_person.address.city' => __('city'),
            'responsible_person.address.country' => __('country'),
        ])), $names);
    }

    public function createPayload(): CreateManufacturerPayload
    {
        return self::newPayload($this->validated());
    }

    public function updatePayload(): UpdateManufacturerPayload
    {
        $create = $this->createPayload();

        return new UpdateManufacturerPayload($create->name, $create->address, $create->email, $create->url, $create->responsiblePerson);
    }

    /** @param  array<array-key, mixed>  $fields  Validated with newRules(). */
    public static function newPayload(array $fields): CreateManufacturerPayload
    {
        $text = fn (array $from, string $key): ?string => is_string($from[$key] ?? null) && $from[$key] !== '' ? $from[$key] : null;
        $person = is_array($fields['responsible_person'] ?? null) ? $fields['responsible_person'] : [];

        return new CreateManufacturerPayload(
            name: (string) $fields['name'],
            address: self::address((array) $fields['address']),
            email: $text($fields, 'email'),
            url: $text($fields, 'url'),
            // Saved only with a name; the form sends the person's empty fields otherwise.
            responsiblePerson: $text($person, 'name') === null ? null : new ResponsiblePerson((string) $person['name'], self::address((array) $person['address']), $text($person, 'email')),
        );
    }

    /** @param  array<array-key, mixed>  $address */
    private static function address(array $address): PostalAddress
    {
        $state = $address['state'] ?? null;

        return new PostalAddress(
            street: preg_split('/\R/', (string) $address['street']) ?: [],
            postcode: (string) $address['postcode'],
            city: (string) $address['city'],
            country: Country::from((string) $address['country']),
            state: is_string($state) && $state !== '' ? $state : null,
        );
    }
}
