@include('partials.document-template', [
    'document' => $invoice,
    'isInvoice' => true,
    'isPdf' => true,
])
