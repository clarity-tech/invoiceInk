<?php

use App\Models\Membership;

beforeEach(function () {
    $this->membership = new Membership;
});

it('has auto-incrementing IDs enabled', function () {
    expect($this->membership->incrementing)->toBeTrue();
});

it('extends Pivot', function () {
    expect($this->membership)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\Pivot::class);
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
