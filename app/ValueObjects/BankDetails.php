<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

readonly class BankDetails implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $accountName = '',
        public string $accountNumber = '',
        public string $bankName = '',
        public string $ifsc = '',
        public string $branch = '',
        public string $swift = '',
        public string $pan = '',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            accountName: trim((string) ($data['account_name'] ?? '')),
            accountNumber: trim((string) ($data['account_number'] ?? '')),
            bankName: trim((string) ($data['bank_name'] ?? '')),
            ifsc: trim((string) ($data['ifsc'] ?? '')),
            branch: trim((string) ($data['branch'] ?? '')),
            swift: trim((string) ($data['swift'] ?? '')),
            pan: trim((string) ($data['pan'] ?? '')),
        );
    }

    public static function empty(): self
    {
        return new self;
    }

    /**
     * Check if bank details have meaningful data (at minimum a bank name).
     */
    public function isConfigured(): bool
    {
        return $this->bankName !== '';
    }

    public function isEmpty(): bool
    {
        return ! $this->isConfigured();
    }

    public function toArray(): array
    {
        return array_filter([
            'account_name' => $this->accountName,
            'account_number' => $this->accountNumber,
            'bank_name' => $this->bankName,
            'ifsc' => $this->ifsc,
            'branch' => $this->branch,
            'swift' => $this->swift,
            'pan' => $this->pan,
        ]);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
