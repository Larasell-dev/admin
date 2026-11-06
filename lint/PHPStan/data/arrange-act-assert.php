<?php

// Tests the rule must report, and tests that follow the convention. The line numbers are
// asserted in ArrangeActAssertRuleTest.

it('has no markers', function () {
    $this->get(route('larasell.admin.login'))->assertOk();
});

it('has no assert', function () {
    // Arrange
    $admin = admin();

    // Act
    $this->actingAs($admin)->get(route('larasell.admin.home'));
});

it('marks its sections out of order', function () {
    // Act
    $response = $this->get(route('larasell.admin.home'));

    // Arrange
    $admin = admin();

    // Assert
    $response->assertOk();
});

it('marks a section twice', function () {
    // Act
    $first = $this->get(route('larasell.admin.home'));

    // Act
    $second = $this->get(route('larasell.admin.home'));

    // Assert
    $second->assertOk();
});

it('combines sections that skip one', function () {
    // Arrange & Assert
    expect(admin())->not->toBeNull();
});

it('starts with code outside any section', function () {
    $admin = admin();

    // Act
    $response = $this->actingAs($admin)->get(route('larasell.admin.home'));

    // Assert
    $response->assertOk();
});

test('spells a marker differently', function () {
    // arrange
    $admin = admin();

    // Act and Assert
    $this->actingAs($admin)->get(route('larasell.admin.home'))->assertOk();
});

it('reports an arrow function without markers', fn () => expect(true)->toBeTrue());

it('marks all three sections', function () {
    // Arrange
    $admin = admin();

    // Act
    $response = $this->actingAs($admin)->get(route('larasell.admin.home'));

    // Assert
    $response->assertOk();
});

it('leaves out arrange when it sets nothing up', function () {
    // Act
    $response = $this->get(route('larasell.admin.login'));

    // Assert
    $response->assertOk();
});

it('combines sections that cannot be split', function () {
    // Arrange
    config()->set('larasell.admin.path', '');

    // Act & Assert
    app('larasell.admin');
})->throws(InvalidArgumentException::class);

it('combines all three sections', function (int $number) {
    // Arrange & Act & Assert
    expect($number)->toBeInt();
})->with([1, 2]);

test('finds markers inside nested blocks and keeps other comments', function () {
    // Arrange
    // An ordinary comment under a marker.
    $admins = [admin(), admin()];

    // Act & Assert
    foreach ($admins as $admin) {
        $this->actingAs($admin)->get(route('larasell.admin.home'))->assertOk();
    }
});

it('marks an arrow function', fn () =>
    // Act & Assert
    expect(true)->toBeTrue());

it('is a todo without a callback')->todo();

beforeEach(function () {
    $this->admin = admin();
});

describe('a group', function () {
    it('marks a test inside a group', function () {
        // Act & Assert
        expect(true)->toBeTrue();
    });
});
