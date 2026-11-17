<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a response out of search results. It is a header rather than a meta
 * tag so that it covers PDFs too.
 *
 * On the share-link routes it is listed before ValidateShareLink, so the 403s
 * that middleware returns or throws carry the header too. The sign-in screens
 * get it through `fortify.middleware`, with log in and register excepted.
 */
class AddNoIndexHeader
{
    /**
     * The middleware for a group of routes some of which may be indexed.
     *
     * Those routes are left alone only while the deployment is indexable, so
     * staging (APP_INDEXABLE=false) still marks them noindex.
     */
    public static function except(string ...$routeNames): string
    {
        return static::class.':'.implode(',', $routeNames);
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$indexableRoutes): Response
    {
        $response = $next($request);

        if (config('app.indexable') && $request->routeIs(...$indexableRoutes)) {
            return $response;
        }

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
