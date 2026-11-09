<?php

use Illuminate\Support\Facades\Validator;
use Larasell\Admin\Platform\Panel\Rules\Confirmation;

it('accepts the expected text, ignoring spaces around it', function (string $typed) {
    // Act
    $validator = Validator::make(['confirmation' => $typed], ['confirmation' => [new Confirmation('Wholesale')]]);

    // Assert
    expect($validator->passes())->toBeTrue();
})->with(['exact' => 'Wholesale', 'padded' => '  Wholesale ']);

it('rejects anything else, including a missing confirmation', function (array $data) {
    // Act
    $validator = Validator::make($data, ['confirmation' => [new Confirmation('Wholesale')]]);

    // Assert
    expect($validator->errors()->get('confirmation'))->toBe(['Type "Wholesale" to confirm.']);
})->with([
    'other case' => [['confirmation' => 'wholesale']],
    'empty' => [['confirmation' => '']],
    'missing' => [[]],
    'not a string' => [['confirmation' => ['Wholesale']]],
]);
