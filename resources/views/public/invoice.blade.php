@include('partials.document-template', [
    'document' => $invoice,
    'isInvoice' => true,
    'isPdf' => false,
    'pdfUrl' => $pdfUrl,
    'linkExpiresAt' => $linkExpiresAt,
])
