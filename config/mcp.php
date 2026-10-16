<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Redirect Domains
    |--------------------------------------------------------------------------
    |
    | These domains are the domains that OAuth clients are permitted to use
    | for redirect URIs. Each domain should be specified with its scheme
    | and host. Domains not in this list will raise validation errors.
    |
    | An "*" may be used to allow all domains.
    |
    */

    'redirect_domains' => [
        '*',
        // 'https://example.com',
        // 'http://localhost',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Custom Schemes
    |--------------------------------------------------------------------------
    |
    | Native desktop OAuth clients like Cursor and VS Code use private-use URI
    | schemes (RFC 8252) for redirect callbacks instead of standard schemes
    | like HTTPS. Here, you may list which custom schemes you will allow.
    |
    */

    'custom_schemes' => [
        // 'claude',
        // 'cursor',
        // 'vscode',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization Server
    |--------------------------------------------------------------------------
    |
    | Here you may configure the OAuth authorization server issuer identifier
    | per RFC 8414. This value appears in your protected resource and auth
    | server metadata endpoints. When null, this defaults to `url('/')`.
    |
    */

    'authorization_server' => null,

    /*
    |--------------------------------------------------------------------------
    | Web Transport
    |--------------------------------------------------------------------------
    |
    | Whether to register the HTTP MCP endpoint at POST /mcp. It is off by
    | default so the server can ship and deploy without exposing a new public
    | endpoint; turn it on per environment once you intend to serve it.
    |
    | Reading runtime config here is safe specifically because `route:cache`
    | runs at container start rather than build time, so the compiled route
    | table reflects the environment it will actually serve. Baking the cache
    | at build time would freeze this answer — the same trap documented for the
    | Cloud Tasks handler in docker/production/Dockerfile.frankenphp.
    |
    */

    'web_enabled' => (bool) env('MCP_WEB_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Local Server Acting User
    |--------------------------------------------------------------------------
    |
    | The local (stdio) MCP server has no HTTP request, so it cannot carry a
    | token to identify the caller. This email names the user the local
    | server acts as. Without it the server still starts, but every tool fails
    | closed rather than reading data it cannot attribute to an organization.
    |
    | This is ignored in production, where the token-authenticated web
    | transport is the only supported way in.
    |
    */

    'local_user_email' => env('MCP_LOCAL_USER_EMAIL'),

];
