<?php

use App\Actions\Fortify\PasswordValidationRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rules\Password;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->testClass = new class
    {
        use PasswordValidationRules;

        public function getPasswordRules(): array
        {
            return $this->passwordRules();
        }
    };
});

it('returns array of password validation rules', function () {
    $rules = $this->testClass->getPasswordRules();

    expect($rules)->toBeArray();
    expect($rules)->toHaveCount(4);
});

it('includes required rule', function () {
    $rules = $this->testClass->getPasswordRules();

    expect($rules)->toContain('required');
});

it('includes string rule', function () {
    $rules = $this->testClass->getPasswordRules();

    expect($rules)->toContain('string');
});

it('includes confirmed rule', function () {
    $rules = $this->testClass->getPasswordRules();

    expect($rules)->toContain('confirmed');
});

it('includes Password default rule', function () {
    $rules = $this->testClass->getPasswordRules();

    $hasPasswordRule = false;
    foreach ($rules as $rule) {
        if ($rule instanceof Password) {
            $hasPasswordRule = true;
            break;
        }
    }

    expect($hasPasswordRule)->toBeTrue();
});

it('can be used by classes that implement it', function () {
    $reflection = new ReflectionClass($this->testClass);
    $traits = $reflection->getTraitNames();

    expect($traits)->toContain('App\Actions\Fortify\PasswordValidationRules');
});

it('provides protected method', function () {
    $reflection = new ReflectionClass($this->testClass);
    $method = $reflection->getMethod('passwordRules');

    expect($method->isProtected())->toBeTrue();
});

it('returns consistent rules', function () {
    $rules1 = $this->testClass->getPasswordRules();
    $rules2 = $this->testClass->getPasswordRules();

    expect($rules1)->toEqual($rules2);
});
