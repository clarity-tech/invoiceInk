![CLARITY Logo](.github/clarity-logo.png)
# Clarity Invoicing Application

A modern Laravel-based invoicing system with organization-centric architecture, multi-currency support, and comprehensive document management.

## Getting Started

**Installation via Laravel Sail:**

1. Clone the repository and install dependencies:
```shell
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

2. Start the application:
```bash
sail up -d
sail artisan migrate:fresh --seed
```


## Database Management with pgweb

The project includes [pgweb](https://github.com/sosedoff/pgweb), a web-based PostgreSQL database browser. 

**Accessing pgweb:**
1. Ensure services are running: `sail up -d`
2. Open pgweb interface at: http://localhost:8081

**Features:**
- View and query database tables
- Run SQL commands
- Export/import data
- View table schemas

**Configuration:**
- Default port: 8081 (customize via `FORWARD_PGWEB_PORT` in `.env`)
- Automatically connects to the PostgreSQL service using credentials from `.env`

The interface will be available after starting the Docker containers.

## Introduction
The Clarity Invoicing Application is a comprehensive Laravel-based invoicing system designed with organization-centric architecture. It provides robust invoice and estimate management with multi-currency support, tax templates, and PDF generation capabilities.

## Features  
- **Organization-Centric Architecture**: Unified organization model replacing team/company separation
- **Multi-Currency Support**: Support for AED, USD, EUR, GBP, INR with proper currency symbols
- **Tax Templates**: Flexible tax system supporting multiple countries (UAE, India, etc.)
- **Invoice & Estimate Management**: Complete document lifecycle with status tracking
- **PDF Generation**: High-quality PDF generation using Spatie Browsershot
- **Public Document Sharing**: ULID-based public URLs for invoices and estimates
- **Livewire Components**: Modern reactive UI components for seamless user experience
- **Comprehensive Testing**: 94.7% test coverage with Unit, Feature, and Browser tests
- **Docker Development**: Pre-configured Laravel Sail for containerized development  

## Development Standards  
- **Coding Standards:** Follow PSR-12 and Laravel coding conventions
- **Testing:** Maintain 90%+ test coverage with comprehensive Unit, Feature, and Browser tests
- **Architecture:** Follow Domain-Driven Design principles with Value Objects and Service Layer patterns
- **Git Workflow:** Use conventional commits with atomic changes and linear history
- **Code Quality:** Run `sail pint --dirty` before all commits to maintain code formatting

## Technology Stack
- **Backend:** Laravel 11.19.3 with PHP 8.4.8
- **Database:** PostgreSQL with comprehensive migrations
- **Frontend:** Livewire 3.6.3 + luvi-ui/laravel-luvi (shadcn for Livewire)
- **Testing:** Pest framework with Laravel Dusk for browser testing
- **PDF Generation:** Spatie Browsershot with headless Chrome
- **Containerization:** Laravel Sail with Docker Compose

## Architecture Overview
- **Organization Model**: Unified business entity management (replaces Team/Company)
- **Customer Management**: Customer entities with polymorphic location relationships
- **Invoice System**: Unified invoice/estimate model with flexible tax handling
- **Location System**: Polymorphic location model serving organizations and customers
- **Value Objects**: EmailCollection, InvoiceTotals for robust data handling
- **Service Layer**: InvoiceCalculator, PdfService, EstimateToInvoiceConverter

## License  
This application is intellectual property of CLARITY Technologies.
