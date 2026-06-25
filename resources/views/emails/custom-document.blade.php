<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ucfirst($invoice->type) }} {{ $invoice->invoice_number }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f4f4f4;
        }

        .email-wrapper-cell {
            padding: 20px;
        }

        .email-container {
            width: 600px;
            max-width: 600px;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        /* Slim platform header — kept muted so the sender's business leads */
        .email-header {
            padding: 22px 30px 0 30px;
        }

        .email-header .brand-mark {
            width: 26px;
            height: 26px;
            vertical-align: middle;
            border: 0;
        }

        .email-header .brand-name {
            vertical-align: middle;
            color: #1f2937;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.01em;
            padding-left: 5px;
        }

        .email-header .brand-slogan {
            margin: 6px 0 0 0;
            color: #9ca3af;
            font-size: 11px;
            letter-spacing: 0.04em;
        }

        .email-content {
            padding: 28px 30px 30px 30px;
        }

        .email-body {
            word-wrap: break-word;
            color: #1f2937;
            font-size: 14px;
            line-height: 1.8;
        }

        .email-body> :first-child {
            margin-top: 0;
        }

        /* Constrain logo images */
        .email-body img {
            max-height: 100px;
            max-width: 200px;
            height: auto;
            width: 150px;
        }

        /* Style company name */
        .email-body>div:first-child strong {
            font-size: 24px;
            font-weight: 300;
            letter-spacing: 3px;
        }

        /* Style invoice number banner */
        .email-body>div:nth-child(2) {
            background-color: #7c3aed;
            color: white;
            padding: 20px;
            text-align: center;
            margin: 10px 0 20px 0;
        }

        .email-body>div:nth-child(2) strong {
            font-size: 18px;
            font-weight: 400;
        }

        /* Style blockquote as invoice amount box */
        .email-body blockquote {
            background-color: #fef9e7;
            border: 1px solid #f4e5c2 !important;
            border-left: 4px solid #f4e5c2;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }

        .email-body blockquote div:first-child {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .email-body blockquote div:last-child strong em {
            font-style: normal;
            font-size: 28px;
            display: block;
            margin-top: 5px;
        }

        .cta-wrapper {
            text-align: center;
        }

        .cta-button {
            display: inline-block;
            background-color: #7c3aed;
            color: #ffffff;
            text-decoration: none;
            padding: 14px 34px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 15px;
            letter-spacing: 0.01em;
            margin: 25px 0;
            text-align: center;
        }

        .cta-button:hover {
            background-color: #6d28d9;
        }

        .footer {
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 12px;
            text-align: center;
        }

        /* Platform branding lockup — sits on the gray background below the card */
        .platform-footer {
            padding: 28px 30px 6px 30px;
            text-align: center;
        }

        .platform-footer .powered-by {
            margin: 0 0 8px 0;
            color: #9ca3af;
            font-size: 10px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .footer-brand {
            text-decoration: none;
            display: inline-block;
        }

        .footer-brand .brand-mark {
            width: 22px;
            height: 22px;
            vertical-align: middle;
            border: 0;
        }

        .footer-brand .brand-name {
            vertical-align: middle;
            color: #1f2937;
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            padding-left: 5px;
        }

        .platform-footer .brand-tagline {
            margin: 7px 0 0 0;
            color: #9ca3af;
            font-size: 11px;
        }

        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
            }

            .email-content {
                padding: 20px !important;
            }
        }
    </style>
</head>

<body style="margin: 0; padding: 0; background-color: #f4f4f4;">
    <span style="display: none !important; visibility: hidden; opacity: 0; color: transparent; height: 0; width: 0; max-height: 0; max-width: 0; overflow: hidden; mso-hide: all;">{{ config('branding.slogan') }}</span>
    <table role="presentation" class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f4f4f4">
        <tr>
            <td class="email-wrapper-cell" align="center">
                <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" align="center">
                    <tr>
                        <td class="email-header">
                            <img src="{{ 'cid:'.config('branding.logo_cid') }}" alt="{{ config('app.name') }}" width="149" height="34" style="display: block; border: 0;">
                            <p class="brand-slogan">{{ config('branding.slogan') }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-content">
                            <div class="email-body">{!! $customBody !!}</div>

                            @if($includeViewOnlineButton)
                            <div class="cta-wrapper">
                                <a href="{{ $viewUrl }}" class="cta-button">
                                    View {{ ucfirst($invoice->type) }} Online
                                </a>
                            </div>
                            @endif

                            <div class="footer">
                                @if($invoice->organization)
                                <p style="margin: 0 0 10px 0; font-weight: 600; color: #1f2937;">
                                    {{ $invoice->organization->company_name ?? $invoice->organization->name }}
                                </p>
                                @if($invoice->organizationLocation)
                                <p style="color: #9ca3af; margin: 0;">
                                    {{ $invoice->organizationLocation->address_line_1 }}<br>
                                    {{ $invoice->organizationLocation->city }}, {{ $invoice->organizationLocation->state }} {{ $invoice->organizationLocation->postal_code }}<br>
                                    {{ $invoice->organizationLocation->country }}
                                </p>
                                @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
                <div class="platform-footer">
                    <p class="powered-by">{{ __('messages.email.powered_by') }}</p>
                    <a href="{{ config('app.url') }}" class="footer-brand">
                        <img src="{{ 'cid:'.config('branding.logo_cid') }}" alt="{{ config('app.name') }}" width="114" height="26" style="display: inline-block; border: 0;">
                        </a>
                    <p class="brand-tagline">{{ config('branding.tagline') }}</p>
                </div>
            </td>
        </tr>
    </table>
</body>

</html>