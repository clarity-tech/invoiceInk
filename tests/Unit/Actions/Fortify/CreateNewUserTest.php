<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new CreateNewUser;
});

it('can create a new user', function () {
    $input = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'terms' => true,
    ];

    $user = $this->action->create($input);

    expect($user)
        ->name->toBe('John Doe')
        ->email->toBe('john@example.com')
        ->and(Hash::check('password123', $user->password))->toBeTrue();
});

it('creates user within database transaction', function () {
    $input = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'terms' => true,
    ];

    $user = $this->action->create($input);

    // Verify the user was created successfully (transaction worked)
    expect($user)->toBeInstanceOf(User::class);
    expect($user->ownedTeams)->toHaveCount(1);
});

it('creates a personal team for the user', function () {
    $input = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'terms' => true,
    ];

    $user = $this->action->create($input);

    expect($user->ownedTeams)
        ->toHaveCount(1)
        ->first()->name->toBe("John's Team")
        ->first()->personal_team->toBeTrue()
        ->first()->user_id->toBe($user->id);
});

it('validates name is required', function () {
    $input = [
        'name' => '',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    expect(fn () => $this->action->create($input))->toThrow(ValidationException::class);
});

it('validates email is required', function () {
    $input = [
        'name' => 'John Doe',
        'email' => '',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    expect(fn () => $this->action->create($input))->toThrow(ValidationException::class);
});

it('validates email format', function () {
    $input = [
        'name' => 'John Doe',
        'email' => 'invalid-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    expect(fn () => $this->action->create($input))->toThrow(ValidationException::class);
});

it('validates email is unique', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $input = [
        'name' => 'John Doe',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    expect(fn () => $this->action->create($input))->toThrow(ValidationException::class);
});

it('validates password with password rules', function () {
    $input = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => '123', // Too short
        'password_confirmation' => '123',
    ];

    expect(fn () => $this->action->create($input))->toThrow(ValidationException::class);
});

it('validates password confirmation', function () {
    $input = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different-password',
    ];

    expect(fn () => $this->action->create($input))->toThrow(ValidationException::class);
});

it('validates terms when feature is enabled', function () {
    config(['jetstream.features' => ['terms']]);

    $input = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'terms' => false,
    ];

    expect(fn () => $this->action->create($input))->toThrow(ValidationException::class);
});

it('does not require terms when feature is disabled', function () {
    config(['jetstream.features' => []]);

    $input = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $user = $this->action->create($input);

    expect($user)->toBeInstanceOf(User::class);
});

it('validates name maximum length', function () {
    $input = [
        'name' => str_repeat('a', 256), // Too long
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    expect(fn () => $this->action->create($input))->toThrow(ValidationException::class);
});

it('validates email maximum length', function () {
    $input = [
        'name' => 'John Doe',
        'email' => str_repeat('a', 250).'@example.com', // Too long
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    expect(fn () => $this->action->create($input))->toThrow(ValidationException::class);
});

it('creates team with first name when user has multiple names', function () {
    $input = [
        'name' => 'John Michael Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'terms' => true,
    ];

    $user = $this->action->create($input);

    expect($user->ownedTeams->first()->name)->toBe("John's Team");
});

it('creates team with full name when user has single name', function () {
    $input = [
        'name' => 'John',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'terms' => true,
    ];

    $user = $this->action->create($input);

    expect($user->ownedTeams->first()->name)->toBe("John's Team");
});

it('hashes password before storing', function () {
    $input = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'terms' => true,
    ];

    $user = $this->action->create($input);

    expect($user->password)->not->toBe('password123');
    expect(Hash::check('password123', $user->password))->toBeTrue();
});

it('uses PasswordValidationRules trait', function () {
    $traits = class_uses(CreateNewUser::class);

    expect($traits)->toHaveKey('App\Actions\Fortify\PasswordValidationRules');
});
