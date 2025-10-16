# Entity-Relationship Diagram

This document contains a text-based Entity-Relationship (ER) diagram for the application's database schema. You can render this into a visual diagram by pasting the code into a Mermaid.js-compatible viewer (e.g., GitHub's markdown renderer, [Mermaid Live Editor](https://mermaid.live)).

## Mermaid Diagram

```mermaid
erDiagram
    USER {
        int id PK
        string name
        string email
        timestamp email_verified_at
        string password
        string remember_token
        int current_team_id
        string profile_photo_path
    }

    TEAM_USER {
        int id PK
        int team_id
        int user_id
        string role
    }

    ORGANIZATION {
        int id PK
        int user_id
        string name
        boolean personal_team
        string company_name
        string tax_number
        string registration_number
        string emails
        string phone
        string website
        string currency
        string notes
        int primary_location_id
        string custom_domain
        string country_code
        string financial_year_type
        int financial_year_start_month
        int financial_year_start_day
    }

    CUSTOMER {
        int id PK
        int organization_id
        string name
        string phone
        string emails
        int primary_location_id
    }

    LOCATION {
        int id PK
        string locatable_type
        int locatable_id
        string name
        string gstin
        string address_line_1
        string address_line_2
        string city
        string state
        string country
        string postal_code
    }

    INVOICE {
        int id PK
        string type
        string ulid
        int organization_id
        int organization_location_id
        int customer_id
        int customer_location_id
        string invoice_number
        string status
        timestamp issued_at
        timestamp due_at
        string currency
        bigint exchange_rate
        bigint subtotal
        bigint tax
        bigint total
        string tax_type
        string tax_breakdown
        string email_recipients
        string notes
        string terms
    }

    INVOICE_ITEM {
        int id PK
        int invoice_id
        string description
        int quantity
        bigint unit_price
        int tax_rate
    }

    TAX_TEMPLATE {
        int id PK
        int organization_id
        string name
        string type
        int rate
        string category
        string country_code
        string description
        boolean is_active
    }

    INVOICE_NUMBERING_SERIES {
        int id PK
        int organization_id
        int location_id
        string name
        string prefix
        string format_pattern
        int current_number
        string reset_frequency
        boolean is_active
        boolean is_default
        timestamp last_reset_at
    }

    USER ||--o{ ORGANIZATION : owns
    USER }|--|{ TEAM_USER : is_member
    ORGANIZATION }|--|{ TEAM_USER : has_member
    ORGANIZATION ||--|{ CUSTOMER : manages
    ORGANIZATION ||--|{ INVOICE : issues
    CUSTOMER ||--|{ INVOICE : receives
    INVOICE ||--|{ INVOICE_ITEM : contains
    ORGANIZATION }o--|| LOCATION : has_primary
    CUSTOMER }o--|| LOCATION : has_primary
    INVOICE }o..o| LOCATION : billed_from
    INVOICE }o..o| LOCATION : billed_to
    ORGANIZATION ||--|{ INVOICE_NUMBERING_SERIES : has
    INVOICE }o..o| INVOICE_NUMBERING_SERIES : uses
    ORGANIZATION ||--|{ TAX_TEMPLATE : has
```