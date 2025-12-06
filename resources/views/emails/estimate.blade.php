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
        .header {
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #1f2937;
            font-size: 24px;
        }
        .organization-name {
            color: #6b7280;
            font-size: 14px;
            margin-top: 5px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 25px;
            color: #4b5563;
        }
        .details-box {
            background-color: #f9fafb;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 20px 0;
        }
        .details-box h3 {
            margin: 0 0 15px 0;
            color: #1f2937;
            font-size: 16px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #6b7280;
        }
        .detail-value {
            color: #1f2937;
        }
        .total-row {
            font-size: 18px;
            font-weight: bold;
            color: #3b82f6;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
        }
        .cta-button {
            display: inline-block;
            background-color: #3b82f6;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-weight: 600;
            margin: 25px 0;
            text-align: center;
        }
        .cta-button:hover {
            background-color: #2563eb;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>{{ ucfirst($invoice->type) }} {{ $invoice->invoice_number }}</h1>
            @if($invoice->organization)
                <div class="organization-name">
                    {{ $invoice->organization->company_name ?? $invoice->organization->name }}
                </div>
            @endif
        </div>

        <div class="greeting">
            Dear Customer,
        </div>

        <div class="message">
            @if($invoice->type === 'estimate')
                <p>Thank you for considering our services! Please find your {{ $invoice->type }} details below.</p>
                @if($invoice->due_at)
                    <p><strong>This estimate is valid until {{ $invoice->due_at->format('F j, Y') }}</strong></p>
                @endif
            @else
                <p>Thank you for your business! Please find your {{ $invoice->type }} details below.</p>
                @if($invoice->due_at)
                    <p><strong>Payment is due by {{ $invoice->due_at->format('F j, Y') }}</strong></p>
                @endif
            @endif
        </div>

        <div class="details-box">
            <h3>{{ ucfirst($invoice->type) }} Details</h3>

            <div class="detail-row">
                <span class="detail-label">{{ ucfirst($invoice->type) }} Number:</span>
                <span class="detail-value">{{ $invoice->invoice_number }}</span>
            </div>

            @if($invoice->issued_at)
                <div class="detail-row">
                    <span class="detail-label">Issue Date:</span>
                    <span class="detail-value">{{ $invoice->issued_at->format('F j, Y') }}</span>
                </div>
            @endif

            @if($invoice->due_at)
                <div class="detail-row">
                    <span class="detail-label">{{ $invoice->type === 'estimate' ? 'Valid Until' : 'Due Date' }}:</span>
                    <span class="detail-value">{{ $invoice->due_at->format('F j, Y') }}</span>
                </div>
            @endif

            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value">{{ $invoice->status->label() }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Subtotal:</span>
                <span class="detail-value">{{ \Akaunting\Money\Money::{$invoice->currency}($invoice->subtotal)->format() }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Tax:</span>
                <span class="detail-value">{{ \Akaunting\Money\Money::{$invoice->currency}($invoice->tax)->format() }}</span>
            </div>

            <div class="detail-row total-row">
                <span class="detail-label">Total Amount:</span>
                <span class="detail-value">{{ \Akaunting\Money\Money::{$invoice->currency}($invoice->total)->format() }}</span>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="{{ $viewUrl }}" class="cta-button">
                View {{ ucfirst($invoice->type) }} Online
            </a>
        </div>

        @if($invoice->notes)
            <div class="message">
                <strong>Notes:</strong>
                <p>{{ $invoice->notes }}</p>
            </div>
        @endif

        <div class="footer">
            <p>{{ $invoice->type === 'estimate' ? 'Thank you for considering our services!' : 'Thank you for your business!' }}</p>
            @if($invoice->organization)
                <p>
                    {{ $invoice->organization->company_name ?? $invoice->organization->name }}
                </p>
                @if($invoice->organizationLocation)
                    <p style="color: #9ca3af; font-size: 12px;">
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
