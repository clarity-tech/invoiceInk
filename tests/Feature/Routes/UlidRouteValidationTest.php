<?php

use Illuminate\Support\Facades\URL;

it('returns 404 for invalid ULID with wrong length', function () {
    $this->get('/invoices/view/tooshort')->assertNotFound();
});

it('returns 404 for invalid ULID with invalid characters', function () {
    // ULIDs exclude I, L, O, U
    $this->get('/invoices/view/OOOOOOOOOOOOOOOOOOOOOOOOOO')->assertNotFound();
});

it('returns 404 for valid ULID format not in database', function () {
    // Valid ULID format (26 base32 chars) but doesn't exist. Signed, or it
    // would be refused before the lookup.
    $link = URL::temporarySignedRoute('invoices.public', now()->addDay(), ['ulid' => '01ARZ3NDEKTSV4RRFFQ69G5FAV'], absolute: false);

    $this->get($link)->assertNotFound();
});

it('returns 404 for invalid ULID on PDF routes', function () {
    $this->get('/invoices/tooshort/pdf')->assertNotFound();
});

it('returns 404 for invalid ULID on estimate routes', function () {
    $this->get('/estimates/view/tooshort')->assertNotFound();
});
