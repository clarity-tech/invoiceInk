<?php

namespace App\Providers;

use App\Contracts\Services\InvoiceNumberingServiceInterface;
use App\Contracts\Services\PdfServiceInterface;
use App\Listeners\EmbedBrandLogoInEmails;
use App\Services\InvoiceNumberingService;
use App\Services\PdfService;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PdfServiceInterface::class, PdfService::class);
        $this->app->bind(InvoiceNumberingServiceInterface::class, InvoiceNumberingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->forcePublicRootUrl();

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        Event::listen(MessageSending::class, EmbedBrandLogoInEmails::class);
    }

    /**
     * Root every absolute URL at the configured public domain.
     *
     * UrlGenerator otherwise takes its root from the incoming request, and
     * APP_URL is only consulted for console requests. In production the queue
     * worker is a separate Cloud Run service that receives jobs as an HTTP
     * POST to /handle-task on its own *.run.app hostname — so any absolute URL
     * built while a job runs (the emailed "View Invoice Online" CTA, signed
     * team-invitation links, Fortify's password-reset link) would point at that
     * internal host instead of the domain customers can actually reach.
     *
     * forceScheme only fixed the scheme, which is why those links came out as
     * https:// on the wrong host. The scheme is pinned from APP_URL too, so the
     * whole origin is deterministic rather than half-inherited from whatever
     * request happens to be in flight. Both are idempotent — set to the same
     * config value on every boot, and setRequest() leaves them intact — so this
     * is safe under Octane's long-running workers.
     */
    private function forcePublicRootUrl(): void
    {
        $root = config('app.url');

        if (! $root) {
            return;
        }

        URL::useOrigin($root);
        URL::forceScheme(parse_url($root, PHP_URL_SCHEME) ?: 'https');
    }
}
