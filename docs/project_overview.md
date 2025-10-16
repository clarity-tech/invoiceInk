
# Project Documentation

## 1. Project Overview

This project is a modern invoicing application built on the Laravel framework. It leverages Livewire and Jetstream to provide a reactive, single-page application experience for managing invoices, estimates, customers, and organizations. The application is designed to be multi-tenant, with data scoped by the user's current organization (team).

## 2. Core Technologies & Dependencies

### Backend

*   **PHP:** ^8.4
*   **Laravel:** ^12.0
*   **Livewire:** ^3.0
*   **Jetstream:** ^5.3 (with Teams)
*   **Akaunting/laravel-money:** ^6.0 (for currency handling)
*   **Laravel Sanctum:** ^4.0 (for API authentication)

### Frontend

*   **Vite:** for asset bundling
*   **Tailwind CSS:** ^3.4.0
*   **Alpine.js:** for JavaScript interactivity
*   **Axios:** for HTTP requests

### Development Dependencies

*   **Pest:** for testing
*   **Laravel Dusk:** for browser testing
*   **Laravel Telescope:** for debugging

## 3. Application Architecture

The application follows a standard Laravel structure, with some key components and conventions.

### 3.1. Models

The core models of the application are:

*   **`User`**: Extends the default Laravel User model and uses Jetstream's `HasTeams` trait.
*   **`Organization`**: Extends Jetstream's `Team` model. This represents a user's company or organization.
*   **`Customer`**: Represents a customer of an organization.
*   **`Invoice`**: Represents both invoices and estimates, differentiated by a `type` enum. It uses a ULID for the public-facing ID.
*   **`InvoiceItem`**: Represents a line item on an invoice or estimate.
*   **`Location`**: A polymorphic model used to store addresses for both `Organization` and `Customer` models.

A global `OrganizationScope` is applied to models like `Customer` and `Invoice` to ensure that users can only access data belonging to their currently active organization.

### 3.2. Livewire Components

The application's user interface is primarily built with Livewire components. The main components are:

*   **`OrganizationManager`**: Handles creating, reading, updating, and deleting organizations.
*   **`CustomerManager`**: Handles CRUD operations for customers.
*   **`InvoiceWizard`**: A multi-step form for creating and editing invoices and estimates.
*   **`NumberingSeriesManager`**: A new component that allows users to manage their invoice numbering series.

These components encapsulate the UI and logic for their respective domains, providing a reactive user experience.

### 3.3. Services

The application uses service classes to encapsulate business logic:

*   **`InvoiceCalculator`**: Calculates the subtotal, tax, and total for an invoice based on its line items.
*   **`EstimateToInvoiceConverter`**: Converts an `Invoice` of type 'estimate' into a new `Invoice` of type 'invoice'.
*   **`PdfService`**: Generates PDF documents for invoices and estimates using an external Chrome-based service.
*   **`InvoiceNumberingService`**: A new service that handles the generation of invoice numbers based on customizable numbering series. This allows for flexible invoice number formats, prefixes, and reset frequencies.

### 3.4. Controllers

*   **`PublicViewController`**: Handles the public-facing routes for viewing invoices and estimates without authentication.

## 4. Database Schema

The database schema is defined in the `database/migrations` directory. The key tables are:

*   **`users`**: Standard Laravel users table.
*   **`teams`**: Stores organizations, extending the Jetstream teams table with additional business-related fields.
*   **`customers`**: Stores customer data, with a foreign key to the `teams` table.
*   **`invoices`**: Stores invoice and estimate data, linked to an organization and a customer.
*   **`invoice_items`**: Stores line items for an invoice.
*   **`invoice_numbering_series`**: A new table to store customizable invoice numbering series.
*   **`locations`**: Stores address information for organizations and customers.

## 5. Key Features

*   **Multi-Organization Support**: Users can belong to multiple organizations and switch between them.
*   **Customer Management**: Users can create, edit, and delete customers.
*   **Invoice and Estimate Management**: Users can create, edit, and delete invoices and estimates.
*   **PDF Generation**: Invoices and estimates can be downloaded as PDF files.
*   **Public Viewing**: Invoices and estimates have public URLs that can be shared with clients.
*   **Currency Support**: Invoices can be created in different currencies.

## 6. Routing

Routes are defined in `routes/web.php` and `routes/api.php`.

*   **Web Routes (`routes/web.php`)**:
    *   Public routes for viewing invoices and estimates (`/invoices/{ulid}`, `/estimates/{ulid}`).
    *   Authenticated routes are grouped under the `auth:sanctum` middleware.
    *   The main application routes (`/dashboard`, `/organizations`, `/customers`, `/invoices`) are handled by Livewire components.
*   **API Routes (`routes/api.php`)**:
    *   A single `/user` route is defined for retrieving the authenticated user's information.

## 7. Frontend

The frontend is built using Blade templates, Tailwind CSS, and Alpine.js.

*   **Views**: Blade templates are located in `resources/views`.
*   **Layouts**: The main application layout is `resources/views/layouts/app.blade.php`, and the guest layout is `resources/views/layouts/guest.blade.php`.
*   **Livewire Views**: The views for the Livewire components are located in `resources/views/livewire`.
*   **Styling**: Tailwind CSS is used for styling, with the configuration file at `tailwind.config.js`.
*   **JavaScript**: Application-specific JavaScript is in `resources/js/app.js`.
