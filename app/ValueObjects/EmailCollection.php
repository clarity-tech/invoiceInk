<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use InvalidArgumentException;
use JsonSerializable;

class EmailCollection implements Arrayable, Jsonable, JsonSerializable
{
    private array $emails;

    public function __construct(array $emails = [])
    {
        $this->emails = array_values(array_filter(array_map('trim', $emails)));
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

    public static function fromArray(array $emails): self
    {
        return new self($emails);
    }

    public function add(string $email): self
    {
        $email = trim($email);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email address: {$email}");
        }

        if (!in_array($email, $this->emails)) {
            $emails = $this->emails;
            $emails[] = $email;
            return new self($emails);
        }

        return $this;
    }

    public function remove(string $email): self
    {
        $emails = array_filter($this->emails, fn($e) => $e !== trim($email));
        return new self($emails);
    }

    public function has(string $email): bool
    {
        return in_array(trim($email), $this->emails);
    }

    public function isEmpty(): bool
    {
        return empty($this->emails);
    }

    public function count(): int
    {
        return count($this->emails);
    }

    public function first(): ?string
    {
        return $this->emails[0] ?? null;
    }

    public function toArray(): array
    {
        return $this->emails;
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->emails, $options);
    }

    public function jsonSerialize(): array
    {
        return $this->emails;
    }

    public function __toString(): string
    {
        return implode(', ', $this->emails);
    }

    private function validate(): void
    {
        foreach ($this->emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("Invalid email address: {$email}");
            }
        }
    }
}