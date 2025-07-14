<?php

use App\ValueObjects\EmailCollection;

test('can create empty email collection', function () {
    $collection = new EmailCollection();
    
    expect($collection->isEmpty())->toBeTrue();
    expect($collection->count())->toBe(0);
    expect($collection->toArray())->toBe([]);
});

test('can create email collection with valid emails', function () {
    $emails = ['test@example.com', 'user@domain.org'];
    $collection = new EmailCollection($emails);
    
    expect($collection->count())->toBe(2);
    expect($collection->toArray())->toBe($emails);
    expect($collection->has('test@example.com'))->toBeTrue();
});

test('filters out invalid emails during construction', function () {
    $emails = ['test@example.com', 'invalid-email', 'user@domain.org'];
    
    expect(fn() => new EmailCollection($emails))
        ->toThrow(InvalidArgumentException::class, 'Invalid email address: invalid-email');
});

test('trims whitespace from emails', function () {
    $emails = ['  test@example.com  ', ' user@domain.org '];
    $collection = new EmailCollection($emails);
    
    expect($collection->toArray())->toBe(['test@example.com', 'user@domain.org']);
});

test('can add valid email', function () {
    $collection = new EmailCollection(['test@example.com']);
    $newCollection = $collection->add('user@domain.org');
    
    expect($newCollection->count())->toBe(2);
    expect($newCollection->has('user@domain.org'))->toBeTrue();
    expect($collection->count())->toBe(1); // Original unchanged
});

test('cannot add invalid email', function () {
    $collection = new EmailCollection(['test@example.com']);
    
    expect(fn() => $collection->add('invalid-email'))
        ->toThrow(InvalidArgumentException::class);
});

test('does not add duplicate emails', function () {
    $collection = new EmailCollection(['test@example.com']);
    $newCollection = $collection->add('test@example.com');
    
    expect($newCollection->count())->toBe(1);
    expect($newCollection)->toBe($collection);
});

test('can remove email', function () {
    $collection = new EmailCollection(['test@example.com', 'user@domain.org']);
    $newCollection = $collection->remove('test@example.com');
    
    expect($newCollection->count())->toBe(1);
    expect($newCollection->has('test@example.com'))->toBeFalse();
    expect($newCollection->has('user@domain.org'))->toBeTrue();
});

test('can get first email', function () {
    $collection = new EmailCollection(['test@example.com', 'user@domain.org']);
    
    expect($collection->first())->toBe('test@example.com');
    
    $empty = new EmailCollection();
    expect($empty->first())->toBeNull();
});

test('can convert to json', function () {
    $emails = ['test@example.com', 'user@domain.org'];
    $collection = new EmailCollection($emails);
    
    expect($collection->toJson())->toBe(json_encode($emails));
    expect($collection->jsonSerialize())->toBe($emails);
});

test('can create from json', function () {
    $emails = ['test@example.com', 'user@domain.org'];
    $json = json_encode($emails);
    $collection = EmailCollection::fromJson($json);
    
    expect($collection->toArray())->toBe($emails);
});

test('throws exception for invalid json', function () {
    expect(fn() => EmailCollection::fromJson('invalid-json'))
        ->toThrow(InvalidArgumentException::class, 'Invalid JSON provided');
});

test('can create from array', function () {
    $emails = ['test@example.com', 'user@domain.org'];
    $collection = EmailCollection::fromArray($emails);
    
    expect($collection->toArray())->toBe($emails);
});

test('can convert to string', function () {
    $emails = ['test@example.com', 'user@domain.org'];
    $collection = new EmailCollection($emails);
    
    expect((string) $collection)->toBe('test@example.com, user@domain.org');
});
