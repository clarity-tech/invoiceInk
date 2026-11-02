<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * robots.txt and the sitemap, rooted at APP_URL so that every deployment,
 * self-hosted ones included, points crawlers at its own public domain.
 */
class SeoController extends Controller
{
    public function robots(): Response
    {
        $body = implode("\n", [
            'User-agent: *',
            'Disallow:',
            '',
            'Sitemap: '.$this->absolute(route('sitemap', absolute: false)),
            '',
        ]);

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $entries = collect([
            '/',
            route('terms.show', absolute: false),
            route('policy.show', absolute: false),
        ])->map(fn (string $path): string => '<url><loc>'.e($this->absolute($path)).'</loc></url>');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .$entries->implode('')
            .'</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function absolute(string $path): string
    {
        return rtrim(config('app.url'), '/').'/'.ltrim($path, '/');
    }
}
