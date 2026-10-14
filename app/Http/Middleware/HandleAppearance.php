<?php

namespace App\Http\Middleware;

use App\Enums\Appearance;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shares the person's chosen theme with every Blade view, so the root layout
 * can render `<html class="dark">` on the server instead of flashing light
 * first and switching once the JavaScript runs.
 */
class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        View::share('appearance', Appearance::fromCookie($request->cookie(Appearance::COOKIE)));

        return $next($request);
    }
}
