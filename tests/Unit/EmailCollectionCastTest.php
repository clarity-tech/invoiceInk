<?php

use App\Casts\EmailCollectionCast;
use App\Models\Organization;
use App\ValueObjects\EmailCollection;

test('can cast null to empty email collection', function () {
    $cast = new EmailCollectionCast;
    $model = new Organization;

    $result = $cast->get($model, 'emails', null, []);

    expect($result)->toBeInstanceOf(EmailCollection::class);
    expect($result->isEmpty())->toBeTrue();
});

test('can cast json string to email collection', function () {
    $cast = new EmailCollectionCast;
    $model = new Organization;
    $emails = ['test@example.com', 'user@domain.org'];
    $json = json_encode($emails);

    $result = $cast->get($model, 'emails', $json, []);

    expect($result)->toBeInstanceOf(EmailCollection::class);
    expect($result->toArray())->toBe($emails);
});

test('can cast array to email collection', function () {
    $cast = new EmailCollectionCast;
    $model = new Organization;
    $emails = ['test@example.com', 'user@domain.org'];

    $result = $cast->get($model, 'emails', $emails, []);

    expect($result)->toBeInstanceOf(EmailCollection::class);
    expect($result->toArray())->toBe($emails);
});

test('returns empty collection for invalid input', function () {
    $cast = new EmailCollectionCast;
    $model = new Organization;

    $result = $cast->get($model, 'emails', 'invalid', []);

    expect($result)->toBeInstanceOf(EmailCollection::class);
    expect($result->isEmpty())->toBeTrue();
});

test('can set null value', function () {
    $cast = new EmailCollectionCast;
    $model = new Organization;

    $result = $cast->set($model, 'emails', null, []);

    expect($result)->toBe('[]');
});

test('can set email collection', function () {
    $cast = new EmailCollectionCast;
    $model = new Organization;
    $emails = ['test@example.com', 'user@domain.org'];
    $collection = new EmailCollection($emails);

    $result = $cast->set($model, 'emails', $collection, []);

    expect($result)->toBe(json_encode($emails));
});

test('can set array', function () {
    $cast = new EmailCollectionCast;
    $model = new Organization;
    $emails = ['test@example.com', 'user@domain.org'];

    $result = $cast->set($model, 'emails', $emails, []);

    expect($result)->toBe(json_encode($emails));
});

test('can set string as single email', function () {
    $cast = new EmailCollectionCast;
    $model = new Organization;
    $email = 'test@example.com';

    $result = $cast->set($model, 'emails', $email, []);

    expect($result)->toBe(json_encode([$email]));
});

test('returns empty array json for invalid input', function () {
    $cast = new EmailCollectionCast;
    $model = new Organization;

    $result = $cast->set($model, 'emails', 123, []);

    expect($result)->toBe('[]');
});
