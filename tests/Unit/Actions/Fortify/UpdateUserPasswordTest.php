<?php

use App\Actions\Fortify\UpdateUserPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new UpdateUserPassword;
    $this->user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);
    // Set the authenticated user for current_password validation to work
    $this->actingAs($this->user);
});

it('can update user password', function () {
    $input = [
        'current_password' => 'current-password',
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ];

    $this->action->update($this->user, $input);

    $this->user->refresh();

    expect(Hash::check('new-password123', $this->user->password))->toBeTrue();
    expect(Hash::check('current-password', $this->user->password))->toBeFalse();
});

it('validates current password is required', function () {
    $input = [
        'current_password' => '',
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ];

    expect(fn () => $this->action->update($this->user, $input))
        ->toThrow(ValidationException::class);
});

it('validates current password is correct', function () {
    $input = [
        'current_password' => 'wrong-password',
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ];

    try {
        $this->action->update($this->user, $input);
        expect(false)->toBeTrue('Expected validation exception');
    } catch (ValidationException $e) {
        expect($e->errors()['current_password'])->toContain('The provided password does not match your current password.');
    }
});

it('validates new password is required', function () {
    $input = [
        'current_password' => 'current-password',
        'password' => '',
        'password_confirmation' => '',
    ];

    expect(fn () => $this->action->update($this->user, $input))
        ->toThrow(ValidationException::class);
});

it('validates new password with password rules', function () {
    $input = [
        'current_password' => 'current-password',
        'password' => '123', // Too short
        'password_confirmation' => '123',
    ];

    expect(fn () => $this->action->update($this->user, $input))
        ->toThrow(ValidationException::class);
});

it('validates new password confirmation', function () {
    $input = [
        'current_password' => 'current-password',
        'password' => 'new-password123',
        'password_confirmation' => 'different-password',
    ];

    expect(fn () => $this->action->update($this->user, $input))
        ->toThrow(ValidationException::class);
});

it('hashes new password before saving', function () {
    $input = [
        'current_password' => 'current-password',
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ];

    $this->action->update($this->user, $input);

    $this->user->refresh();

    expect($this->user->password)->not->toBe('new-password123');
    expect(Hash::check('new-password123', $this->user->password))->toBeTrue();
});

it('uses updatePassword validation bag', function () {
    $input = [
        'current_password' => 'wrong-password',
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ];

    try {
        $this->action->update($this->user, $input);
        expect(false)->toBeTrue('Expected validation exception');
    } catch (ValidationException $e) {
        expect($e->validator->getMessageBag()->getMessages())->toBeArray();
    }
});

it('uses force fill to update password', function () {
    $originalPassword = $this->user->password;

    $input = [
        'current_password' => 'current-password',
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ];

    $this->action->update($this->user, $input);

    $this->user->refresh();

    expect($this->user->password)->not->toBe($originalPassword);
});

it('accepts valid password change', function () {
    $input = [
        'current_password' => 'current-password',
        'password' => 'ValidNewPassword123!',
        'password_confirmation' => 'ValidNewPassword123!',
    ];

    $this->action->update($this->user, $input);

    $this->user->refresh();

    expect(Hash::check('ValidNewPassword123!', $this->user->password))->toBeTrue();
});

it('uses PasswordValidationRules trait', function () {
    $traits = class_uses(UpdateUserPassword::class);

    expect($traits)->toHaveKey('App\Actions\Fortify\PasswordValidationRules');
});
