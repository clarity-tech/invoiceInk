<?php

namespace App\Support;

/**
 * The recipients remembered on a document, stored in its `email_recipients`
 * JSON column as a list of `['email' => ..., 'checked' => bool]`. The invoice
 * form and the email composer both pre-select the checked ones, so every
 * writer goes through here to keep that list in one shape.
 */
class EmailRecipients
{
    /**
     * Normalize a submitted recipient list into a deduped array of
     * `['email' => ..., 'checked' => bool]`, unique by lowercased email. A
     * missing `checked` defaults to true. Legacy plain-string email entries
     * (older invoices/seed data) are treated as checked.
     *
     * @param  array<int, array{email?: string, checked?: mixed}|string>  $recipients
     * @return array<int, array{email: string, checked: bool}>
     */
    public static function normalize(array $recipients): array
    {
        $seen = [];
        $normalized = [];

        foreach ($recipients as $recipient) {
            if (is_string($recipient)) {
                $recipient = ['email' => $recipient, 'checked' => true];
            }

            if (! is_array($recipient)) {
                continue;
            }

            $email = trim($recipient['email'] ?? '');

            if ($email === '') {
                continue;
            }

            $key = strtolower($email);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $normalized[] = [
                'email' => $email,
                'checked' => filter_var($recipient['checked'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        return $normalized;
    }

    /**
     * Merge the addresses a document was just sent to into its remembered
     * recipient list. Sent addresses become checked (new ones appended); any
     * previously remembered address that was not sent this time is kept but
     * marked unchecked.
     *
     * @param  array<int, array{email?: string, checked?: mixed}|string>  $existing
     * @param  array<int, string>  $sent
     * @return array<int, array{email: string, checked: bool}>
     */
    public static function merge(array $existing, array $sent): array
    {
        $sentKeys = collect($sent)
            ->map(fn (string $email): string => strtolower(trim($email)))
            ->filter()
            ->flip();

        $result = [];
        $seen = [];

        foreach (self::normalize($existing) as $entry) {
            $key = strtolower($entry['email']);
            $seen[$key] = true;
            $result[] = ['email' => $entry['email'], 'checked' => $sentKeys->has($key)];
        }

        foreach ($sent as $email) {
            $email = trim($email);
            $key = strtolower($email);

            if ($email === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = ['email' => $email, 'checked' => true];
        }

        return $result;
    }
}
