<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('documents.headers.invoice') }} {{ $document->invoice_number }}</title>
</head>
<body>
    <h1>{{ __('documents.headers.invoice') }} {{ $document->invoice_number }}</h1>
    
    <p>{{ __('messages.email.greeting', ['email' => $recipientEmail]) }}</p>
    
    <p>{{ __('messages.email.invoice_message', ['number' => $document->invoice_number]) }}</p>
    
    <p>{{ __('messages.email.invoice_details') }}</p>
    <ul>
        <li>{{ __('documents.fields.invoice_number') }}: {{ $document->invoice_number }}</li>
        <li>{{ __('documents.fields.status') }}: {{ ucfirst($document->status) }}</li>
        <li>{{ __('documents.financial.total') }} {{ $document->formatted_total }}</li>
        @if($document->due_at)
            <li>{{ __('documents.fields.due_date') }}: {{ $document->due_at->format('F j, Y') }}</li>
        @endif
    </ul>
    
    <p>{{ __('messages.email.view_online_invoice') }}: <a href="{{ route('invoices.public', $document->ulid) }}">{{ route('invoices.public', $document->ulid) }}</a></p>
    
    <p>{{ __('messages.email.thank_you_business') }}</p>
</body>
</html>