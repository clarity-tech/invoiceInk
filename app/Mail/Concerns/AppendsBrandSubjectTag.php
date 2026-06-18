<?php

namespace App\Mail\Concerns;

/**
 * Optionally appends a platform attribution tag to an email subject
 * (e.g. "Invoice #INV-001 - Sent Using InvoiceInk"), controlled by
 * config('branding.subject_tag'). Applied consistently across all mailables
 * so subject branding can be toggled in one place.
 */
trait AppendsBrandSubjectTag
{
    protected function withBrandSubjectTag(string $subject): string
    {
        if (! config('branding.subject_tag')) {
            return $subject;
        }

        $suffix = trim((string) (config('branding.subject_tag_text') ?? 'Sent Using '.config('app.name')));

        return $suffix === '' ? $subject : $subject.' - '.$suffix;
    }
}
