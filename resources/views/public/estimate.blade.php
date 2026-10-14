@include('partials.document-template', [
    'document' => $estimate,
    'isInvoice' => false,
    'isPdf' => false,
    'pdfUrl' => $pdfUrl,
    'linkExpiresAt' => $linkExpiresAt,
])
