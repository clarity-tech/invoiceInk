<?php

use App\Mcp\Servers\InvoicingServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| MCP Routes
|--------------------------------------------------------------------------
|
| This file is loaded automatically by Laravel MCP's service provider; it
| must not be registered in bootstrap/app.php.
|
*/

// Off by default, so merging and deploying this does not expose a new public
// endpoint until someone decides to serve it. Safe as a runtime condition
// because route:cache runs at container start, not build time — see the note in
// config/mcp.php.
//
// Authentication is not optional here. Tools read organization-scoped data, and
// they identify the organization from the authenticated user's current team.
// Only OAuth access tokens (guard "api", Passport) are accepted. Clients follow
// the 401's resource_metadata pointer to the discovery documents oauthRoutes()
// registers, register themselves, and send the user through the consent
// screen. Without these routes they fail with "Couldn't reach the MCP server".
if (config('mcp.web_enabled')) {
    Mcp::oauthRoutes();

    Mcp::web('/mcp', InvoicingServer::class)
        ->middleware(['auth:api', 'throttle:60,1']);
}

// The stdio transport carries no token, so it names its acting user through
// config('mcp.local_user_email') instead. That is a development convenience and
// is never registered in production, where the token-authenticated web
// transport above is the only supported way in.
if (! app()->isProduction()) {
    Mcp::local('invoicing', InvoicingServer::class);
}
