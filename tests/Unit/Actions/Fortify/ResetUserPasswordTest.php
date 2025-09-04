<?php

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new ResetUserPassword;
    $this->user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);
});

it('can reset user password', function () {
    $input = [
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ];

    $this->action->reset($this->user, $input);

    $this->user->refresh();

    expect(Hash::check('new-password123', $this->user->password))->toBeTrue();
    expect(Hash::check('old-password', $this->user->password))->toBeFalse();
});

it('validates password is required', function () {
    $input = [
        'password' => '',
        'password_confirmation' => '',
    ];

    expect(fn () => $this->action->reset($this->user, $input))
        ->toThrow(ValidationException::class);
});

it('validates password with password rules', function () {
    $input = [
        'password' => '123', // Too short
        'password_confirmation' => '123',
    ];

    expect(fn () => $this->action->reset($this->user, $input))
        ->toThrow(ValidationException::class);
});

it('validates password confirmation', function () {
    $input = [
        'password' => 'new-password123',
        'password_confirmation' => 'different-password',
    ];

    expect(fn () => $this->action->reset($this->user, $input))
        ->toThrow(ValidationException::class);
});

it('hashes password before saving', function () {
    $input = [
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ];

    $this->action->reset($this->user, $input);

    $this->user->refresh();

    expect($this->user->password)->not->toBe('new-password123');
    expect(Hash::check('new-password123', $this->user->password))->toBeTrue();
});

it('uses force fill to update password', function () {
    $originalPassword = $this->user->password;

    $input = [
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ];

    $this->action->reset($this->user, $input);

    $this->user->refresh();

    expect($this->user->password)->not->toBe($originalPassword);
});

it('validates password minimum length', function () {
    $input = [
        'password' => '1234567', // 7 characters (assuming min 8)
        'password_confirmation' => '1234567',
    ];

    expect(fn () => $this->action->reset($this->user, $input))
        ->toThrow(ValidationException::class);
});

it('accepts valid password with confirmation', function () {
    $input = [
        'password' => 'ValidPassword123!',
        'password_confirmation' => 'ValidPassword123!',
    ];

    $this->action->reset($this->user, $input);

    $this->user->refresh();

    expect(Hash::check('ValidPassword123!', $this->user->password))->toBeTrue();
});

it('uses PasswordValidationRules trait', function () {
    $traits = class_uses(ResetUserPassword::class);

    expect($traits)->toHaveKey('App\Actions\Fortify\PasswordValidationRules');
});
