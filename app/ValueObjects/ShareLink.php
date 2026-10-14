<?php

namespace App\ValueObjects;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Arrayable;

/**
 * A signed link to a document, and when it stops working.
 *
 * The two travel together because wherever a link is shown, its expiry is
 * worth showing too: the person sharing it and the person opening it both need
 * to know how long it will last.
 *
 * @implements Arrayable<string, string>
 */
readonly class ShareLink implements Arrayable
{
    public function __construct(
        public string $url,
        public CarbonInterface $expiresAt,
    ) {}

    /**
     * @return array{url: string, expires_at: string}
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'expires_at' => $this->expiresAt->toIso8601String(),
        ];
    }
}
