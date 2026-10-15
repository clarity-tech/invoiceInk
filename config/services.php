<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        // Dedicated SES credentials so mail delivery does not collide with the
        // AWS_* env vars used by the S3/RustFS object storage disk. Falls back
        // to the shared AWS_* values when SES-specific ones are not provided.
        'key' => env('SES_KEY', env('AWS_ACCESS_KEY_ID')),
        'secret' => env('SES_SECRET', env('AWS_SECRET_ACCESS_KEY')),
        'region' => env('SES_REGION', env('AWS_DEFAULT_REGION', 'ap-south-1')),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gotenberg' => [
        'enabled' => env('GOTENBERG_ENABLED', true),
        'url' => env('GOTENBERG_URL', 'http://gotenberg:3000'),
        // Each document is rendered twice (page count, then footer placement), so
        // this must clear two Chromium renders. It also has to stay above
        // Gotenberg's own --api-timeout and below Octane's max_execution_time.
        'timeout' => env('GOTENBERG_TIMEOUT', 90),
    ],

    'frankfurter' => [
        'url' => env('FRANKFURTER_URL', 'https://api.frankfurter.dev/v2'),
        'timeout' => env('FRANKFURTER_TIMEOUT', 10),
    ],

    // Drafting an invoice from a document with Claude. The key itself is
    // ANTHROPIC_API_KEY, read by config/ai.php. The request runs while the user
    // waits, so the timeout must stay under the web service's 120s.
    'invoice_drafts' => [
        'model' => env('INVOICE_DRAFT_MODEL', 'claude-haiku-5-5'),
        'timeout' => (int) env('INVOICE_DRAFT_TIMEOUT', 60),
        'per_hour' => (int) env('INVOICE_DRAFT_PER_HOUR', 20),
    ],

    // Writing emails with AI, from the send and reminder dialogs.
    'email_drafts' => [
        'model' => env('EMAIL_DRAFT_MODEL', 'claude-haiku-5-5'),
        'timeout' => (int) env('EMAIL_DRAFT_TIMEOUT', 30),
        'per_hour' => (int) env('EMAIL_DRAFT_PER_HOUR', 60),
    ],

];
