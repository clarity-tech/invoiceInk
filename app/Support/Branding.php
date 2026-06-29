<?php

namespace App\Support;

class Branding
{
    /**
     * Build a platform brand link tagged with UTM attribution parameters so
     * referral traffic from generated documents and emails can be tracked.
     * The medium identifies the channel (e.g. "pdf", "web", "email") and the
     * campaign the placement intent (e.g. "powered_by", "brand_header");
     * empty UTM values are omitted.
     */
    public static function attributedUrl(string $medium, ?string $campaign): string
    {
        $url = (string) config('app.url');

        $params = array_filter([
            'utm_source' => config('branding.utm_source'),
            'utm_medium' => $medium,
            'utm_campaign' => $campaign,
        ], static fn ($value): bool => $value !== null && $value !== '');

        return $params === [] ? $url : $url.'?'.http_build_query($params);
    }

    /**
     * The footer "powered by" brand link, using the configured campaign.
     */
    public static function poweredByUrl(string $medium): string
    {
        return self::attributedUrl($medium, config('branding.utm_campaign'));
    }
}
