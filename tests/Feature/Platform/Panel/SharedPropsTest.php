<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Larasell\Admin\AdminPanel;

it('adds the props modules and extensions share to every admin page, resolving them only when a page renders', function () {
    // Arrange
    $resolved = 0;
    $this->bootingWith(function () use (&$resolved): void {
        AdminPanel::resolve()->share('acmeBanner', function () use (&$resolved): string {
            $resolved++;

            return 'Spring sale';
        });
    });

    // Act & Assert
    expect($resolved)->toBe(0);

    $this->get(route('larasell.admin.login'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('acmeBanner', 'Spring sale'));

    expect($resolved)->toBe(1);
});
