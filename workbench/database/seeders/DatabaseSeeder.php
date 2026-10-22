<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Models\Customer;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Price;
use Larasell\Larasell\Translatable;

/**
 * Sample store data for the playground, so its pages have something to show:
 * `vendor/bin/testbench db:seed --class='Workbench\Database\Seeders\DatabaseSeeder'`.
 */
class DatabaseSeeder extends Seeder
{
    /** Root categories with their subcategories, each with products and their prices in cents. */
    private const CATALOG = [
        'Clothing' => [
            'Shirts' => ['Oxford Shirt' => 4900, 'Linen Shirt' => 5900],
            'Trousers' => ['Chinos' => 6900, 'Cargo Trousers' => 7900],
            'Jackets' => ['Rain Jacket' => 12900],
        ],
        'Shoes' => [
            'Sneakers' => ['Canvas Sneakers' => 5900, 'Leather Sneakers' => 9900],
            'Boots' => ['Chelsea Boots' => 14900],
        ],
        'Accessories' => [
            'Bags' => ['Tote Bag' => 2900, 'Backpack' => 8900],
            'Hats' => ['Beanie' => 1900],
            'Belts' => [],
        ],
        'Home' => [
            'Kitchen' => ['Enamel Mug' => 1500],
            'Living room' => [],
        ],
    ];

    private const CUSTOMERS = [
        ['Ada', 'Lovelace'],
        ['Alan', 'Turing'],
        ['Grace', 'Hopper'],
        ['Margaret', 'Hamilton'],
    ];

    public function run(): void
    {
        // Setup runs this on every orb start; seeding once keeps the playground's own edits.
        if (Category::query()->exists()) {
            return;
        }

        foreach (self::CATALOG as $rootName => $children) {
            $root = $this->category($rootName);

            foreach ($children as $childName => $products) {
                $child = $this->category($childName, $root);

                foreach ($products as $productName => $price) {
                    Product::create([
                        'slug' => Translatable::fromString(str($productName)->slug()->value()),
                        'name' => Translatable::fromString($productName),
                        'price' => Price::of($price),
                        'status' => Visibility::Visible,
                    ])->categories()->attach($child);
                }
            }
        }

        foreach (self::CUSTOMERS as [$firstName, $lastName]) {
            Customer::create(['first_name' => $firstName, 'last_name' => $lastName]);
        }
    }

    private function category(string $name, ?Category $parent = null): Category
    {
        return Category::create([
            'parent_id' => $parent?->id,
            'slug' => Translatable::fromString(str($name)->slug()->value()),
            'name' => Translatable::fromString($name),
            'status' => Visibility::Visible,
        ]);
    }
}
