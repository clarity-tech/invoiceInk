<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $document->invoice_number }}</title>
</head>
<body>
    <h1>Invoice {{ $document->invoice_number }}</h1>
    
    <p>Dear {{ $recipientEmail }},</p>
    
    <p>Please find attached your invoice for {{ $document->invoice_number }}.</p>
    
    <p>Invoice Details:</p>
    <ul>
        <li>Invoice Number: {{ $document->invoice_number }}</li>
        <li>Status: {{ ucfirst($document->status) }}</li>
        <li>Total: ₹{{ number_format($document->total / 100, 2) }}</li>
        @if($document->due_at)
            <li>Due Date: {{ $document->due_at->format('F j, Y') }}</li>
        @endif
    </ul>
    
    <p>You can view this invoice online at: <a href="{{ route('invoices.public', $document->ulid) }}">{{ route('invoices.public', $document->ulid) }}</a></p>
    
    <p>Thank you for your business!</p>
</body>
</html>