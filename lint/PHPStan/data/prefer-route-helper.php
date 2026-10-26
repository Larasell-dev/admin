<?php

// Every way our Pest tests hard-code admin URLs, and the look-alikes the rule must leave alone.
// The line numbers are asserted in PreferRouteHelperRuleTest.

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\TestCase;

it('requests a hard-coded URL', function () {
    $category = (object) ['id' => 1];

    // Request methods with a literal path (AuthTest, ProfileTest, …)
    $this->get('/admin/login');
    $this->post('/admin/products', ['name' => 'Boots']);
    $this->put('/admin/profile', []);
    $this->delete('/admin/categories/1');
    $this->getJson('/admin/categories/1/available-products');
    $this->postJson('/admin/profile/avatar', []);

    // Interpolated paths (CategoriesTest, ProductsTest)
    $this->get("/admin/products/{$category->id}/edit");

    // Query strings (CategoriesTest, PageTitlesTest)
    $this->get("/admin/categories/create?parent={$category->id}");

    // Concatenated paths (ProfileTest)
    $this->get('/admin/profile?id='.$category->id);

    // Chained after actingAs() and from() (ProfileTest, CategoriesTest)
    $this->actingAs($admin)->from('/admin/profile')->put('/admin/profile', []);

    // Redirect assertions (AuthTest, CategoriesTest)
    $this->get('/admin')->assertRedirect('/admin/login');
    $this->post('/admin/categories')->assertRedirect("/admin/categories/{$category->id}");

    // Expected URLs in Inertia props (CategoriesTest, ProductsTest)
    $page->where('createUrl', url('/admin/categories/create'));
    $page->where('updateUrl', url("/admin/categories/{$category->id}"));

    // A path stored in a variable first
    $path = '/admin/customers';
    $this->get($path);
});

it('does not report URLs built from routes or unrelated calls', function (string $url) {
    $this->get(route('larasell.admin.login'));
    $this->get(route('larasell.admin.products.edit', 1))->assertRedirect(route('larasell.admin.login'));
    $this->get($url);
    $this->post($response->inertiaProps('table.rows.0._actions.0.url'));

    // Defining routes and actions takes paths
    Route::get('/admin/home', fn () => '')->name('larasell.admin.home');
    $action->to('/admin/books/create');

    // Other objects with methods of the same name
    (new Collection)->get('/admin');

    // Strings that aren't paths
    $this->get('admin/login');
    url();
});

function classBasedTest(TestCase $test, TestResponse $response): void
{
    $test->get('/admin/login');
    $response->assertRedirect('/admin');
}
