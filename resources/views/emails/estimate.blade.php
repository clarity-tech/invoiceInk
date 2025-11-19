<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('documents.headers.estimate') }} {{ $document->invoice_number }}</title>
</head>
<body>
    <h1>{{ __('documents.headers.estimate') }} {{ $document->invoice_number }}</h1>
    
    <p>{{ __('messages.email.greeting', ['email' => $recipientEmail]) }}</p>
    
    <p>{{ __('messages.email.estimate_message', ['number' => $document->invoice_number]) }}</p>
    
    <p>{{ __('messages.email.estimate_details') }}</p>
    <ul>
        <li>{{ __('documents.fields.estimate_number') }}: {{ $document->invoice_number }}</li>
        <li>{{ __('documents.fields.status') }}: {{ ucfirst($document->status) }}</li>
        <li>{{ __('documents.financial.total') }} {{ $document->formatted_total }}</li>
        @if($document->due_at)
            <li>{{ __('documents.fields.due_date') }}: {{ $document->due_at->format('F j, Y') }}</li>
        @endif
    </ul>
    
    <p>{{ __('messages.email.view_online_estimate') }}: <a href="{{ route('estimates.public', $document->ulid) }}">{{ route('estimates.public', $document->ulid) }}</a></p>
    
    <p>{{ __('messages.email.thank_you_considering') }}</p>
</body>
</html>