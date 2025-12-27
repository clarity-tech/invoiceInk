<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ ucfirst($invoice->type) }} {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .email-body {
            white-space: pre-wrap;
            word-wrap: break-word;
            color: #1f2937;
            font-size: 14px;
            line-height: 1.8;
        }
        /* Constrain logo images */
        .email-body img {
            max-height: 100px;
            max-width: 200px;
            height: auto;
            width: 150px;
        }
        /* Style company name */
        .email-body > div:first-child strong {
            font-size: 24px;
            font-weight: 300;
            letter-spacing: 3px;
        }
        /* Style invoice number banner */
        .email-body > div:nth-child(2) {
            background-color: #7c3aed;
            color: white;
            padding: 20px;
            text-align: center;
            margin: 10px 0 20px 0;
        }
        .email-body > div:nth-child(2) strong {
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
        .cta-button {
            display: inline-block;
            background-color: #7c3aed;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-weight: 600;
            margin: 25px 0;
            text-align: center;
        }
        .cta-button:hover {
            background-color: #6d28d9;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 12px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-body">{!! $customBody !!}</div>

        <div style="text-align: center;">
            <a href="{{ $viewUrl }}" class="cta-button">
                View {{ ucfirst($invoice->type) }} Online
            </a>
        </div>

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
    </div>
</body>
</html>
