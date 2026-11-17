<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

/**
 * robots.txt and the sitemap, rooted at APP_URL so that every deployment,
 * self-hosted ones included, points crawlers at its own public domain.
 */
class SeoController extends Controller
{
    public function robots(): Response
    {
        if (! config('app.indexable')) {
            return response("User-agent: *\nDisallow: /\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        $body = implode("\n", [
            'User-agent: *',
            'Disallow:',
            '',
            'Sitemap: '.$this->absolute(route('sitemap', absolute: false)),
            '',
        ]);

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * Every page meant to be found by search, and nothing else.
     *
     * Register is listed because it is where a search for the app should land
     * someone new; a self-hosted copy with registration switched off has no
     * such page. Log in is left out: it is for people who already know the
     * way, and every page links to it.
     */
    public function sitemap(): Response
    {
        $entries = collect([
            '/',
            route('marketing.gst-invoicing', absolute: false),
            route('marketing.gst-invoice-format', absolute: false),
            route('marketing.hsn-sac-codes', absolute: false),
            route('marketing.claude', absolute: false),
            route('marketing.open-source', absolute: false),
            Route::has('register') ? route('register', absolute: false) : null,
            route('terms.show', absolute: false),
            route('policy.show', absolute: false),
        ])->filter()->map(fn (string $path): string => '<url><loc>'.e($this->absolute($path)).'</loc></url>');

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
