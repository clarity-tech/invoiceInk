<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create user with required fields', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->password)->not->toBeNull();
});

test('user email must be unique', function () {
    User::create([
        'name' => 'First User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->expectException(\Illuminate\Database\QueryException::class);
    
    User::create([
        'name' => 'Second User',
        'email' => 'test@example.com', // Duplicate email
        'password' => bcrypt('password'),
    ]);
});

test('user has email verified at timestamp', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    expect($user->email_verified_at)->not->toBeNull();
    expect($user->email_verified_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('user can have unverified email', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    expect($user->email_verified_at)->toBeNull();
});

test('user fillable attributes work correctly', function () {
    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => bcrypt('secret'),
    ];

    $user = User::create($userData);

    expect($user->name)->toBe('John Doe');
    expect($user->email)->toBe('john@example.com');
});

test('user password is hidden from array output', function () {
    $user = User::factory()->create();
    $userArray = $user->toArray();

    expect($userArray)->not->toHaveKey('password');
});

test('user remember token is hidden from array output', function () {
    $user = User::factory()->create([
        'remember_token' => 'test_token',
    ]);
    $userArray = $user->toArray();

    expect($userArray)->not->toHaveKey('remember_token');
});

test('user timestamps are cast correctly', function () {
    $user = User::factory()->create();

    expect($user->created_at)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($user->updated_at)->toBeInstanceOf(\Carbon\Carbon::class);
});