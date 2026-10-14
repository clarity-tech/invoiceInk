<?php

namespace App\Http\Middleware;

use App\Models\Invoice;
use App\Models\Scopes\OrganizationScope;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets a share link through only while it is genuine and unexpired.
 *
 * Laravel's `signed` middleware answers an expired link and a forged one with
 * the same bare 403. An expired link was really sent to someone, though, so it
 * gets a page that says who to ask for a new one; a forged one learns nothing.
 */
class ValidateShareLink
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Checked before anything reads the database, so a guessed ULID is
        // refused the same way whether or not the document exists. A link
        // without an expiry is refused too: every share link is minted with one.
        if (! is_string($request->query('expires')) || ! $this->hasCorrectSignature($request)) {
            throw new InvalidSignatureException;
        }

        if (URL::signatureHasNotExpired($request)) {
            return $next($request);
        }

        $document = Invoice::withoutGlobalScope(OrganizationScope::class)
            ->with('organization')
            ->where('ulid', $request->route('ulid'))
            ->firstOrFail();

        return response()->view('public.link-expired', ['document' => $document], Response::HTTP_FORBIDDEN);
    }

    /**
     * Only the expiry is signed alongside the path, so anything else on the
     * query string is left out of the check. Messengers and mail trackers
     * append their own parameters (fbclid, utm_source, ...), and a link that
     * stopped working once shared would be no use to anyone. Nothing the page
     * shows depends on them.
     */
    private function hasCorrectSignature(Request $request): bool
    {
        return URL::hasCorrectSignature(
            $request,
            absolute: false,
            ignoreQuery: fn (string $parameter): bool => $parameter !== 'expires',
        );
    }
}
