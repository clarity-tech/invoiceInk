![CLARITY Logo](.github/clarity-logo.png)
# Clarity Invoicing Application

A modern Laravel-based invoicing system with organization-centric architecture, multi-currency support, and comprehensive document management.

## Prerequisites

- Docker & Docker Compose
- Git

## Getting Started

### 1. Clone the repository

```bash
git clone <repository-url>
cd invoicing
git submodule update --init --recursive
```

### 2. Environment setup

```bash
cp .env.example .env
```

Update `.env` with the PostgreSQL configuration:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=secret
```

### 3. Install PHP dependencies

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php85-composer:latest \
    composer install --ignore-platform-reqs
```

### 4. Start the development environment

```bash
./vendor/bin/sail up -d
```

### 5. Generate application key

```bash
sail artisan key:generate
```

### 6. Run migrations and seed the database

```bash
sail artisan migrate:fresh --seed
```

### 7. Install frontend dependencies and build assets

```bash
sail npm install
sail npm run dev
```

The application will be available at http://localhost.

### 8. (Optional) Set up browser testing

```bash
sail composer browser-setup
```

Downloads Chromium for Pest Browser tests (~60s, one-time). Persists across container restarts — only cleared by `sail down -v`.

### Quick Start (all-in-one)

After cloning and setting up `.env`:

```bash
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php85-composer:latest composer install --ignore-platform-reqs
./vendor/bin/sail up -d
sail artisan key:generate
sail artisan migrate:fresh --seed
sail npm install
sail npm run dev
```

## Services

Once `sail up -d` is running, the following services are available:

| Service       | URL                      | Description                        |
|---------------|--------------------------|------------------------------------|
| Application   | http://localhost         | Main application                   |
| Vite          | http://localhost:5173    | Vite dev server (HMR)             |
| Mailpit       | http://localhost:8025    | Email testing dashboard            |
| pgweb         | http://localhost:8081    | Web-based PostgreSQL browser       |
| RustFS Console| http://localhost:9001    | S3-compatible object storage       |

Ports can be customized via `.env` (e.g. `APP_PORT`, `FORWARD_PGWEB_PORT`, `FORWARD_RUSTFS_CONSOLE_PORT`).

## Running Tests

```bash
# Run all tests
sail artisan test

# Run tests in parallel
sail artisan test --parallel

# Run browser tests (Pest Browser)
sail artisan test tests/Browser

# Run browser tests with visible browser
sail pest --headed tests/Browser

# Format code before committing
sail pint --dirty
```

## Useful Commands

```bash
# Start all services
sail up -d

# Stop all services
sail down

# View logs
sail logs -f

# Run artisan commands
sail artisan <command>

# Run composer commands
sail composer <command>

# Access the database CLI
sail psql

# Clear caches
sail artisan config:clear && sail artisan cache:clear

# Run the dev server with queue, logs, and vite concurrently
sail composer dev
```

## Technology Stack

- **Backend:** Laravel 12 with PHP 8.5 + Laravel Octane + Jetstream
- **Database:** PostgreSQL 17
- **Frontend:** Livewire 3 + Alpine.js + luvi-ui/laravel-luvi (shadcn for Livewire) + Tailwind CSS 4
- **Testing:** Pest + Pest Browser (Playwright)
- **PDF Generation:** Headless Chrome service
- **Cache/Queue:** Valkey (Redis-compatible)
- **Object Storage:** RustFS (S3-compatible)
- **Email:** Mailpit (development)
- **Containerization:** Laravel Sail (development)

## Project Structure

```
app/                  # Application code (Models, Livewire, Services, etc.)
database/
  migrations/         # Database migrations
  seeders/            # Database seeders
  factories/          # Model factories
resources/
  views/              # Blade & Livewire views
  css/                # Stylesheets
  js/                 # JavaScript
tests/
  Unit/               # Unit tests
  Feature/            # Feature tests
  Browser/            # Pest browser tests (Playwright)
docker/
  8.5/                # Sail PHP 8.5 Dockerfile
  chrome/             # Chrome PDF service
  pgsql/              # PostgreSQL init scripts
  production/         # Production Docker configurations
```

## Production Deployment

The application includes production Docker configurations with multiple deployment options:

```bash
# FrankenPHP + Laravel Octane (Recommended)
docker-compose -f docker/production/docker-compose.prod.yml up -d

# Traditional Nginx + PHP-FPM
docker build -f docker/production/Dockerfile.nginx-fpm -t invoicing-nginx:latest .
docker run -d -p 80:80 -e APP_KEY=your-key invoicing-nginx:latest
```

See [docker/production/README.md](docker/production/README.md) for the complete deployment guide.

## License

This application is intellectual property of CLARITY Technologies.
