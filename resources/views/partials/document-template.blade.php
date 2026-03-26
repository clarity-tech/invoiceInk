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
    $isGstInvoice = $isInvoice && !empty($orgLocation?->gstin);
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

    // Derive payment terms from date difference
    $paymentTerms = null;
    if ($document->issued_at && $document->due_at) {
        $daysDiff = (int) $document->issued_at->diffInDays($document->due_at);
        $paymentTerms = match($daysDiff) {
            0 => 'Due on Receipt',
            7 => 'Net 7',
            15 => 'Net 15',
            30 => 'Net 30',
            45 => 'Net 45',
            60 => 'Net 60',
            90 => 'Net 90',
            default => "Due in {$daysDiff} days",
        };
    }

    // Shipping location (if different from billing)
    $shipLocation = $document->customerShippingLocation;
    $hasShipping = $shipLocation && $shipLocation->id !== $custLocation?->id;

    // Organization contact info
    $orgEmails = $organization->emails;
    $orgPhone = $organization->phone;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $headerTitle }} {{ $document->invoice_number }}</title>
    <style>
        /* ── Reset & Base ─────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        @page {
            size: A4;
            margin: 10mm;
        }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .document-card { box-shadow: none !important; border-radius: 0 !important; }
        }

        body {
            font-family: 'Figtree', 'Inter', 'Segoe UI', system-ui, sans-serif;
            font-size: 13px;
            line-height: 1.55;
            color: #111827;
            -webkit-font-smoothing: antialiased;
            background: {{ $isPdf ? '#fff' : '#f3f4f6' }};
        }

        /* ── Utility ──────────────────────────────────────── */
        .nums { font-variant-numeric: tabular-nums; }

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
            padding-bottom: 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        .doc-header .company-logo {
            max-height: 52px;
            max-width: 180px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .doc-header .company-name {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 2px;
        }
        .doc-header .company-meta {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.6;
        }
        .doc-header .company-meta .gstin {
            color: #374151;
            font-weight: 500;
            margin-top: 2px;
        }

        .doc-title-block { text-align: right; }
        .doc-title {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #111827;
            text-transform: uppercase;
        }
        .doc-number {
            font-size: 13px;
            color: #6b7280;
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

        .amount-highlight {
            margin-top: 16px;
            padding: 12px 16px;
            background: #faf5ff;
            border-radius: 8px;
            text-align: right;
        }
        .amount-highlight .label {
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .amount-highlight .value {
            font-size: 22px;
            font-weight: 800;
            color: #6d28d9;
            letter-spacing: -0.01em;
        }

        /* ── Bill To + Details ────────────────────────────── */
        .meta-row {
            display: flex;
            gap: 32px;
            padding: 24px 0;
        }
        .meta-col { flex: 1; }
        .meta-col.right-col { text-align: right; }

        .section-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #9ca3af;
            margin-bottom: 8px;
        }
        .customer-name {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }
        .address-text {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.65;
        }
        .address-text .gstin {
            color: #374151;
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
            color: #6b7280;
            font-weight: 600;
            padding-right: 16px;
            text-align: right;
            white-space: nowrap;
        }
        .detail-table .dt-value {
            color: #111827;
            text-align: right;
            font-weight: 500;
        }

        /* ── Items Table ──────────────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 24px;
        }
        .items-table thead th {
            padding: 10px 12px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #374151;
            background: #f8f6fc;
            border-top: 2px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
        }
        .items-table thead th.text-left { text-align: left; }
        .items-table thead th.text-center { text-align: center; }
        .items-table thead th.text-right { text-align: right; }

        .items-table tbody td {
            padding: 10px 12px;
            font-size: 13px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }
        .items-table tbody tr:nth-child(even) td {
            background: #fafafa;
        }
        .items-table .item-desc {
            color: #111827;
            font-weight: 500;
        }
        .items-table .item-sac {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 2px;
        }
        .items-table .tax-detail {
            font-size: 11px;
            color: #9ca3af;
        }
        .items-table .col-num { width: 36px; text-align: center; color: #9ca3af; }
        .items-table .col-qty { width: 60px; text-align: center; }
        .items-table .col-rate { width: 110px; text-align: right; }
        .items-table .col-tax { width: 110px; text-align: right; }
        .items-table .col-amount { width: 110px; text-align: right; font-weight: 600; }

        /* ── Totals ───────────────────────────────────────── */
        .totals-wrapper {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 24px;
        }
        .totals-box { width: 280px; }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
        }
        .totals-row .t-label { color: #6b7280; font-weight: 500; }
        .totals-row .t-value { font-weight: 600; color: #111827; }
        .totals-row.total-line {
            border-top: 2px solid #e5e7eb;
            margin-top: 4px;
            padding-top: 8px;
        }
        .totals-row.total-line .t-label,
        .totals-row.total-line .t-value {
            font-weight: 700;
            font-size: 14px;
            color: #111827;
        }
        .totals-row.payment-row .t-label { color: #059669; }
        .totals-row.payment-row .t-value { color: #059669; }

        .balance-due-box {
            margin-top: 8px;
            padding: 10px 14px;
            background: #faf5ff;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .balance-due-box .t-label {
            font-weight: 700;
            font-size: 13px;
            color: #6d28d9;
        }
        .balance-due-box .t-value {
            font-weight: 800;
            font-size: 18px;
            color: #6d28d9;
        }

        .words-row {
            margin-top: 10px;
            font-size: 12px;
            color: #6b7280;
        }
        .words-row .words-value {
            font-style: italic;
            font-weight: 600;
            color: #374151;
        }

        /* ── Notes / Terms / Bank ─────────────────────────── */
        .info-section {
            margin-bottom: 20px;
        }
        .info-section .info-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6d28d9;
            margin-bottom: 4px;
        }
        .info-section .info-title.neutral {
            color: #374151;
        }
        .info-section .info-body {
            font-size: 12px;
            color: #4b5563;
            line-height: 1.65;
            white-space: pre-wrap;
        }

        .bank-box {
            padding: 14px 16px;
            background: #f9fafb;
            border-left: 3px solid #6d28d9;
            border-radius: 0 6px 6px 0;
            margin-bottom: 20px;
        }
        .bank-box .bank-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #374151;
            margin-bottom: 6px;
        }
        .bank-box p {
            font-size: 12px;
            color: #4b5563;
            line-height: 1.6;
            margin: 0;
        }
        .bank-box .bank-name-line {
            font-weight: 600;
            color: #111827;
        }

        /* ── Footer ───────────────────────────────────────── */
        .doc-footer {
            margin-top: 40px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }
        .doc-footer .footer-logo {
            height: 28px;
            opacity: 0.3;
            margin-bottom: 6px;
        }
        .doc-footer .footer-text {
            font-size: 11px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
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
                        {{ $orgLocation->country }}
                        @if($orgLocation->gstin)
                            <div class="gstin">{{ __('documents.fields.gstin') }} {{ $orgLocation->gstin }}</div>
                        @endif
                        @if($orgEmails && $orgEmails->count() > 0)
                            <div style="margin-top: 4px;">{{ $orgEmails->getEmails()[0] ?? '' }}</div>
                        @endif
                        @if($orgPhone)
                            <div>{{ $orgPhone }}</div>
                        @endif
                    </div>
                </div>

                <div class="doc-title-block">
                    <div class="doc-title">{{ $headerTitle }}</div>
                    <div class="doc-number"># {{ $document->invoice_number }}</div>

                    @if($isInvoice)
                        <span class="status-pill status-{{ $document->status->value }}">{{ $document->status->label() }}</span>
                    @endif

                    <div class="amount-highlight">
                        <div class="label">{{ $balanceLabel }}</div>
                        <div class="value nums">{{ $isInvoice ? $document->formatted_remaining_balance : $document->formatted_total }}</div>
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
                        {{ $custLocation->country }}
                        @if($custLocation->gstin)
                            <div class="gstin">{{ __('documents.fields.gstin') }} {{ $custLocation->gstin }}</div>
                        @endif
                        @if($custLocation->state)
                            <div>{{ __('documents.fields.place_of_supply') }}: {{ $custLocation->state }}@if($custLocation->gstin) ({{ substr($custLocation->gstin, 0, 2) }})@endif</div>
                        @endif
                    </div>
                </div>

                <div class="meta-col right-col">
                    <table class="detail-table">
                        @if($document->issued_at)
                            <tr>
                                <td class="dt-label">{{ __('documents.fields.issue_date') }}</td>
                                <td class="dt-value nums">{{ $document->issued_at->format('d M Y') }}</td>
                            </tr>
                        @endif
                        @if($document->due_at)
                            <tr>
                                <td class="dt-label">{{ __('documents.fields.due_date') }}</td>
                                <td class="dt-value nums">{{ $document->due_at->format('d M Y') }}</td>
                            </tr>
                        @endif
                        @if($paymentTerms)
                            <tr>
                                <td class="dt-label">Terms</td>
                                <td class="dt-value">{{ $paymentTerms }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            {{-- B2. Ship To (if different from Bill To) ────── --}}
            @if($hasShipping)
                <div class="meta-row" style="margin-top: -8px; padding-top: 0;">
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
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width:36px">{{ __('documents.table.row_number') }}</th>
                        <th class="text-left">{{ __('documents.table.item_and_description') }}</th>
                        <th class="text-center" style="width:60px">{{ __('documents.table.qty') }}</th>
                        <th class="text-right" style="width:110px">{{ __('documents.table.rate') }}</th>
                        <th class="text-right" style="width:110px">{{ $taxColumnHeader }}</th>
                        <th class="text-right" style="width:110px">{{ __('documents.table.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($document->items as $index => $item)
                        <tr>
                            <td class="col-num">{{ $index + 1 }}</td>
                            <td>
                                <div class="item-desc">{{ $item->description }}</div>
                                @if($item->sac_code)
                                    <div class="item-sac">{{ __('documents.fields.sac') }} {{ $item->sac_code }}</div>
                                @endif
                            </td>
                            <td class="col-qty nums">{{ number_format($item->quantity, 2) }}</td>
                            <td class="col-rate nums">{{ $item->formatted_unit_price }}</td>
                            <td class="col-tax nums">
                                {{ $item->formatted_tax_amount }}
                                @if($item->tax_rate)
                                    <div class="tax-detail">{{ $item->formatted_tax_rate }}</div>
                                @endif
                            </td>
                            <td class="col-amount nums">{{ $item->formatted_pre_tax_line_total }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- D. Totals ──────────────────────────────────── --}}
            <div class="totals-wrapper">
                <div class="totals-box">
                    <div class="totals-row">
                        <span class="t-label">{{ __('documents.financial.subtotal') }}</span>
                        <span class="t-value nums">{{ $document->formatted_subtotal }}</span>
                    </div>

                    @if(!empty($document->tax_breakdown) && is_array($document->tax_breakdown))
                        @foreach($document->tax_breakdown as $taxName => $taxAmount)
                            @if(is_numeric($taxAmount))
                                <div class="totals-row">
                                    <span class="t-label">{{ $taxName }}</span>
                                    <span class="t-value nums">{{ $document->formatMoney($taxAmount) }}</span>
                                </div>
                            @endif
                        @endforeach
                    @else
                        @if($document->tax > 0)
                            <div class="totals-row">
                                <span class="t-label">{{ $document->tax_type ?: __('documents.financial.tax') }}</span>
                                <span class="t-value nums">{{ $document->formatted_tax }}</span>
                            </div>
                        @endif
                    @endif

                    <div class="totals-row total-line">
                        <span class="t-label">{{ __('documents.financial.total') }}</span>
                        <span class="t-value nums">{{ $document->formatted_total }}</span>
                    </div>

                    @if($showPaymentInfo)
                        <div class="totals-row payment-row">
                            <span class="t-label">Amount Paid</span>
                            <span class="t-value nums">&minus; {{ $document->formatted_amount_paid }}</span>
                        </div>
                    @endif

                    <div class="balance-due-box">
                        <span class="t-label">{{ $balanceLabel }}</span>
                        <span class="t-value nums">{{ $isInvoice ? $document->formatted_remaining_balance : $document->formatted_total }}</span>
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
                <div class="bank-box">
                    <div class="bank-title">Bank Details</div>
                    <p class="bank-name-line">{{ $bank->accountName ?: ($organization->company_name ?? $organization->name) }}</p>
                    @if($bank->accountNumber)
                        <p>Account No: {{ $bank->accountNumber }}</p>
                    @endif
                    @if($bank->bankName)
                        <p>{{ $bank->bankName }}</p>
                    @endif
                    @if($bank->ifsc || $bank->branch)
                        <p>
                            @if($bank->ifsc)
                                IFSC {{ $bank->ifsc }}
                            @endif
                            @if($bank->ifsc && $bank->branch)
                                &middot;
                            @endif
                            @if($bank->branch)
                                {{ $bank->branch }} Branch
                            @endif
                        </p>
                    @endif
                    @if($bank->swift)
                        <p>SWIFT Code {{ $bank->swift }}</p>
                    @endif
                    @if($bank->pan)
                        <p>PAN {{ $bank->pan }}</p>
                    @endif
                </div>
            @endif

            {{-- G. Terms ───────────────────────────────────── --}}
            @if($document->terms)
                <div class="info-section">
                    <div class="info-title neutral">{{ __('documents.fields.terms_and_conditions') }}</div>
                    <div class="info-body">{{ $document->terms }}</div>
                </div>
            @endif

            {{-- H. Footer ──────────────────────────────────── --}}
            <div class="doc-footer">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $organization->name }}" class="footer-logo"><br>
                @endif
                <span class="footer-text">{{ $footerMessage }}</span>
            </div>
        </div>
    </div>
</body>
</html>
