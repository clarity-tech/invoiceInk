<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Estimate {{ $document->invoice_number }}</title>
</head>
<body>
    <h1>Estimate {{ $document->invoice_number }}</h1>
    
    <p>Dear {{ $recipientEmail }},</p>
    
    <p>Please find attached your estimate for {{ $document->invoice_number }}.</p>
    
    <p>Estimate Details:</p>
    <ul>
        <li>Estimate Number: {{ $document->invoice_number }}</li>
        <li>Status: {{ ucfirst($document->status) }}</li>
        <li>Total: {{ $document->formatted_total }}</li>
        @if($document->due_at)
            <li>Valid Until: {{ $document->due_at->format('F j, Y') }}</li>
        @endif
    </ul>
    
    <p>You can view this estimate online at: <a href="{{ route('estimates.public', $document->ulid) }}">{{ route('estimates.public', $document->ulid) }}</a></p>
    
    <p>Thank you for considering our services!</p>
</body>
</html>