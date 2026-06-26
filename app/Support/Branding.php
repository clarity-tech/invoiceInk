<?php

namespace App\Support;

class Branding
{
    /**
     * Build the platform "powered by" brand link, tagged with UTM attribution
     * parameters so referral traffic from generated documents and emails can
     * be tracked. The medium identifies the placement (e.g. "pdf", "web",
     * "email"); empty UTM values are omitted.
     */
    public static function poweredByUrl(string $medium): string
    {
        $url = (string) config('app.url');

        $params = array_filter([
            'utm_source' => config('branding.utm_source'),
            'utm_medium' => $medium,
            'utm_campaign' => config('branding.utm_campaign'),
        ], static fn ($value): bool => $value !== null && $value !== '');

        return $params === [] ? $url : $url.'?'.http_build_query($params);
    }
}
