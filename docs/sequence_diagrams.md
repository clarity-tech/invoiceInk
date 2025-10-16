
# Sequence Diagrams

This document contains sequence diagrams that illustrate the backend interactions for specific application flows.

## 1. Saving an Invoice

This diagram shows the sequence of events when a user saves an invoice using the `InvoiceWizard` Livewire component.

```mermaid
sequenceDiagram
    participant Browser
    participant InvoiceWizard as Livewire Component
    participant InvoiceCalculator as Service
    participant InvoiceNumberingService as Service
    participant Database

    Browser->>+InvoiceWizard: User clicks 'Save'
    InvoiceWizard->>InvoiceWizard: Validate input data
    alt Editing an existing invoice
        InvoiceWizard->>Database: Find Invoice by ID
        InvoiceWizard->>Database: Update Invoice record
        InvoiceWizard->>Database: Delete existing InvoiceItems
    else Creating a new invoice
        InvoiceWizard->>+InvoiceNumberingService: generateInvoiceNumber(...)
        InvoiceNumberingService->>Database: Find or create numbering series
        InvoiceNumberingService->>InvoiceNumberingService: Format invoice number
        InvoiceNumberingService->>Database: Increment and save series
        InvoiceNumberingService-->>-InvoiceWizard: Return invoice number and series ID
        InvoiceWizard->>Database: Create new Invoice record
    end
    loop For each item
        InvoiceWizard->>Database: Create InvoiceItem record
    end
    InvoiceWizard->>+InvoiceCalculator: updateInvoiceTotals(invoice)
    InvoiceCalculator->>Database: Load invoice items
    InvoiceCalculator->>InvoiceCalculator: Calculate subtotal, tax, and total
    InvoiceCalculator-->>-InvoiceWizard: Return updated totals
    InvoiceWizard->>Database: Update invoice with new totals
    InvoiceWizard-->>-Browser: Redirect to invoice list
```

## 2. Public PDF Download

This diagram illustrates the process of a non-authenticated user downloading a PDF of an invoice.

```mermaid
sequenceDiagram
    participant User
    participant Browser
    participant PublicViewController
    participant PdfService
    participant ChromeService as External PDF Service
    participant Database

    User->>+Browser: Clicks on public PDF link
    Browser->>+PublicViewController: GET /invoices/{ulid}/pdf
    PublicViewController->>+Database: Find Invoice by ULID (without global scopes)
    Database-->>-PublicViewController: Return Invoice data
    PublicViewController->>+PdfService: downloadInvoicePdf(invoice)
    PdfService->>PdfService: Generate PDF from Blade view
    PdfService->>+ChromeService: POST /generate-pdf with HTML content
    ChromeService-->>-PdfService: Return PDF content
    PdfService-->>-PublicViewController: Return PDF content
    PublicViewController-->>-Browser: Return HTTP response with PDF
    Browser-->>-User: Download PDF file
```
