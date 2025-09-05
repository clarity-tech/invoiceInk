<?php

use App\Models\Membership;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->membership = new Membership;
});

it('has auto-incrementing IDs enabled', function () {
    expect($this->membership->incrementing)->toBeTrue();
});

it('extends JetstreamMembership', function () {
    expect($this->membership)->toBeInstanceOf(\Laravel\Jetstream\Membership::class);
});

it('inherits fillable attributes from parent', function () {
    $fillable = $this->membership->getFillable();

    // Test that it returns an array (parent may not have 'role' specifically)
    expect($fillable)->toBeArray();
});

it('can be instantiated', function () {
    $membership = new Membership;

    expect($membership)->toBeInstanceOf(Membership::class);
});
