
# Data Model Examples

This document provides several examples of how the application's data models relate to each other in common scenarios. These diagrams use Mermaid's class diagram syntax to represent instances of the models (objects) and their relationships.

## 1. Basic Organization Setup

This example shows a user named "John Doe" who owns an organization called "Clarity Inc.". The organization has a primary location in New York.

```mermaid
classDiagram
    class User {
        +id: 1
        +name: "John Doe"
        +email: "john.doe@example.com"
    }

    class Organization {
        +id: 101
        +name: "Clarity Inc."
        +user_id: 1
        +primary_location_id: 201
    }

    class Location {
        +id: 201
        +name: "Main Office"
        +address_line_1: "123 Main St"
        +city: "New York"
        +locatable_id: 101
        +locatable_type: "Organization"
    }

    User "1" -- "1" Organization : owns
    Organization "1" -- "1" Location : has primary
```

## 2. Organization with a Customer

Building on the previous example, "Clarity Inc." now has a customer, "Global Tech", which has its own location in Boston.

```mermaid
classDiagram
    class Organization {
        +id: 101
        +name: "Clarity Inc."
    }

    class Customer {
        +id: 501
        +name: "Global Tech"
        +organization_id: 101
        +primary_location_id: 202
    }

    class Location {
        +id: 202
        +name: "Boston Office"
        +address_line_1: "456 Tech Ave"
        +city: "Boston"
        +locatable_id: 501
        +locatable_type: "Customer"
    }

    Organization "1" -- "1" Customer : manages
    Customer "1" -- "1" Location : has primary
```

## 3. A Complete Invoice

This example shows a complete invoice created by "Clarity Inc." for "Global Tech". The invoice uses a specific numbering series and has two line items.

```mermaid
classDiagram
    class Invoice {
        +id: 1001
        +invoice_number: "INV-2025-07-123"
        +organization_id: 101
        +customer_id: 501
        +invoice_numbering_series_id: 301
        +total: 165000
    }

    class InvoiceItem {
        +id: 2001
        +invoice_id: 1001
        +description: "Web Development"
        +quantity: 10
        +unit_price: 15000
    }
    
    class InvoiceItem {
        +id: 2002
        +invoice_id: 1001
        +description: "Design Services"
        +quantity: 5
        +unit_price: 3000
    }

    class InvoiceNumberingSeries {
        +id: 301
        +name: "Default Invoice Series"
        +prefix: "INV"
        +organization_id: 101
    }

    class Organization {
        +id: 101
        +name: "Clarity Inc."
    }

    class Customer {
        +id: 501
        +name: "Global Tech"
    }

    Invoice "1" -- "*" InvoiceItem : contains
    Invoice "1" -- "1" Organization : issued by
    Invoice "1" -- "1" Customer : issued to
    Invoice "1" -- "1" InvoiceNumberingSeries : uses
```

## 4. Invoice with Tax

This example shows a more realistic invoice that includes tax calculations. It features an invoice item with an 18% tax rate, and the main invoice reflects the calculated tax and total amounts.

```mermaid
classDiagram
    class Invoice {
        +id: 1002
        +invoice_number: "INV-2025-07-124"
        +subtotal: 150000
        +tax: 27000
        +total: 177000
    }

    class InvoiceItem {
        +id: 2003
        +invoice_id: 1002
        +description: "Consulting Services"
        +quantity: 10
        +unit_price: 15000
        +tax_rate: 1800
    }

    class TaxTemplate {
        +id: 401
        +name: "GST 18%"
        +rate: 1800
        +type: "GST"
    }

    Invoice "1" -- "1" InvoiceItem : contains
    InvoiceItem -- "1" TaxTemplate : applies
```
