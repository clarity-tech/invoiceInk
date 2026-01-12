<?php

use App\Casts\BankDetailsCast;
use App\ValueObjects\BankDetails;
use Illuminate\Database\Eloquent\Model;

beforeEach(function () {
    $this->cast = new BankDetailsCast;
    $this->model = Mockery::mock(Model::class);
});

test('get returns BankDetails from JSON string', function () {
    $json = json_encode(['bank_name' => 'Test Bank', 'ifsc' => 'TEST0001']);

    $result = $this->cast->get($this->model, 'bank_details', $json, []);

    expect($result)->toBeInstanceOf(BankDetails::class);
    expect($result->bankName)->toBe('Test Bank');
    expect($result->ifsc)->toBe('TEST0001');
});

test('get returns empty BankDetails from null', function () {
    $result = $this->cast->get($this->model, 'bank_details', null, []);

    expect($result)->toBeInstanceOf(BankDetails::class);
    expect($result->isEmpty())->toBeTrue();
});

test('get returns empty BankDetails from invalid JSON', function () {
    $result = $this->cast->get($this->model, 'bank_details', 'not-json', []);

    expect($result)->toBeInstanceOf(BankDetails::class);
    expect($result->isEmpty())->toBeTrue();
});

test('get returns BankDetails from array', function () {
    $data = ['bank_name' => 'Test Bank', 'account_number' => '123'];

    $result = $this->cast->get($this->model, 'bank_details', $data, []);

    expect($result)->toBeInstanceOf(BankDetails::class);
    expect($result->bankName)->toBe('Test Bank');
    expect($result->accountNumber)->toBe('123');
});

test('set returns JSON string from BankDetails object', function () {
    $bankDetails = new BankDetails(bankName: 'Test Bank', ifsc: 'TEST0001');

    $result = $this->cast->set($this->model, 'bank_details', $bankDetails, []);

    expect($result)->toBeString();
    $decoded = json_decode($result, true);
    expect($decoded['bank_name'])->toBe('Test Bank');
    expect($decoded['ifsc'])->toBe('TEST0001');
});

test('set returns null from null value', function () {
    $result = $this->cast->set($this->model, 'bank_details', null, []);

    expect($result)->toBeNull();
});

test('set returns null from empty BankDetails', function () {
    $bankDetails = BankDetails::empty();

    $result = $this->cast->set($this->model, 'bank_details', $bankDetails, []);

    expect($result)->toBeNull();
});

test('set returns JSON string from array', function () {
    $data = ['bank_name' => 'Test Bank', 'ifsc' => 'TEST0001'];

    $result = $this->cast->set($this->model, 'bank_details', $data, []);

    expect($result)->toBeString();
    $decoded = json_decode($result, true);
    expect($decoded['bank_name'])->toBe('Test Bank');
});

test('set returns null from empty array', function () {
    $result = $this->cast->set($this->model, 'bank_details', [], []);

    expect($result)->toBeNull();
});

test('set returns null from array without bank_name', function () {
    $data = ['account_name' => 'Test Co', 'account_number' => '123'];

    $result = $this->cast->set($this->model, 'bank_details', $data, []);

    expect($result)->toBeNull();
});

test('roundtrip: set then get preserves data', function () {
    $original = new BankDetails(
        accountName: 'Clarity Technologies',
        accountNumber: '123456 0000 7890',
        bankName: 'Bank of Baroda',
        ifsc: 'BARB0MGROAD',
        branch: 'MG ROAD',
        swift: 'BARBINBBXXX',
        pan: 'ABCPE1234F',
    );

    $json = $this->cast->set($this->model, 'bank_details', $original, []);
    $restored = $this->cast->get($this->model, 'bank_details', $json, []);

    expect($restored->accountName)->toBe($original->accountName);
    expect($restored->accountNumber)->toBe($original->accountNumber);
    expect($restored->bankName)->toBe($original->bankName);
    expect($restored->ifsc)->toBe($original->ifsc);
    expect($restored->branch)->toBe($original->branch);
    expect($restored->swift)->toBe($original->swift);
    expect($restored->pan)->toBe($original->pan);
});
