<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use InvalidArgumentException;
use JsonSerializable;

class ContactCollection implements Arrayable, Jsonable, JsonSerializable
{
    private array $contacts;

    public function __construct(array $contacts = [])
    {
        $this->contacts = array_values(array_filter($contacts, fn($contact) => $this->isValidContact($contact)));
        $this->validate();
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON provided');
        }

        return new self(is_array($data) ? $data : []);
    }

    public static function fromArray(array $contacts): self
    {
        return new self($contacts);
    }

    public function add(string $name, string $email): self
    {
        $name = trim($name);
        $email = trim($email);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email address: {$email}");
        }

        if (! $this->hasEmail($email)) {
            $contacts = $this->contacts;
            $contacts[] = ['name' => $name, 'email' => $email];

            return new self($contacts);
        }

        return $this;
    }

    public function remove(string $email): self
    {
        $contacts = array_filter($this->contacts, fn($contact) => $contact['email'] !== trim($email));

        return new self($contacts);
    }

    public function hasEmail(string $email): bool
    {
        $email = trim($email);
        return collect($this->contacts)->contains('email', $email);
    }

    public function getByEmail(string $email): ?array
    {
        $email = trim($email);
        return collect($this->contacts)->firstWhere('email', $email);
    }

    public function isEmpty(): bool
    {
        return empty($this->contacts);
    }

    public function count(): int
    {
        return count($this->contacts);
    }

    public function first(): ?array
    {
        return $this->contacts[0] ?? null;
    }

    public function getEmails(): array
    {
        return collect($this->contacts)->pluck('email')->toArray();
    }

    public function getNames(): array
    {
        return collect($this->contacts)->pluck('name')->toArray();
    }

    public function getFirstEmail(): ?string
    {
        $first = $this->first();
        return $first ? $first['email'] : null;
    }

    public function getDisplayName(string $email): string
    {
        $contact = $this->getByEmail($email);
        if (!$contact) {
            return $email;
        }

        $name = trim($contact['name']);
        return $name ? "{$name} ({$email})" : $email;
    }

    public function toArray(): array
    {
        return $this->contacts;
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->contacts, $options);
    }

    public function jsonSerialize(): array
    {
        return $this->contacts;
    }

    public function __toString(): string
    {
        return collect($this->contacts)
            ->map(fn($contact) => $this->getDisplayName($contact['email']))
            ->join(', ');
    }

    private function isValidContact($contact): bool
    {
        return is_array($contact) 
            && isset($contact['name'], $contact['email']) 
            && is_string($contact['name']) 
            && is_string($contact['email'])
            && !empty(trim($contact['email']));
    }

    private function validate(): void
    {
        foreach ($this->contacts as $contact) {
            if (!$this->isValidContact($contact)) {
                throw new InvalidArgumentException('Invalid contact structure. Must have name and email keys.');
            }

            if (! filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("Invalid email address: {$contact['email']}");
            }
        }
    }
}