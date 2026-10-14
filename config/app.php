<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'InvoiceInk'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Cloud Run Service Name
    |--------------------------------------------------------------------------
    |
    | Cloud Run injects K_SERVICE into every service container (see the
    | container contract). It is absent everywhere else, so it is how the
    | application tells a real deployment — where APP_URL is the authoritative
    | public origin — from a local or CI run that legitimately serves itself on
    | some other origin. Read it here rather than via env() in application
    | code, because production caches its config at container start.
    |
    | Cloud Run *Jobs* set CLOUD_RUN_JOB instead and are not covered: they run
    | as console commands, which already root their URLs at APP_URL.
    |
    */

    'cloud_run_service' => env('K_SERVICE'),

    /*
    |--------------------------------------------------------------------------
    | Share Link Lifetime
    |--------------------------------------------------------------------------
    |
    | How many days a link to an invoice or estimate keeps working for someone
    | who is not signed in. Every link is signed and carries its expiry, so a
    | bare ULID opens nothing. Each email mints a new link, reminders included,
    | so a customer who is still being chased always holds a working one.
    |
    */

    'share_link_days' => (int) env('SHARE_LINK_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Email Send Defaults
    |--------------------------------------------------------------------------
    |
    | Default state for the optional toggles in the "send document" email
    | composer (the EmailModal). These seed the checkboxes when the modal is
    | opened; the sender can still override them per email. Shared to the
    | frontend via HandleInertiaRequests and used as the server-side fallback
    | in InvoiceController::email().
    |
    */

    'invoice_email' => [

        // Attach the rendered PDF of the invoice/estimate to the email.
        'attach_pdf' => (bool) env('INVOICE_EMAIL_ATTACH_PDF', true),

        // Include the "View Invoice/Estimate Online" CTA button linking to the
        // public document page.
        'include_view_online_button' => (bool) env('INVOICE_EMAIL_VIEW_ONLINE_BUTTON', true),

    ],

];
