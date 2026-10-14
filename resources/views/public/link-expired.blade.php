{{--
    Shown for a share link that was genuinely issued but has run out (see
    ValidateShareLink). A forged or bare link never reaches this page, so naming
    the sender here tells nobody anything they were not already sent.
--}}
@php
    $organization = $document->organization;
    $senderName = $organization->company_name ?? $organization->name;
    $senderEmail = $organization->emails?->first()['email'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="en" @class(['dark' => ($appearance ?? null) === \App\Enums\Appearance::DARK])>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    @include('partials.appearance')
    <title>{{ __('messages.share_link.expired_title') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* The same tokens as the public document, so an expired link looks like
           the page it used to open. */
        :root {
            --canvas: #f3f4f6;
            --card: #fff;
            --foreground: #111827;
            --muted-foreground: #6b7280;
            --border: #e5e7eb;
            --brand: #7A5A9E;
        }

        .dark {
            color-scheme: dark;

            --canvas: oklch(13% 0.028 261.692);
            --card: oklch(21% 0.034 264.665);
            --foreground: oklch(96.7% 0.003 264.542);
            --muted-foreground: oklch(70.7% 0.022 261.325);
            --border: oklch(27.8% 0.033 256.848);
            --brand: oklch(78% 0.09 305);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background: var(--canvas);
            color: var(--foreground);
            font-family: 'Open Sans', 'Segoe UI', system-ui, sans-serif;
            line-height: 1.6;
        }

        main {
            width: 100%;
            max-width: 440px;
            padding: 32px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        h1 {
            margin: 0 0 12px;
            font-size: 20px;
            font-weight: 600;
        }

        p {
            margin: 0 0 12px;
            color: var(--muted-foreground);
            font-size: 15px;
        }

        p:last-child { margin-bottom: 0; }

        a {
            color: var(--brand);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <main>
        <h1>{{ __('messages.share_link.expired_title') }}</h1>
        <p>{{ __('messages.share_link.expired_body', [
            'document' => strtolower($document->typeLabel()),
            'number' => $document->invoice_number,
            'organization' => $senderName,
        ]) }}</p>
        <p>{{ __('messages.share_link.expired_help', ['organization' => $senderName]) }}</p>
        @if($senderEmail)
            <p><a href="mailto:{{ $senderEmail }}">{{ __('messages.share_link.expired_contact', ['email' => $senderEmail]) }}</a></p>
        @endif
    </main>
</body>
</html>
