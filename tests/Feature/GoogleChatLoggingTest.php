<?php

use Enigma\GoogleChatHandler;
use Illuminate\Support\Facades\Log;

// The channel exists so a production error is seen rather than sitting in
// Cloud Logging waiting for someone to go looking. These cover the ways it
// could make things worse instead.

it('defines the google-chat channel pointing at the package handler', function () {
    $channel = config('logging.channels.google-chat');

    expect($channel)->not->toBeNull()
        ->and($channel['driver'])->toBe('monolog')
        ->and($channel['handler'])->toBe(GoogleChatHandler::class);
});

it('only reports errors and above, not the warning noise', function () {
    // A warning-level default would page on every "Falling back to rate=1",
    // which is the opposite of useful.
    expect(config('logging.channels.google-chat.level'))->toBe('error');
});

it('is off by default so local and CI deliver nothing', function () {
    expect(config('logging.channels.google-chat.enabled'))->toBeFalse();
});

it('never throws when no webhook is configured', function () {
    // The package defaults `on_missing_url` to "throw". Left at that, an
    // unconfigured environment would turn a logged error into a second,
    // worse exception.
    expect(config('logging.channels.google-chat.on_missing_url'))->toBe('ignore');

    config(['logging.channels.google-chat.enabled' => true]);
    app('log')->forgetChannel('google-chat');

    $threw = null;

    try {
        Log::channel('google-chat')->error('probe');
    } catch (Throwable $e) {
        $threw = $e::class.': '.$e->getMessage();
    }

    expect($threw)->toBeNull();
});

it('bounds the synchronous HTTP call, because this runs on Octane workers', function () {
    // The handler posts inline in the request. Unbounded, a slow webhook would
    // hold an Octane worker for the client default of 30s.
    expect(config('logging.channels.google-chat.timeout'))->toBeLessThanOrEqual(10)
        ->and(config('logging.channels.google-chat.connect_timeout'))->toBeLessThanOrEqual(5)
        ->and(config('logging.channels.google-chat.retries'))->toBeLessThanOrEqual(2);
});

it('falls back to stderr so a delivery failure still reaches Cloud Logging', function () {
    expect(config('logging.channels.google-chat.fallback_channel'))->toBe('stderr');
});
