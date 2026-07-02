<?php

use App\ValueObjects\ContactCollection;

describe('ContactCollection', function () {
    // --- add() method (lines 47-62) ---

    it('adds a contact with trimmed name and email', function () {
        $collection = new ContactCollection;
        $result = $collection->add('  John Doe  ', '  john@example.com  ');

        expect($result->count())->toBe(1);
        expect($result->first())->toBe(['name' => 'John Doe', 'email' => 'john@example.com', 'receives_emails' => true]);
    });

    it('throws exception when adding invalid email', function () {
        $collection = new ContactCollection;
        $collection->add('John', 'not-an-email');
    })->throws(InvalidArgumentException::class, 'Invalid email address: not-an-email');

    it('does not add duplicate email', function () {
        $collection = new ContactCollection([
            ['name' => 'John', 'email' => 'john@example.com'],
        ]);
        $result = $collection->add('John Doe', 'john@example.com');

        expect($result->count())->toBe(1);
        expect($result)->toBe($collection);
    });

    // --- remove() method (lines 64-69) ---

    it('removes a contact by email', function () {
        $collection = new ContactCollection([
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => 'Jane', 'email' => 'jane@example.com'],
        ]);
        $result = $collection->remove('john@example.com');

        expect($result->count())->toBe(1);
        expect($result->first()['email'])->toBe('jane@example.com');
    });

    // --- hasEmail() method (lines 71-76) ---

    it('checks if email exists with trimming', function () {
        $collection = new ContactCollection([
            ['name' => 'John', 'email' => 'john@example.com'],
        ]);

        expect($collection->hasEmail('john@example.com'))->toBeTrue();
        expect($collection->hasEmail('  john@example.com  '))->toBeTrue();
        expect($collection->hasEmail('missing@example.com'))->toBeFalse();
    });

    // --- getByEmail() method (lines 78-83) ---

    it('gets contact by email with trimming', function () {
        $collection = new ContactCollection([
            ['name' => 'John', 'email' => 'john@example.com'],
        ]);

        expect($collection->getByEmail('  john@example.com  '))->toBe(['name' => 'John', 'email' => 'john@example.com', 'receives_emails' => true]);
        expect($collection->getByEmail('missing@example.com'))->toBeNull();
    });

    // --- getEmails() / getNames() methods (lines 100-108) ---

    it('returns all emails', function () {
        $collection = new ContactCollection([
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => 'Jane', 'email' => 'jane@example.com'],
        ]);

        expect($collection->getEmails())->toBe(['john@example.com', 'jane@example.com']);
    });

    it('returns all names', function () {
        $collection = new ContactCollection([
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => 'Jane', 'email' => 'jane@example.com'],
        ]);

        expect($collection->getNames())->toBe(['John', 'Jane']);
    });

    // --- getDisplayName() method (lines 119-127) ---

    it('returns display name with name and email', function () {
        $collection = new ContactCollection([
            ['name' => 'John Doe', 'email' => 'john@example.com'],
        ]);

        expect($collection->getDisplayName('john@example.com'))->toBe('John Doe (john@example.com)');
    });

    it('returns just email when name is empty', function () {
        $collection = new ContactCollection([
            ['name' => '', 'email' => 'john@example.com'],
        ]);

        expect($collection->getDisplayName('john@example.com'))->toBe('john@example.com');
    });

    it('returns email when contact not found', function () {
        $collection = new ContactCollection;

        expect($collection->getDisplayName('unknown@example.com'))->toBe('unknown@example.com');
    });

    // --- jsonSerialize() method (line 141) ---

    it('json serializes contacts', function () {
        $collection = new ContactCollection([
            ['name' => 'John', 'email' => 'john@example.com'],
        ]);

        expect($collection->jsonSerialize())->toBe([
            ['name' => 'John', 'email' => 'john@example.com', 'receives_emails' => true],
        ]);
    });

    // --- __toString() method (lines 144-149) ---

    it('converts to string with display names', function () {
        $collection = new ContactCollection([
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => '', 'email' => 'jane@example.com'],
        ]);

        expect((string) $collection)->toBe('John (john@example.com), jane@example.com');
    });

    it('converts empty collection to empty string', function () {
        $collection = new ContactCollection;

        expect((string) $collection)->toBe('');
    });

    // --- validate() method (lines 164, 168) ---

    it('throws on invalid email in contacts', function () {
        new ContactCollection([
            ['name' => 'John', 'email' => 'not-valid'],
        ]);
    })->throws(InvalidArgumentException::class, 'Invalid email address: not-valid');

    // --- isValidContact() filtering (lines 151-158) ---

    it('filters out invalid contact structures', function () {
        $collection = new ContactCollection([
            ['name' => 'John', 'email' => 'john@example.com'],
            'not-an-array-or-email',
            ['missing_email' => true],
            ['name' => 123, 'email' => 'x@y.com'],
            ['name' => 'Test', 'email' => ''],
        ]);

        expect($collection->count())->toBe(1);
    });

    // --- normalization of null/missing name (regression guard) ---

    it('keeps contacts whose name is null or missing', function () {
        // Laravel's ConvertEmptyStringsToNull middleware turns '' into null
        // before requests reach the controller; the cast then receives a
        // contact with name=null. The contact must survive as name=''.
        $collection = new ContactCollection([
            ['name' => null, 'email' => 'nulled@example.com'],
            ['email' => 'missing@example.com'],
        ]);

        expect($collection->count())->toBe(2);
        expect($collection->getEmails())->toBe([
            'nulled@example.com',
            'missing@example.com',
        ]);
        expect($collection->getNames())->toBe(['', '']);
    });

    // --- Legacy string email conversion (line 18-19) ---

    it('converts legacy email strings to contact format', function () {
        $collection = new ContactCollection(['john@example.com']);

        expect($collection->count())->toBe(1);
        expect($collection->first())->toBe(['name' => '', 'email' => 'john@example.com', 'receives_emails' => true]);
    });

    // --- fromJson error handling (lines 29-38) ---

    it('throws on invalid JSON', function () {
        ContactCollection::fromJson('not json');
    })->throws(InvalidArgumentException::class, 'Invalid JSON provided');

    it('handles non-array JSON gracefully', function () {
        $collection = ContactCollection::fromJson('"just a string"');

        expect($collection->isEmpty())->toBeTrue();
    });

    // --- receives_emails flag ---

    it('defaults receives_emails to true when absent', function () {
        $collection = new ContactCollection([
            ['name' => 'John', 'email' => 'john@example.com'],
        ]);

        expect($collection->first()['receives_emails'])->toBeTrue();
    });

    it('preserves an explicit receives_emails false', function () {
        $collection = new ContactCollection([
            ['name' => 'John', 'email' => 'john@example.com', 'receives_emails' => false],
        ]);

        expect($collection->first()['receives_emails'])->toBeFalse();
    });

    it('normalizes truthy/falsy receives_emails values to booleans', function () {
        $collection = new ContactCollection([
            ['name' => 'A', 'email' => 'a@example.com', 'receives_emails' => 0],
            ['name' => 'B', 'email' => 'b@example.com', 'receives_emails' => '1'],
        ]);

        $contacts = $collection->toArray();
        expect($contacts[0]['receives_emails'])->toBeFalse();
        expect($contacts[1]['receives_emails'])->toBeTrue();
    });

    it('adds a contact with the receives_emails flag', function () {
        $collection = (new ContactCollection)->add('John', 'john@example.com', false);

        expect($collection->first()['receives_emails'])->toBeFalse();
    });
});
