# Production Docker Setup for Laravel Invoicing with FrankenPHP + Octane

This directory contains multiple production-ready Docker configurations for the Laravel Invoicing application, each optimized for different deployment scenarios.

## Available Docker Variants

### 1. 🚀 FrankenPHP + Octane (Primary)
**File**: `Dockerfile.frankenphp`  
**Image**: `ghcr.io/clarity-tech/invoicing:latest`

- **Technology**: FrankenPHP + Laravel Octane + ServerSideUp base images
- **Performance**: High performance with worker mode and HTTP/2/3 support
- **Use Case**: Modern production deployments requiring maximum performance
- **Memory**: ~128-256MB per container
- **Scaling**: Vertical scaling with worker processes

```bash
# Build
docker build -f docker/production/Dockerfile.frankenphp -t invoicing-frankenphp .

# Run with docker-compose
docker-compose -f docker/production/docker-compose.prod.yml up -d
```

**Features**:
- Multi-stage build with optimized assets
- Built-in worker mode for Laravel
- HTTP/2 and HTTP/3 support
- Supervisor for background processes
- Health checks and security hardening

### 2. 🐳 Traditional Nginx + PHP-FPM
**File**: `Dockerfile.nginx-fpm`  
**Image**: `ghcr.io/clarity-tech/invoicing-nginx:latest`

- **Technology**: ServerSideUp Nginx + PHP-FPM
- **Performance**: Traditional high performance setup
- **Use Case**: Conservative production environments, existing Nginx expertise
- **Memory**: ~96-128MB per container
- **Scaling**: Horizontal scaling with separate web/app tiers

```bash
# Build
docker build -f docker/production/Dockerfile.nginx-fpm -t invoicing-nginx .

# Run standalone
docker run -d -p 80:80 \
  --env-file .env.production \
  invoicing-nginx
```

**Features**:
- Separate Nginx and PHP-FPM processes
- Traditional web server configuration
- Easy to understand and debug
- Compatible with existing infrastructure

### 3. ⚡ Standalone Binary (Ultra-Minimal)
**File**: `Dockerfile.standalone`  
**Images**: 
- `ghcr.io/clarity-tech/invoicing-standalone:latest` (Distroless)
- `ghcr.io/clarity-tech/invoicing-standalone-alpine:latest` (Alpine)

- **Technology**: FrankenPHP static binary with embedded application
- **Performance**: Ultimate performance and minimal resource usage
- **Use Case**: Microservices, serverless, high-density deployments
- **Memory**: ~32-64MB per container
- **Scaling**: Horizontal scaling with immutable containers

```bash
# Build distroless version
docker build -f docker/production/Dockerfile.standalone --target runtime -t invoicing-standalone .

# Build Alpine version  
docker build -f docker/production/Dockerfile.standalone --target standalone-alpine -t invoicing-standalone-alpine .

# Run with docker-compose
docker-compose -f docker/production/docker-compose.standalone.yml up -d
```

**Features**:
- Single binary with embedded application
- Distroless or Alpine base for security
- Immutable containers
- Perfect for Kubernetes/microservices
- Extreme resource efficiency

## Configuration Files

### Core Configuration
- `php.ini` - Production PHP configuration with OPcache optimization
- `Caddyfile` - FrankenPHP web server configuration
- `entrypoint.sh` - Production startup script with health checks
- `supervisor.conf` - Process management for queue workers

### Nginx Configuration
- `nginx/site.conf` - Nginx site configuration for PHP-FPM
- `nginx/nginx-lb.conf` - Load balancer configuration for standalone binaries
- `nginx/proxy_params` - Proxy parameters for load balancing
- `entrypoint-nginx.sh` - Nginx + PHP-FPM startup script
- `supervisor-nginx.conf` - Supervisor configuration for Nginx setup

### Docker Compose Files
- `docker-compose.prod.yml` - Full production stack with FrankenPHP
- `docker-compose.standalone.yml` - Standalone binary deployment with scaling

## Environment Variables

### Required Variables
```bash
# Application
APP_NAME="Clarity Invoicing"
APP_KEY=base64:your-secret-key
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_DATABASE=invoicing
DB_USERNAME=postgres
DB_PASSWORD=your-db-password

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=your-redis-password

# Mail
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=noreply@your-domain.com
```

### Optional Variables
```bash
# Octane Configuration (FrankenPHP variants)
OCTANE_ENABLED=true
OCTANE_WORKERS=auto
OCTANE_MAX_REQUESTS=500

# Performance Tuning
RUN_MIGRATIONS=true
RUN_SEEDERS=false
ENABLE_SUPERVISOR=true

# Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_BUCKET=your-bucket
```

## Deployment Recommendations

### 🏢 Enterprise Production
**Recommended**: FrankenPHP + Octane
- High performance requirements
- Modern infrastructure
- Team comfortable with new technologies

### 🏛️ Conservative Production  
**Recommended**: Nginx + PHP-FPM
- Existing Nginx expertise
- Gradual migration strategy
- Well-understood deployment patterns

### ☁️ Cloud Native / Microservices
**Recommended**: Standalone Binary
- Kubernetes deployments
- Serverless environments (Knative, etc.)
- High-density container deployments
- Cost optimization priority

### 🔧 Development / Testing
**Recommended**: Any variant works
- Use docker-compose for local development
- FrankenPHP for testing production behavior
- Standalone for resource-constrained environments

## Performance Comparison

| Variant | Memory Usage | Startup Time | Request/sec | Container Size |
|---------|-------------|--------------|-------------|----------------|
| FrankenPHP + Octane | 128-256MB | ~10s | ~2000+ | ~200MB |
| Nginx + PHP-FPM | 96-128MB | ~8s | ~1500+ | ~180MB |
| Standalone Binary | 32-64MB | ~3s | ~2500+ | ~50MB |

## Security Features

All variants include:
- ✅ Non-root user execution
- ✅ Security headers (HSTS, CSP, etc.)
- ✅ Vulnerability scanning in CI/CD
- ✅ Minimal attack surface
- ✅ Regular security updates via base images

## Monitoring & Observability

### Health Checks
- HTTP endpoints: `/up` or `/health`
- Container health checks built-in
- Kubernetes readiness/liveness probes supported

### Logging
- Structured JSON logging to stderr
- Centralized log aggregation ready
- Performance metrics included

### Metrics
- Built-in PHP-FPM metrics (Nginx variant)
- Octane metrics (FrankenPHP variant)
- Custom application metrics via Laravel

## GitHub Actions CI/CD

The repository includes automated building and testing of all variants:

```yaml
# .github/workflows/docker-build.yml builds:
- FrankenPHP + Octane (latest)
- Nginx + PHP-FPM (nginx)  
- Standalone Binary Distroless (standalone)
- Standalone Binary Alpine (standalone-alpine)
```

All images are pushed to GitHub Container Registry with:
- Multi-platform builds (AMD64 + ARM64)
- Vulnerability scanning
- Automated testing
- Semantic versioning

## Quick Start

1. **Choose your variant** based on requirements above
2. **Copy environment variables** from `.env.example`
3. **Build and run**:

```bash
# FrankenPHP (recommended for most)
docker-compose -f docker/production/docker-compose.prod.yml up -d

# Standalone (for cloud-native)
docker-compose -f docker/production/docker-compose.standalone.yml up -d
```

4. **Access the application** at http://localhost:8000

## Support

- 📚 [Laravel Octane Documentation](https://laravel.com/docs/octane)
- 🚀 [FrankenPHP Documentation](https://frankenphp.dev/)
- 🐳 [ServerSideUp Docker Images](https://serversideup.net/open-source/docker-php/)
- 🔧 Issues: Create GitHub issue with variant details