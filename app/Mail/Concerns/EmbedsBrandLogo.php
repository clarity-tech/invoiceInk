<?php

namespace App\Mail\Concerns;

use Symfony\Component\Mime\Email;

/**
 * Embeds the brand mark as an inline (CID) image, but only when the email logo
 * is in "embedded" mode (config('branding.logo_external') === false). In the
 * default external mode the templates reference a hosted URL and nothing is
 * attached, so this is a no-op. When embedded, mail templates reference the
 * mark via <img src="cid:{{ config('branding.logo_cid') }}">.
 */
trait EmbedsBrandLogo
{
    protected function embedBrandLogo(Email $message): void
    {
        if (config('branding.logo_external')) {
            return;
        }

        $path = public_path((string) config('branding.logo_mark'));

        if (is_file($path)) {
            $message->embedFromPath($path, (string) config('branding.logo_cid'), 'image/png');
        }
    }
}
