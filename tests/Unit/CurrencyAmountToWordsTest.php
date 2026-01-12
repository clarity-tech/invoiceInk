<?php

use App\Currency;

test('INR whole amount converts to words', function () {
    $result = Currency::INR->amountToWords(4720000);

    expect($result)->toBe('Indian Rupee Forty-Seven Thousand Two Hundred Only');
});

test('INR amount with paise converts to words', function () {
    $result = Currency::INR->amountToWords(4720050);

    expect($result)->toBe('Indian Rupee Forty-Seven Thousand Two Hundred and Fifty Paise Only');
});

test('USD amount converts to words', function () {
    $result = Currency::USD->amountToWords(150000);

    expect($result)->toBe('US Dollar One Thousand Five Hundred Only');
});

test('USD amount with cents converts to words', function () {
    $result = Currency::USD->amountToWords(150099);

    expect($result)->toBe('US Dollar One Thousand Five Hundred and Ninety-Nine Cents Only');
});

test('GBP amount converts to words with Pence', function () {
    $result = Currency::GBP->amountToWords(10050);

    expect($result)->toBe('British Pound One Hundred and Fifty Pence Only');
});

test('AED amount converts to words with Fils', function () {
    $result = Currency::AED->amountToWords(500025);

    expect($result)->toBe('UAE Dirham Five Thousand and Twenty-Five Fils Only');
});

test('JPY amount converts to words without subunit', function () {
    $result = Currency::JPY->amountToWords(100000);

    expect($result)->toBe('Japanese Yen One Thousand Only');
});

test('zero amount converts to words', function () {
    $result = Currency::INR->amountToWords(0);

    expect($result)->toBe('Indian Rupee Zero Only');
});

test('large amount converts to words', function () {
    // 10,00,000.00 INR = 1 million cents = 10 lakh
    $result = Currency::INR->amountToWords(10000000);

    expect($result)->toContain('Indian Rupee');
    expect($result)->toContain('Only');
    expect($result)->toContain('One Hundred Thousand');
});

test('EUR amount with cents converts to words', function () {
    $result = Currency::EUR->amountToWords(99999);

    expect($result)->toBe('Euro Nine Hundred Ninety-Nine and Ninety-Nine Cents Only');
});
