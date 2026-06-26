<?php

use App\Support\PhoneNumber;

test('normalizes a national number to E.164 using the country hint', function () {
    expect(PhoneNumber::normalize('(415) 555-2671', 'US'))->toBe('+14155552671');
    expect(PhoneNumber::normalize('020 7183 8750', 'GB'))->toBe('+442071838750');
    expect(PhoneNumber::normalize('098765 43210', 'IN'))->toBe('+919876543210');
});

test('passes an already E.164 number through unchanged', function () {
    expect(PhoneNumber::normalize('+14155552671', null))->toBe('+14155552671');
    expect(PhoneNumber::normalize('+919876543210', 'IN'))->toBe('+919876543210');
});

test('returns null for blank or unparseable input', function () {
    expect(PhoneNumber::normalize(null, 'US'))->toBeNull();
    expect(PhoneNumber::normalize('', 'US'))->toBeNull();
    expect(PhoneNumber::normalize('   ', 'US'))->toBeNull();
    expect(PhoneNumber::normalize('not a phone', 'US'))->toBeNull();
});

test('formats a stored E.164 number for display', function () {
    expect(PhoneNumber::format('+14155552671'))->toBe('+1 415-555-2671');
    expect(PhoneNumber::format('+442071838750'))->toBe('+44 20 7183 8750');
});

test('format returns null for blank input', function () {
    expect(PhoneNumber::format(null))->toBeNull();
    expect(PhoneNumber::format(''))->toBeNull();
});
