<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Pre-fill for the create form drafted from a document, and everything the
 * person reviewing it should check. Nothing here is saved until they save.
 *
 * @implements Arrayable<string, mixed>
 */
readonly class InvoiceDraft implements Arrayable
{
    /**
     * @param  array<string, mixed>  $defaults  in the shape of the create form's defaults
     * @param  list<string>  $warnings
     */
    public function __construct(
        public array $defaults,
        public array $warnings,
        public string $source,
    ) {}

    /**
     * @param  array{defaults: array<string, mixed>, warnings: list<string>, source: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['defaults'], $data['warnings'], $data['source']);
    }

    /**
     * @return array{defaults: array<string, mixed>, warnings: list<string>, source: string}
     */
    public function toArray(): array
    {
        return [
            'defaults' => $this->defaults,
            'warnings' => $this->warnings,
            'source' => $this->source,
        ];
    }
}
