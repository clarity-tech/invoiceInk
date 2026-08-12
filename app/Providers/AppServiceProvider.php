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

        Event::listen(MessageSending::class, EmbedBrandLogoInEmails::class);
    }

    /**
     * Root every absolute URL at the configured public domain — on Cloud Run.
     *
     * UrlGenerator otherwise takes its root from the incoming request, and
     * APP_URL is only consulted for console requests. The queue worker is a
     * separate Cloud Run service that receives jobs as an HTTP POST to
     * /handle-task on its own *.run.app hostname — so any absolute URL built
     * while a job runs (the emailed "View Invoice Online" CTA, signed
     * team-invitation links, Fortify's password-reset link) would point at that
     * internal host instead of the domain customers can actually reach.
     *
     * The gate keys on *where* the app is running, not on the environment
     * name, so every Cloud Run deployment is covered — a staging service has
     * the same split architecture and the same defect, and would be missed by
     * an APP_ENV === 'production' check.
     *
     * It is load-bearing, not caution. Anything that serves the app on another
     * origin sets its own root and must keep it: Pest's browser plugin boots an
     * HTTP server on a random port and calls useOrigin() with it, and since the
     * application is rebuilt per test, forcing unconditionally clobbered that
     * on every boot — form posts went to APP_URL instead, so login and
     * registration bounced back to their forms.
     *
     * On the Cloud Run web service this is a no-op: the Cloudflare Worker sets
     * X-Forwarded-Host to the public domain and all proxies are trusted, so the
     * request root already is APP_URL. It is idempotent — the same config value
     * on every boot, and setRequest() leaves the forced root intact — so it is
     * safe under Octane's long-running workers.
     */
    private function forcePublicRootUrl(): void
    {
        if (! config('app.cloud_run_service')) {
            return;
        }

        if (! $root = config('app.url')) {
            return;
        }

        URL::useOrigin($root);
        URL::forceScheme(parse_url($root, PHP_URL_SCHEME) ?: 'https');
    }
}
