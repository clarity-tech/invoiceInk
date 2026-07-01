<?php

namespace App\Support;

class Branding
{
    /**
     * Build a platform brand link tagged with UTM attribution parameters so
     * referral traffic from generated documents and emails can be tracked.
     * The channel identifies where the link surfaced (e.g. "pdf", "web",
     * "email") and becomes the utm_source; the content identifies the specific
     * placement within that channel (e.g. "brand_header", "powered_by") and
     * becomes the utm_content. The medium and campaign are constant across
     * placements and read from config. Empty UTM values are omitted.
     */
    public static function attributedUrl(string $channel, ?string $content = null): string
    {
        $url = (string) config('app.url');

        $params = array_filter([
            'utm_source' => $channel,
            'utm_medium' => config('branding.utm_medium'),
            'utm_campaign' => config('branding.utm_campaign'),
            'utm_content' => $content,
        ], static fn ($value): bool => $value !== null && $value !== '');

        return $params === [] ? $url : $url.'?'.http_build_query($params);
    }

    /**
     * The footer "powered by" brand link for the given channel, tagged with the
     * "powered_by" placement as its utm_content.
     */
    public static function poweredByUrl(string $channel): string
    {
        return self::attributedUrl($channel, 'powered_by');
    }
}
