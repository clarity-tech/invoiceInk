{{--
    Shared document template for invoices and estimates (public + PDF).

    Expected variables:
        $document       - Invoice model instance
        $isInvoice      - bool (true = invoice, false = estimate)
        $isPdf          - bool (true = PDF render, false = public web view)
--}}
@php
    $organization = $document->organization;
    $logoUrl = $isPdf ? $organization->logo_base64 : $organization->logo_url;
    $orgLocation = $document->organizationLocation;
    $custLocation = $document->customerLocation;
    $isGstInvoice = $document->isGstInvoice();
    $taxColumnHeader = $document->tax_type ?: __('documents.table.tax_rate');

    // Set the invoice relation on each item to avoid lazy-loading through OrganizationScope
    foreach ($document->items as $item) {
        $item->setRelation('invoice', $document);
    }

    if ($isInvoice) {
        $headerTitle = $isGstInvoice ? __('documents.headers.tax_invoice_upper') : __('documents.headers.invoice_upper');
        $balanceLabel = __('documents.financial.balance_due');
        $footerMessage = __('messages.footer.computer_generated_invoice');
        $pdfRoute = route('invoices.pdf', $document->ulid);
        $printLabel = __('actions.buttons.print_invoice');
    } else {
        $headerTitle = __('documents.headers.estimate_upper');
        $balanceLabel = __('documents.financial.estimated_total');
        $footerMessage = __('messages.footer.computer_generated_estimate');
        $pdfRoute = route('estimates.pdf', $document->ulid);
        $printLabel = __('actions.buttons.print_estimate');
    }

    // Payment info (invoices only)
    $showPaymentInfo = $isInvoice && $document->amount_paid > 0;
    $remainingBalance = $isInvoice ? $document->remaining_balance : $document->total;
    $paymentTerms = $document->paymentTermsLabel();
    // Show Ship To only when a separate shipping location is set (different from billing).
    $hasShipping = $document->hasShippingAddress();
    $shipLocation = $document->customerShippingLocation;

    // Organization contact info
    $orgEmails = $organization->emails;
    $orgPhone = $organization->formatted_phone;

    // Display amounts as-is, including the currency symbol (renders fine in the PDF).
    $amt = fn(string $formatted): string => $formatted;
    // Line-item amounts are shown without the currency symbol (kept on totals only).
    $amtPlain = fn(string $formatted): string => ltrim(preg_replace('/[^\d,\.]+/', '', $formatted));
    $formattedBalance = $amt($isInvoice ? $document->formatted_remaining_balance : $document->formatted_total);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $headerTitle }} {{ $document->invoice_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..700;1,300..700&display=swap" rel="stylesheet">
    <style>
        /* ── Reset & Base ─────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        @page {
            size: A4;
            margin: 1mm 5mm;
        }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .document-card { box-shadow: none !important; border-radius: 0 !important; }
        }

        body {
            font-family: 'Open Sans', 'Segoe UI', system-ui, sans-serif;
            font-size: 13px;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
            background: {{ $isPdf ? '#fff' : '#f3f4f6' }};
        }

        /* ── Utility ──────────────────────────────────────── */
        .nums { font-variant-numeric: tabular-nums; color: #000000; }
        .text-inv-gray { color: #333333; }
        .text-inv-label { color: #333319; }
        .text-brand-theme { color: #7A5A9E; }
        .bg-brand-theme { background-color: #7A5A9E; }

        /* ── Page Wrapper ─────────────────────────────────── */
        .page-wrapper {
            max-width: 820px;
            margin: 0 auto;
            {{ $isPdf ? '' : 'padding: 32px 16px;' }}
        }

        /* ── Action Bar ───────────────────────────────────── */
        .action-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
        }
        .action-bar a,
        .action-bar button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: background 0.15s, box-shadow 0.15s;
        }
        .btn-secondary {
            background: #fff;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .btn-secondary:hover { background: #f9fafb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .btn-primary {
            background: #6d28d9;
            color: #fff;
        }
        .btn-primary:hover { background: #5b21b6; }

        /* ── Document Card ────────────────────────────────── */
        .document-card {
            background: #fff;
            {{ $isPdf ? 'padding: 0;' : 'border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 6px 16px rgba(0,0,0,0.04); padding: 48px;' }}
        }

        /* ── Header ───────────────────────────────────────── */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 14px;
        }
        .doc-header .company-logo {
            max-height: 52px;
            max-width: 180px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .doc-header .company-name {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .doc-header .company-meta {
            font-size: 12px;
            line-height: 1.3;
        }
        .doc-header .company-meta .tax_id {
            font-weight: 500;
            margin-top: 2px;
        }

        .doc-title-block { text-align: right; flex-shrink: 0; padding-left: 16px; }
        .doc-title {
            font-size: 20px;
            font-weight: 500;
            letter-spacing: 0.01em;
            text-transform: uppercase;
        }
        .doc-number {
            font-size: 12px;
            font-weight: 700;
            margin-top: 2px;
        }

        /* Status pill */
        .status-pill {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            margin-top: 8px;
        }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-partially_paid { background: #fef3c7; color: #92400e; }
        .status-draft { background: #f3f4f6; color: #6b7280; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-accepted { background: #d1fae5; color: #065f46; }
        .status-void { background: #fee2e2; color: #991b1b; }

        /* ── Bill To + Details ────────────────────────────── */
        .meta-row {
            display: flex;
            gap: 32px;
            padding: 14px 0;
        }
        .meta-col { flex: 1; }

        .section-label {
            font-size: 10px;
            color: #333319;
        }
        .customer-name {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .address-text {
            font-size: 12px;
            line-height: 1.3;
        }
        .address-text .tax_id {
            font-weight: 500;
            margin-top: 2px;
        }

        .detail-table {
            margin-left: auto;
            border-collapse: collapse;
        }
        .detail-table td {
            padding: 3px 0;
            font-size: 12px;
            vertical-align: top;
        }
        .detail-table .dt-label {
            color: #333319;
            padding-right: 60px;
            white-space: nowrap;
        }
        .detail-table .dt-label::after { content: ' :'; }
        .detail-table .dt-value {
            font-weight: 500;
        }

        /* ── Items Table ──────────────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 10px;
        }
        .items-table thead th {
            padding: 6px 10px;
            font-size: 10px;
            font-weight: 500;
            color: #fff;
            border-top: none;
            border-bottom: none;
        }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .items-table tbody td {
            padding: 7px 10px;
            font-size: 13px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }
        .items-table tbody tr:nth-child(even) td {
            background: #fafafa;
        }
        .items-table .item-sac {
            font-size: 11px;
            margin-top: 2px;
        }
        .items-table .tax-detail {
            font-size: 11px;
        }
        .items-table .col-num { width: 35px; text-align: center; }
        .items-table .col-qty { width: 50px; text-align: center; }
        .items-table .col-rate { width: 110px; text-align: right; }
        .items-table .col-tax { width: 90px; text-align: right; }
        .items-table .col-amount { width: 110px; text-align: right; }

        /* ── Totals ───────────────────────────────────────── */
        .totals-wrapper {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 12px;
        }
        .totals-box { width: 335px; display: flex; flex-direction: column; gap: 10px; }
        .totals-row {
            display: flex;
            justify-content: space-between;
            gap: 50px;
            font-size: 12px;
            padding: 0 10px;
        }
        .totals-row .t-label { color: #333319; text-align: right; flex: 1; }
        .totals-row .t-value { text-align: right; white-space: nowrap; width: 120px; flex-shrink: 0; }
        .totals-row.total-line {
        }
        .totals-row.total-line .t-label,
        .totals-row.total-line .t-value {
            font-weight: 700;
        }
        .totals-row.payment-row .t-label { color: #059669; }
        .totals-row.payment-row .t-value { color: #059669; }

        .balance-due-box {
            padding: 5px 10px;
            background: #f3f4f6;
            border-radius: 2px;
        }
        .balance-due-box .t-label,
        .balance-due-box .t-value {
            font-weight: 700;
        }

        .words-row {
            margin-top: 6px;
            font-size: 12px;
            color: #6b7280;
        }
        .words-row .words-value {
            font-style: italic;
            font-weight: 600;
            color: #333319;
        }

        /* ── Notes / Terms / Bank ─────────────────────────── */
        .info-section {
            margin-bottom: 10px;
        }
        .info-section .info-title {
            font-size: 11px;
            margin-bottom: 4px;
        }
        .info-section .info-body {
            font-size: 12px;
            white-space: pre-wrap;
            line-height: 1.3;
        }

        /* ── Footer ───────────────────────────────────────── */
        .doc-footer {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }
        .doc-footer .footer-logo {
            height: 56px;
            opacity: 0.22;
            margin-bottom: 6px;
        }
        .doc-footer .footer-text {
            font-size: 11px;
        }
        .doc-footer .powered-by {
            margin-top: 12px;
            color: #9ca3af;
        }
        .doc-footer .powered-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .doc-footer .powered-brand {
            color: #7A5A9E;
            font-weight: 700;
            text-decoration: none;
        }
@if(! $isPdf)
        /* ── Mobile (web view only) ───────────────────────── */
        .items-scroll { width: 100%; }
        @media screen and (max-width: 640px) {
            .page-wrapper { padding: 16px 12px; }
            .document-card { padding: 22px 18px; }

            /* Stack the logo/title header */
            .doc-header { flex-direction: column; }
            .doc-title-block { text-align: left; padding-left: 0; margin-top: 14px; }

            /* Stack Bill To / details */
            .meta-row { flex-direction: column; gap: 18px; }
            .detail-table { margin-left: 0; width: 100%; }
            .detail-table .dt-label { padding-right: 16px; }

            /* Let the items table scroll horizontally instead of overflowing the page */
            .items-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .items-table { min-width: 460px; }

            /* Totals span the full width */
            .totals-wrapper { justify-content: stretch; }
            .totals-box { width: 100%; }
        }
@endif
    </style>
</head>
<body class="text-inv-gray">
    <div class="page-wrapper">
        @if(!$isPdf)
            {{-- Action Bar --}}
            <div class="action-bar no-print">
                <button onclick="window.print()" class="btn-secondary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    {{ $printLabel }}
                </button>
                <a href="{{ $pdfRoute }}" class="btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    {{ __('actions.buttons.download_pdf') }}
                </a>
            </div>
        @endif

        <div class="document-card">
            {{-- A. Header ──────────────────────────────────── --}}
            <div class="doc-header">
                <div>
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $organization->name }}" class="company-logo">
                    @endif
                    <div class="company-name">{{ $organization->company_name ?? $organization->name }}</div>
                    <div class="company-meta">
                        {{ $orgLocation->address_line_1 }}@if($orgLocation->address_line_2), {{ $orgLocation->address_line_2 }}@endif<br>
                        {{ $orgLocation->city }} {{ $orgLocation->state }} {{ $orgLocation->postal_code }}<br>
                        {{ $orgLocation->countryName() }}
                        @if($orgLocation->tax_id)
                            <div class="tax_id">{{ $orgLocation->taxIdLabel() }} {{ $orgLocation->tax_id }}</div>
                        @endif
                        @if($orgPhone && !$isPdf)
                            <div>{{ $orgPhone }}</div>
                        @endif
                    </div>
                </div>

                <div class="doc-title-block">
                    <div class="doc-title text-brand-theme">{{ $headerTitle }}</div>
                    <div class="doc-number"># {{ $document->invoice_number }}</div>

                    @if($isInvoice && !$isPdf)
                        <span class="status-pill status-{{ $document->status->value }}">{{ $document->status->label() }}</span>
                    @endif

                    <div style="margin-top: 12px;">
                        <div style="font-size: 11px; font-weight: 700; color: #374151; letter-spacing: 0.04em;">{{ $balanceLabel }}</div>
                        <div style="font-weight: 700;" class="nums">{{ $formattedBalance }}</div>
                    </div>
                </div>
            </div>

            {{-- B. Bill To + Details ───────────────────────── --}}
            <div class="meta-row">
                <div class="meta-col">
                    <div class="section-label">{{ __('documents.headers.bill_to') }}</div>
                    <div class="customer-name">{{ $document->customer?->name ?? $custLocation->locatable?->name ?? '' }}</div>
                    <div class="address-text">
                        {{ $custLocation->address_line_1 }}<br>
                        @if($custLocation->address_line_2)
                            {{ $custLocation->address_line_2 }}<br>
                        @endif
                        {{ $custLocation->city }}<br>
                        {{ $custLocation->postal_code }} {{ $custLocation->state }}<br>
                        {{ $custLocation->countryName() }}
                        @if($custLocation->tax_id)
                            <div class="tax_id">{{ $custLocation->taxIdLabel() }} {{ $custLocation->tax_id }}</div>
                        @endif
                        @if($custLocation->isIndian() && $custLocation->state)
                            <br>
                            <div>{{ __('documents.fields.place_of_supply') }}: {{ $custLocation->state }}@if($custLocation->tax_id) ({{ substr($custLocation->tax_id, 0, 2) }})@endif</div>
                        @endif
                    </div>
                </div>

                <div class="meta-col ">
                    <table class="detail-table">
                        @if($document->issued_at)
                            <tr>
                                <td class="dt-label text-right">
                                    {{ __('documents.fields.issue_date') }}
                                </td>
                                <td class="dt-value nums text-right">
                                    {{ $document->issued_at->format('d/m/Y') }}
                                </td>
                            </tr>
                        @endif
                        @if($paymentTerms)
                            <tr>
                                <td class="dt-label text-right">
                                    {{ __('documents.fields.terms') }}
                                </td>
                                <td class="dt-value text-right">{{ $paymentTerms }}</td>
                            </tr>
                        @endif
                        @if($document->due_at)
                            <tr>
                                <td class="dt-label text-right">
                                    {{ __('documents.fields.due_date') }}
                                </td>
                                <td class="dt-value nums text-right">
                                    {{ $document->due_at->format('d/m/Y') }}
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            {{-- B1. Subject Line --}}
            @if($document->subject)
                <div style="font-size: 13px;">
                    <div>Subject :</div>
                    <div>{{ $document->subject }}</div>
                </div>
            @endif

            {{-- B2. Ship To (only when different from Bill To) ────── --}}
            @if($hasShipping)
                <div class="meta-row" style="margin-top: 12px; padding-top: 0;">
                    <div class="meta-col">
                        <div class="section-label">Ship To</div>
                        <div class="customer-name">{{ $shipLocation->name ?? '' }}</div>
                        <div class="address-text">
                            {{ $shipLocation->address_line_1 }}<br>
                            @if($shipLocation->address_line_2)
                                {{ $shipLocation->address_line_2 }}<br>
                            @endif
                            {{ $shipLocation->city }}<br>
                            {{ $shipLocation->postal_code }} {{ $shipLocation->state }}<br>
                            {{ $shipLocation->country }}
                        </div>
                    </div>
                </div>
            @endif

            {{-- C. Line Items ──────────────────────────────── --}}
            <div class="items-scroll">
            <table class="items-table" style="margin-top: 15px;">
                <thead class="bg-brand-theme">
                    <tr>
                        <th class="text-center" style="width:36px">{{ __('documents.table.row_number') }}</th>
                        <th class="text-left">{{ __('documents.table.item_and_description') }}</th>
                        <th class="text-center">{{ __('documents.table.qty') }}</th>
                        <th class="text-right">{{ __('documents.table.rate') }}</th>
                        <th class="text-right">{{ $taxColumnHeader }}</th>
                        <th class="text-right">{{ __('documents.table.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($document->items as $index => $item)
                        <tr>
                            <td class="col-num">{{ $index + 1 }}</td>
                            <td>
                                <div>{{ $item->description }}</div>
                                @if($item->sac_code)
                                    <div class="item-sac">{{ __('documents.fields.sac') }}: {{ $item->sac_code }}</div>
                                @endif
                            </td>
                            <td class="col-qty nums">{{ number_format($item->quantity, 2) }}</td>
                            <td class="col-rate nums">{{ $amtPlain($item->formatted_unit_price) }}</td>
                            <td class="col-tax nums">
                                {{ $amtPlain($item->formatted_tax_amount) }}
                                @if($item->tax_rate)
                                    <div class="tax-detail">{{ $item->formatted_tax_rate }}</div>
                                @endif
                            </td>
                            <td class="col-amount nums">{{ $amtPlain($item->formatted_pre_tax_line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>

            {{-- D. Totals ──────────────────────────────────── --}}
            <div class="totals-wrapper">
                <div class="totals-box">
                    <div class="totals-row">
                        <span class="t-label">{{ __('documents.financial.subtotal') }}</span>
                        <span class="t-value nums">{{ $amt($document->formatted_subtotal) }}</span>
                    </div>

                    @if(!empty($document->tax_breakdown) && is_array($document->tax_breakdown))
                        @foreach($document->tax_breakdown as $taxName => $taxAmount)
                            @if(is_numeric($taxAmount))
                                <div class="totals-row">
                                    <span class="t-label">{{ $taxName }}</span>
                                    <span class="t-value nums">{{ $amt($document->formatMoney($taxAmount)) }}</span>
                                </div>
                            @endif
                        @endforeach
                    @else
                        @if($document->tax > 0)
                            <div class="totals-row">
                                <span class="t-label">{{ $document->tax_type ?: __('documents.financial.tax') }}</span>
                                <span class="t-value nums">{{ $amt($document->formatted_tax) }}</span>
                            </div>
                        @endif
                    @endif

                    <div class="totals-row total-line">
                        <span class="t-label">{{ __('documents.financial.total') }}</span>
                        <span class="t-value nums">{{ $amt($document->formatted_total) }}</span>
                    </div>

                    @if($showPaymentInfo)
                        <div class="totals-row payment-row">
                            <span class="t-label">Amount Paid</span>
                            <span class="t-value nums">&minus; {{ $amt($document->formatted_amount_paid) }}</span>
                        </div>
                    @endif

                    <div class="totals-row balance-due-box">
                        <span class="t-label">{{ $balanceLabel }}</span>
                        <span class="t-value nums">{{ $formattedBalance }}</span>
                    </div>

                    <div class="words-row">
                        {{ __('documents.financial.total_in_words') }}:
                        <span class="words-value">{{ $document->currency->amountToWords($document->total) }}</span>
                    </div>
                </div>
            </div>

            {{-- E. Notes ───────────────────────────────────── --}}
            @if($document->notes)
                <div class="info-section">
                    <div class="info-title">{{ __('documents.fields.notes') }}</div>
                    <div class="info-body">{{ $document->notes }}</div>
                </div>
            @endif

            {{-- F. Bank Details (invoices only) ────────────── --}}
            @if($isInvoice && $organization->hasBankDetails())
                @php $bank = $organization->bank_details; @endphp
                <div style="margin-bottom: 10px; font-size: 12px; line-height: 1.3;">
                    <div style="font-weight: 600;">{{ $bank->accountName ?: ($organization->company_name ?? $organization->name) }}</div>
                    @if($bank->accountNumber)
                        <div>Account No: {{ $bank->accountNumber }}</div>
                    @endif
                    @if($bank->bankName)
                        <div>{{ $bank->bankName }}</div>
                    @endif
                    @if($bank->ifsc || $bank->branch)
                        <div>{{ $bank->formattedBranchLine() }}</div>
                    @endif
                    @if($bank->swift)
                        <div>SWIFT Code {{ $bank->swift }}</div>
                    @endif
                    @if($bank->pan)
                        <div>PAN {{ $bank->pan }}</div>
                    @endif
                </div>
            @endif

            {{-- G. Terms ───────────────────────────────────── --}}
            @if($document->terms)
                <div class="info-section">
                    <div class="info-title text-inv-label">
                        {{ __('documents.fields.terms_and_conditions') }}
                    </div>
                    <div class="info-body text-inv-gray" style="white-space: pre-line;">{{ trim($document->terms) }}</div>
                </div>
            @endif

            {{-- H. Footer ──────────────────────────────────── --}}
            <div class="doc-footer">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $organization->name }}" class="footer-logo"><br>
                @endif

                <div class="powered-by">
                    <span class="powered-label">{{ __('messages.email.powered_by') }}</span><br>
                    <a href="{{ config('app.url') }}" class="powered-brand">{{ config('app.name', 'InvoiceInk') }}</a>
                    &mdash; Smart Invoicing for Modern Businesses
                </div>
            </div>
        </div>
    </div>
</body>
</html>
