![CLARITY Logo](.github/clarity-logo.png)
# InvoiceInk

A modern invoicing application built with Laravel 13, Inertia.js v3, Vue 3, and TypeScript. Features multi-currency support, organization-centric architecture, and comprehensive document management.

## Prerequisites

- Docker & Docker Compose
- Git

## Getting Started

### 1. Clone and install PHP dependencies

```bash
git clone <repository-url>
cd invoicing

docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php85-composer:latest \
    composer install --ignore-platform-reqs
```

### 2. Environment setup

```bash
cp .env.example .env
```

### 3. Start services and run setup

```bash
./vendor/bin/sail up -d
sail php artisan app:setup --seed
sail bun install
sail bun run build
```

The `app:setup` command handles everything: key generation, migrations, database seeding, S3 bucket creation on RustFS, and cache clearing.

The application will be available at http://localhost.

### 4. (Optional) Start Vite dev server for HMR

```bash
sail bun run dev
```

### 5. (Optional) Set up browser testing

```bash
sail composer browser-setup
```

Downloads Chromium for Pest Browser tests (~60s, one-time).

## Services

| Service        | URL                    | Description                    |
|----------------|------------------------|--------------------------------|
| Application    | http://localhost       | Main application               |
| Vite           | http://localhost:5173  | Vite dev server (HMR)          |
| Mailpit        | http://localhost:8025  | Email testing dashboard         |
| pgweb          | http://localhost:8081  | Web-based PostgreSQL browser    |
| RustFS Console | http://localhost:9001  | S3-compatible object storage    |

Ports can be customized via `.env` (e.g., `APP_PORT`, `FORWARD_PGWEB_PORT`, `FORWARD_RUSTFS_CONSOLE_PORT`).

## Running Tests

```bash
# Run all tests
sail php artisan test

# Run tests in parallel
sail php artisan test --parallel

# Run with coverage
sail php artisan test --coverage

# Run browser tests (Pest Browser + Playwright)
sail php artisan test tests/Browser

# Format code before committing
sail pint --dirty && sail bun run lint && sail bun run format
```

## Useful Commands

```bash
sail up -d                    # Start all services
sail down                     # Stop all services
sail composer dev             # Dev server with queue, logs, and vite
sail psql                     # Access PostgreSQL CLI
sail php artisan app:setup-storage  # Recreate S3 bucket (local dev only)
```

See `CLAUDE.md` for the full command reference (testing, formatting, database, frontend).

## Technology Stack

| Layer          | Technology                                                    |
|----------------|---------------------------------------------------------------|
| **Backend**    | Laravel 13, PHP 8.5, Laravel Octane (FrankenPHP)              |
| **Frontend**   | Vue 3 (Composition API), Inertia.js v3, TypeScript            |
| **Styling**    | Tailwind CSS v4, lucide-vue-next icons                        |
| **Routing**    | Laravel Wayfinder (type-safe frontend route generation)       |
| **Database**   | PostgreSQL 17                                                 |
| **Testing**    | Pest 4, Pest Browser (Playwright), 957+ tests, 83% coverage  |
| **PDF**        | Gotenberg (HTML-to-PDF via Docker sidecar)                    |
| **Storage**    | RustFS (local S3-compatible), S3/Spaces (production)          |
| **Cache/Queue**| Valkey (Redis-compatible)                                     |
| **Auth**       | Laravel Fortify (login, register, 2FA, email verification)    |
| **Email**      | Mailpit (development)                                         |
| **Container**  | Laravel Sail (dev), ServerSideUp FrankenPHP + Octane (prod)   |

## Project Structure

```
app/
  Http/Controllers/       # Inertia controllers (Dashboard, Invoice, Customer, etc.)
  Models/                 # Eloquent models (Organization, Invoice, Customer, etc.)
  Services/               # Business logic (InvoiceCalculator, PdfService, etc.)
  Enums/                  # PHP enums (Currency, Country, InvoiceStatus, etc.)
resources/
  ts/                     # TypeScript + Vue 3 frontend
    Pages/                # Inertia page components
    Components/           # Shared Vue components
    Layouts/              # App, Guest, NavigationMenu layouts
    composables/          # useFormatMoney, useInvoiceCalculator, useFlash
    types/                # TypeScript interfaces for all models
    routes/               # Wayfinder-generated typed route functions
  views/                  # Blade templates (PDF, email, public views, welcome)
  css/                    # Tailwind CSS
tests/
  Unit/                   # Unit tests (models, services, value objects)
  Feature/                # Feature tests (controllers, HTTP assertions)
  Browser/                # Pest Browser tests (Playwright)
docker/
  8.5/                    # Sail PHP 8.5 Dockerfile
  production/             # Production Docker configurations
```

## Production Deployment

```bash
# FrankenPHP + Laravel Octane (Recommended)
docker-compose -f docker/production/docker-compose.prod.yml up -d
```

See [docker/production/README.md](docker/production/README.md) for the complete deployment guide.

## License

This application is intellectual property of CLARITY Technologies.
