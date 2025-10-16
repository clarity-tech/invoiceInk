# Flow Diagrams

This document contains flow diagrams that illustrate the key user journeys in the application.

## 1. Invoice/Estimate Creation Flow

This diagram shows the steps a user takes to create a new invoice or estimate using the `InvoiceWizard` Livewire component.

```mermaid
graph TD
    A[Start] --> B(Clicks 'Create Invoice');
    B --> C(Invoice Wizard Appears);
    C --> D{Step 1: Basic Details};
    D --> E(Selects Organization);
    E --> F(Selects Customer);
    F --> G(Selects Locations);
    G --> H(Selects Numbering Series);
    H --> I{Clicks 'Next Step'};
    I --> J{Step 2: Add Items};
    J --> K(Adds Line Items);
    K --> L(Enters Quantity and Price);
    L --> M{Totals are auto-calculated};
    M --> N{Clicks 'Next Step'};
    N --> O{Step 3: Review & Save};
    O --> P(Reviews all details);
    P --> Q{Clicks 'Save'};
    Q --> R(Invoice is created);
    R --> S(Redirected to Invoice List);
    S --> T[End];
```

## 2. User Authentication Flow

```mermaid
graph TD
    subgraph Registration
        A[Start] --> B(User visits site);
        B --> C{Has account?};
        C -- No --> D(Clicks 'Register');
        D --> E(Fills out registration form);
        E --> F{Submits form};
        F --> G(User is created);
        G --> H(Redirected to Dashboard);
        H --> I[End];
    end

    subgraph Login
        C -- Yes --> J(Clicks 'Login');
        J --> K(Enters credentials);
        K --> L{Submits form};
        L --> M{Credentials valid?};
        M -- Yes --> N(Redirected to Dashboard);
        N --> O[End];
        M -- No --> P(Shows error message);
        P --> K;
    end
```

## 3. Organization Management Flow

```mermaid
graph TD
    A[Start] --> B(User navigates to Organizations page);
    B --> C{Clicks 'Create Organization'};
    C --> D(Fills out organization details);
    D --> E(Fills out primary location details);
    E --> F{Submits form};
    F --> G(Organization and Location are created);
    G --> H(Redirected to Organizations list);
    H --> I{Clicks 'Edit' on an organization};
    I --> J(Updates details);
    J --> K{Submits form};
    K --> L(Organization is updated);
    L --> H;
    H --> M{Clicks 'Delete' on an organization};
    M --> N(Organization is deleted);
    N --> H;
    H --> Z[End];
```

## 4. Customer Management Flow

```mermaid
graph TD
    A[Start] --> B(User navigates to Customers page);
    B --> C{Clicks 'Create Customer'};
    C --> D(Fills out customer details);
    D --> E(Fills out primary location details);
    E --> F{Submits form};
    F --> G(Customer and Location are created);
    G --> H(Redirected to Customers list);
    H --> I{Clicks 'Edit' on a customer};
    I --> J(Updates details);
    J --> K{Submits form};
    K --> L(Customer is updated);
    L --> H;
    H --> M{Clicks 'Delete' on a customer};
    M --> N(Customer is deleted);
    N --> H;
    H --> Z[End];
```

## 5. Numbering Series Management Flow

```mermaid
graph TD
    A[Start] --> B(User navigates to Numbering Series page);
    B --> C{Clicks 'Create Series'};
    C --> D(Fills out series details);
    D --> E(Sets prefix, format, and reset frequency);
    E --> F{Submits form};
    F --> G(Numbering series is created);
    G --> H(Redirected to Series list);
    H --> I{Clicks 'Edit' on a series};
    I --> J(Updates details);
    J --> K{Submits form};
    K --> L(Series is updated);
    L --> H;
    H --> M{Clicks 'Delete' on a series};
    M --> N(Series is deleted);
    N --> H;
    H --> Z[End];
```

## 6. Estimate to Invoice Conversion Flow

```mermaid
graph TD
    A[Start] --> B(User is viewing an Estimate);
    B --> C{Clicks 'Convert to Invoice'};
    C --> D(System creates a new Invoice from the Estimate data);
    D --> E(A new invoice number is generated);
    E --> F(User is redirected to the new Invoice's edit page);
    F --> G[End];
```