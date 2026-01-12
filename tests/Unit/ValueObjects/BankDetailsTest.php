<?php

use App\ValueObjects\BankDetails;

test('BankDetails can be created with all properties', function () {
    $bank = new BankDetails(
        accountName: 'Clarity Technologies',
        accountNumber: '123456 0000 7890',
        bankName: 'Bank of Baroda',
        ifsc: 'BARB0MGROAD',
        branch: 'MG ROAD',
        swift: 'BARBINBBXXX',
        pan: 'ABCPE1234F',
    );

    expect($bank->accountName)->toBe('Clarity Technologies');
    expect($bank->accountNumber)->toBe('123456 0000 7890');
    expect($bank->bankName)->toBe('Bank of Baroda');
    expect($bank->ifsc)->toBe('BARB0MGROAD');
    expect($bank->branch)->toBe('MG ROAD');
    expect($bank->swift)->toBe('BARBINBBXXX');
    expect($bank->pan)->toBe('ABCPE1234F');
});

test('BankDetails defaults to empty strings', function () {
    $bank = new BankDetails;

    expect($bank->accountName)->toBe('');
    expect($bank->accountNumber)->toBe('');
    expect($bank->bankName)->toBe('');
    expect($bank->ifsc)->toBe('');
    expect($bank->branch)->toBe('');
    expect($bank->swift)->toBe('');
    expect($bank->pan)->toBe('');
});

test('BankDetails::fromArray creates instance from associative array', function () {
    $bank = BankDetails::fromArray([
        'account_name' => 'Test Co',
        'bank_name' => 'Test Bank',
        'ifsc' => 'TEST0001',
    ]);

    expect($bank->accountName)->toBe('Test Co');
    expect($bank->bankName)->toBe('Test Bank');
    expect($bank->ifsc)->toBe('TEST0001');
    expect($bank->accountNumber)->toBe('');
});

test('BankDetails::fromArray trims whitespace', function () {
    $bank = BankDetails::fromArray([
        'bank_name' => '  Bank of Baroda  ',
        'ifsc' => ' BARB0MGROAD ',
    ]);

    expect($bank->bankName)->toBe('Bank of Baroda');
    expect($bank->ifsc)->toBe('BARB0MGROAD');
});

test('BankDetails::fromArray casts non-string values to string', function () {
    $bank = BankDetails::fromArray([
        'account_number' => 1234567890,
        'bank_name' => 'Test Bank',
    ]);

    expect($bank->accountNumber)->toBe('1234567890');
});

test('BankDetails::fromArray handles missing keys gracefully', function () {
    $bank = BankDetails::fromArray([]);

    expect($bank->accountName)->toBe('');
    expect($bank->bankName)->toBe('');
    expect($bank->isEmpty())->toBeTrue();
});

test('BankDetails::empty returns empty instance', function () {
    $bank = BankDetails::empty();

    expect($bank->isEmpty())->toBeTrue();
    expect($bank->isConfigured())->toBeFalse();
});

test('isConfigured returns true when bank_name is present', function () {
    $bank = new BankDetails(bankName: 'Test Bank');

    expect($bank->isConfigured())->toBeTrue();
    expect($bank->isEmpty())->toBeFalse();
});

test('isConfigured returns false when bank_name is empty', function () {
    $bank = new BankDetails(accountName: 'Test Co', accountNumber: '123');

    expect($bank->isConfigured())->toBeFalse();
    expect($bank->isEmpty())->toBeTrue();
});

test('toArray filters out empty values', function () {
    $bank = new BankDetails(
        bankName: 'Test Bank',
        ifsc: 'TEST0001',
    );

    $array = $bank->toArray();

    expect($array)->toBe([
        'bank_name' => 'Test Bank',
        'ifsc' => 'TEST0001',
    ]);
    expect($array)->not->toHaveKey('account_name');
    expect($array)->not->toHaveKey('swift');
});

test('toArray includes all non-empty values', function () {
    $bank = new BankDetails(
        accountName: 'Test Co',
        accountNumber: '123',
        bankName: 'Test Bank',
        ifsc: 'TEST0001',
        branch: 'Main',
        swift: 'TESTSWIFT',
        pan: 'ABCDE1234F',
    );

    expect($bank->toArray())->toHaveCount(7);
});

test('jsonSerialize returns same as toArray', function () {
    $bank = new BankDetails(bankName: 'Test Bank', ifsc: 'TEST0001');

    expect($bank->jsonSerialize())->toBe($bank->toArray());
});

test('BankDetails is readonly', function () {
    $bank = new BankDetails(bankName: 'Test Bank');

    $reflection = new ReflectionClass($bank);
    expect($reflection->isReadOnly())->toBeTrue();
});
