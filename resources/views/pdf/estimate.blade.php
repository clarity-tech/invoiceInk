@include('partials.document-template', [
    'document' => $estimate,
    'isInvoice' => false,
    'isPdf' => true,
    'fillPages' => $fillPages ?? null,
])
