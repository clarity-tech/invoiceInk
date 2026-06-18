<?php

namespace App\Mail\Concerns;

use Symfony\Component\Mime\Email;

/**
 * Embeds the platform logo as an inline (CID) attachment on the outgoing
 * message so it renders even in clients that block remote images. The shared
 * mail templates reference it via <img src="cid:{{ config('branding.logo_cid') }}">.
 */
trait EmbedsBrandLogo
{
    protected function embedBrandLogo(Email $message): void
    {
        $path = public_path((string) config('branding.logo'));

        if (is_file($path)) {
            $message->embedFromPath($path, (string) config('branding.logo_cid'), 'image/png');
        }
    }
}
