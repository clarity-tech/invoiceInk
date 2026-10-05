<?php

namespace App\Mcp\Concerns;

use App\Http\Controllers\AiConnectionController;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

/**
 * Resolves the single organization an MCP tool is allowed to read.
 *
 * Every tool must call `actingOrganization()` and scope its queries with the
 * result. Do not rely on the global `OrganizationScope` alone: it returns early
 * without applying any filter when `auth()->check()` is false, which is exactly
 * the case under the stdio transport. Resolution fails closed — every path that
 * cannot name an organization returns an error `Response` rather than allowing
 * an unscoped query.
 */
trait ResolvesActingOrganization
{
    /**
     * Resolve the organization whose data the request may read.
     *
     * Returns a `Response` on failure so callers can return it directly.
     */
    protected function actingOrganization(Request $request): Organization|Response
    {
        $user = $this->actingUser($request);

        if ($user instanceof Response) {
            return $user;
        }

        $organization = $user->currentTeam;

        if (! $organization instanceof Organization) {
            return Response::error(
                'The authenticated user has no current organization selected, so there is no data to '
                .'read. Select an organization in the web app first.'
            );
        }

        return $organization;
    }

    /**
     * Resolve the user acting on this request.
     *
     * Returns a `Response` on failure so callers can return it directly.
     */
    protected function actingUser(Request $request): User|Response
    {
        $user = $this->resolveActingUser($request);

        if (! $user instanceof User) {
            return Response::error(
                'Not authenticated, so no invoicing data can be read. Connect over HTTP with an API '
                .'token ("Authorization: Bearer <token>"), or set MCP_LOCAL_USER_EMAIL when using the '
                .'local stdio server.'
            );
        }

        // A token authenticated this request, so it must be one minted for MCP.
        // Without this, any Sanctum token the user holds for an unrelated
        // purpose would unlock their whole invoicing history over MCP.
        // currentAccessToken() is null on the stdio path, which carries no
        // token at all and is gated by config instead.
        $token = $user->currentAccessToken();

        if ($token !== null && ! $user->tokenCan(AiConnectionController::ABILITY)) {
            return Response::error(
                'This API token is not authorised for AI access. Create a connection under '
                .'Settings > Connect AI tools and use the token it gives you.'
            );
        }

        return $user;
    }

    /**
     * Locate the acting user, or null when none can be trusted.
     */
    private function resolveActingUser(Request $request): ?User
    {
        $user = $request->user();

        if ($user instanceof User) {
            return $user;
        }

        // Only the stdio transport reaches this point: it has no HTTP request to
        // carry a token, so the acting user must be named explicitly. Production
        // is excluded outright — there the web transport and its token are the
        // only supported way in.
        if (app()->isProduction()) {
            return null;
        }

        $email = config('mcp.local_user_email');

        if (blank($email)) {
            return null;
        }

        $user = User::where('email', $email)->first();

        if ($user instanceof User) {
            // Authenticate for the remainder of the call so OrganizationScope
            // engages too, sitting behind each tool's own explicit scoping.
            Auth::setUser($user);
        }

        return $user;
    }
}
