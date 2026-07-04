<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Email;

/**
 * Embeds the brand mark as an inline (CID) image on every outgoing email that
 * references it, whenever the platform is in embedded-logo mode.
 *
 * Mailables such as DocumentMailer and TeamInvitation attach the mark
 * themselves via the EmbedsBrandLogo trait, but framework notifications
 * (password reset, email verification) render the shared markdown header/footer
 * — which reference cid:{logo_cid} — without any such hook. This listener is the
 * single place that guarantees the CID always resolves, so the wordmark stays
 * legible in dark mode across the whole application rather than showing a broken
 * image. It is idempotent: if a mailable already embedded the mark, it is left
 * untouched.
 */
class EmbedBrandLogoInEmails
{
    public function handle(MessageSending $event): void
    {
        if (config('branding.logo_external')) {
            return;
        }

        $message = $event->message;

        if (! $message instanceof Email) {
            return;
        }

        $cid = (string) config('branding.logo_cid');
        $html = $message->getHtmlBody();

        // Only embed when the template actually references the inline mark.
        if (! is_string($html) || ! str_contains($html, 'cid:'.$cid)) {
            return;
        }

        // Skip if a mailable already attached the mark under this CID.
        foreach ($message->getAttachments() as $part) {
            if ($part->getFilename() === $cid) {
                return;
            }
        }

        $path = public_path((string) config('branding.logo_mark'));

        if (is_file($path)) {
            $message->embedFromPath($path, $cid, 'image/png');
        }
    }
}
