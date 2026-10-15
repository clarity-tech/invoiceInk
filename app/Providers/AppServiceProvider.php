<?php

namespace App\Providers;

use App\Contracts\Services\InvoiceNumberingServiceInterface;
use App\Contracts\Services\PdfServiceInterface;
use App\Listeners\EmbedBrandLogoInEmails;
use App\Services\InvoiceNumberingService;
use App\Services\PdfService;
use Carbon\CarbonInterval;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PdfServiceInterface::class, PdfService::class);
        $this->app->bind(InvoiceNumberingServiceInterface::class, InvoiceNumberingService::class);

        // MCP clients sign in with the authorization code flow only. Set here
        // rather than in boot() because Passport reads it while registering its
        // routes, and the device flow would add public pages nothing uses.
        Passport::$deviceCodeGrantEnabled = false;
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->forcePublicRootUrl();
        $this->configurePassport();

        // Each draft is a paid call to the AI provider.
        RateLimiter::for('invoice-drafts', fn (Request $request) => Limit::perHour(config('services.invoice_drafts.per_hour'))
            ->by($request->user()?->id ?: $request->ip())
            ->response(fn () => back()->withErrors(['draft' => __('messages.invoice_draft.rate_limited')])));

        RateLimiter::for('email-drafts', fn (Request $request) => Limit::perHour(config('services.email_drafts.per_hour'))
            ->by($request->user()?->id ?: $request->ip())
            ->response(fn () => response()->json([
                'message' => __('messages.email_draft.rate_limited'),
                'errors' => ['draft' => [__('messages.email_draft.rate_limited')]],
            ], 422)));

        Event::listen(MessageSending::class, EmbedBrandLogoInEmails::class);
    }

    /**
     * Configure the OAuth server that MCP clients sign in through.
     *
     * Passport's defaults issue access tokens that live for a year. A public
     * client such as Claude refreshes on a 401 and ahead of expiry, so a short
     * access token costs nothing and bounds how long a leaked one stays
     * useful. Refresh tokens rotate on every use, so a connection in regular
     * use never reaches the 30 days; one left idle that long has to sign in
     * again.
     *
     * OAuth exists here only for MCP, so a client that omits the scope
     * parameter gets the MCP scope rather than a token every tool refuses.
     */
    private function configurePassport(): void
    {
        Passport::tokensExpireIn(CarbonInterval::hour());
        Passport::refreshTokensExpireIn(CarbonInterval::days(30));
        Passport::defaultScopes([Registrar::OAUTH_SCOPE]);

        Passport::authorizationView(fn (array $parameters): InertiaResponse => $this->renderOAuthConsent(
            $parameters['client'],
            $parameters['request'],
            $parameters['authToken'],
        ));
    }

    /**
     * Render the consent screen for an MCP client asking to read this account.
     *
     * The client name is whatever the client sent when it registered itself,
     * so it proves nothing. The redirect host is the part a user can actually
     * check, and the MCP authorization spec requires showing it.
     */
    private function renderOAuthConsent(Client $client, Request $request, string $authToken): InertiaResponse
    {
        $redirectUri = $request->query('redirect_uri') ?: ($client->redirect_uris[0] ?? '');
        $redirectHost = (string) parse_url((string) $redirectUri, PHP_URL_HOST);

        return Inertia::render('OAuth/Authorize', [
            'clientId' => $client->getKey(),
            'clientName' => $client->name,
            'redirectHost' => $redirectHost,
            'isLocalRedirect' => in_array($redirectHost, ['localhost', '127.0.0.1', '[::1]'], true),
            'email' => $request->user()->email,
            'organization' => $request->user()->currentTeam?->name,
            'authToken' => $authToken,
            'csrfToken' => csrf_token(),
        ]);
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
