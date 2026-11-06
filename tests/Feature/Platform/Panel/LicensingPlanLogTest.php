<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Chronicle\Facades\Chronicle;
use Larasell\Larasell\Enums\Plan;
use Larasell\Larasell\Facades\Entitlement;

it('logs the licensing plan on admin requests', function (Plan $plan) {
    // Arrange
    $chronicle = Chronicle::fake();
    Entitlement::fake($plan);

    // Act
    $this->get(route('larasell.admin.login'))->assertOk();

    // Assert
    expect($chronicle->added())->toMatchArray(['licensing.plan' => $plan->value]);
})->with([
    'free' => [Plan::Free],
    'pro' => [Plan::Pro],
]);

it('logs the licensing plan on the routes modules register', function () {
    // Arrange
    $chronicle = Chronicle::fake();
    Entitlement::fake(Plan::Free);
    $this->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');

    // Act
    $this->get(route('larasell.admin.products.index'))->assertOk();

    // Assert
    expect($chronicle->added())->toMatchArray(['licensing.plan' => 'free']);
});

it('does not log the licensing plan on the app\'s own requests', function () {
    // Arrange
    $chronicle = Chronicle::fake();
    Route::middleware('web')->get('shop', fn () => 'shop')->name('shop');

    // Act
    $this->get(route('shop'))->assertOk();

    // Assert
    expect($chronicle->added())->not->toHaveKey('licensing.plan');
});
