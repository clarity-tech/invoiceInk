#!/bin/bash

# H5BP Nginx Configuration Sync Script
# Merges official H5BP configs with Laravel-specific customizations
# Usage: ./sync-h5bp.sh [--update-submodule]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
NGINX_DIR="$(dirname "$SCRIPT_DIR")/nginx"
H5BP_DIR="$NGINX_DIR/h5bp"
OUTPUT_DIR="$NGINX_DIR/generated"
LARAVEL_DIR="$NGINX_DIR/laravel-custom"

log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if H5BP submodule exists and is initialized
check_h5bp_submodule() {
    if [ ! -d "$H5BP_DIR" ] || [ ! -f "$H5BP_DIR/nginx.conf" ]; then
        log_error "H5BP submodule not found or not initialized!"
        log_info "Run: git submodule update --init --recursive"
        exit 1
    fi
}

# Update H5BP submodule to latest version
update_h5bp_submodule() {
    if [ "$1" = "--update-submodule" ]; then
        log_info "Updating H5BP submodule to latest version..."
        cd "$H5BP_DIR"
        git fetch origin
        LATEST_TAG=$(git tag --sort=-version:refname | head -1)
        git checkout "$LATEST_TAG"
        cd - > /dev/null
        log_success "Updated H5BP to version $LATEST_TAG"
    fi
}

# Create Laravel-specific customization directory structure
create_laravel_customizations() {
    mkdir -p "$LARAVEL_DIR"
    
    # Create Laravel-specific overrides
    cat > "$LARAVEL_DIR/laravel-overrides.conf" << 'EOF'
# Laravel-specific nginx configuration overrides
# These settings override or extend H5BP defaults for Laravel applications

# Laravel application settings
client_max_body_size 50M;

# Laravel health check endpoint
location /up {
    access_log off;
    return 200 "OK";
    add_header Content-Type text/plain;
}

# Laravel front controller pattern
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

# Rate limiting for Laravel authentication endpoints
location ~* ^/(login|register|password|two-factor-challenge) {
    limit_req zone=auth burst=5 nodelay;
    try_files $uri $uri/ /index.php?$query_string;
}

# Rate limiting for API endpoints
location ~* ^/api/ {
    limit_req zone=api burst=20 nodelay;
    try_files $uri $uri/ /index.php?$query_string;
}

# Laravel-specific security blocks
location ~ /(\.env|artisan|composer\.(json|lock)|package\.(json|lock)|bun\.lock|yarn\.lock) {
    deny all;
    access_log off;
    log_not_found off;
}

# Block access to Laravel storage and bootstrap cache
location ~ /(storage|bootstrap/cache) {
    deny all;
    access_log off;
    log_not_found off;
}

# PHP-FPM configuration for Laravel
location ~ \.php$ {
    # Security: Don't allow accessing any .php files that don't exist
    try_files $uri =404;
    
    fastcgi_split_path_info ^(.+\.php)(/.+)$;
    fastcgi_pass 127.0.0.1:9000;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
    
    # FastCGI settings optimized for Laravel
    fastcgi_connect_timeout 60s;
    fastcgi_send_timeout 60s;
    fastcgi_read_timeout 60s;
    fastcgi_buffer_size 128k;
    fastcgi_buffers 4 256k;
    fastcgi_busy_buffers_size 256k;
    fastcgi_temp_file_write_size 256k;
    
    # Hide PHP version
    fastcgi_hide_header X-Powered-By;
}
EOF

    # Create Laravel-specific rate limiting zones
    cat > "$LARAVEL_DIR/laravel-rate-limits.conf" << 'EOF'
# Laravel-specific rate limiting zones
# Include this in the http block of nginx.conf

# Rate limiting zones for Laravel authentication
limit_req_zone $binary_remote_addr zone=auth:10m rate=10r/m;

# Rate limiting zones for API endpoints
limit_req_zone $binary_remote_addr zone=api:10m rate=60r/m;

# Rate limiting for general requests
limit_req_zone $binary_remote_addr zone=general:10m rate=10r/s;
EOF

    # Create Laravel-specific CSP for Livewire compatibility
    cat > "$LARAVEL_DIR/laravel-csp.conf" << 'EOF'
# Laravel Livewire-compatible Content Security Policy
# Override H5BP CSP with Laravel/Livewire optimizations

add_header Content-Security-Policy "
    default-src 'self';
    script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net;
    style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
    img-src 'self' data: https:;
    font-src 'self' data: https://fonts.gstatic.com;
    connect-src 'self';
    frame-ancestors 'self';
    worker-src 'self' blob:;
    child-src 'self' blob:;
" always;
EOF
}

# Generate merged nginx configuration
generate_merged_config() {
    mkdir -p "$OUTPUT_DIR"
    
    log_info "Generating merged nginx configuration..."
    
    # Create main nginx.conf with H5BP base + Laravel customizations
    cat > "$OUTPUT_DIR/nginx.conf" << 'EOF'
# Nginx configuration for Laravel with HTML5 Boilerplate optimizations
# Generated by sync-h5bp.sh - DO NOT EDIT MANUALLY

user www-data;
worker_processes auto;
pid /run/nginx.pid;

# Worker configuration
worker_rlimit_nofile 65535;

events {
    worker_connections 1024;
    use epoll;
    multi_accept on;
}

http {
    # H5BP: Basic settings
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    # H5BP: Logging format
    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';

    # Include H5BP configurations
    include /etc/nginx/h5bp/basic.conf;
    include /etc/nginx/h5bp/web_performance/compression.conf;
    include /etc/nginx/h5bp/web_performance/cache-file-descriptors.conf;
    include /etc/nginx/h5bp/security/server_software_information.conf;
    
    # Include Laravel-specific rate limiting zones
    include /etc/nginx/laravel-custom/laravel-rate-limits.conf;

    # Include server configurations
    include /etc/nginx/conf.d/*.conf;
}
EOF

    # Create Laravel server block with H5BP includes
    cat > "$OUTPUT_DIR/laravel-site.conf" << 'EOF'
# Laravel production server configuration with H5BP optimizations
# Generated by sync-h5bp.sh - DO NOT EDIT MANUALLY

server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php index.html;

    # Include H5BP security configurations
    include /etc/nginx/h5bp/security/x-frame-options.conf;
    include /etc/nginx/h5bp/security/x-content-type-options.conf;
    include /etc/nginx/h5bp/security/referrer-policy.conf;
    include /etc/nginx/h5bp/security/permissions-policy.conf;
    
    # Include Laravel-specific CSP (overrides H5BP default)
    include /etc/nginx/laravel-custom/laravel-csp.conf;

    # Include H5BP web performance configurations
    include /etc/nginx/h5bp/web_performance/cache_expiration.conf;
    include /etc/nginx/h5bp/web_performance/compression.conf;

    # Include H5BP location-based configurations
    include /etc/nginx/h5bp/location/security_file_access.conf;
    include /etc/nginx/h5bp/location/web_performance_filename-based_cache_busting.conf;

    # Laravel-specific configurations
    include /etc/nginx/laravel-custom/laravel-overrides.conf;

    # Logging
    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log warn;
}
EOF

    log_success "Generated merged configuration files:"
    log_info "  - $OUTPUT_DIR/nginx.conf"
    log_info "  - $OUTPUT_DIR/laravel-site.conf"
}

# Generate Dockerfile snippet for copying configurations
generate_dockerfile_snippet() {
    cat > "$OUTPUT_DIR/Dockerfile.nginx-snippet" << 'EOF'
# Copy H5BP and Laravel nginx configurations
# Add this to your Dockerfile.nginx-fpm

# Copy H5BP base configurations
COPY --chown=www-data:www-data docker/production/nginx/h5bp/h5bp/ /etc/nginx/h5bp/
COPY --chown=www-data:www-data docker/production/nginx/h5bp/mime.types /etc/nginx/mime.types

# Copy Laravel customizations
COPY --chown=www-data:www-data docker/production/nginx/laravel-custom/ /etc/nginx/laravel-custom/

# Copy generated configurations
COPY --chown=www-data:www-data docker/production/nginx/generated/nginx.conf /etc/nginx/nginx.conf
COPY --chown=www-data:www-data docker/production/nginx/generated/laravel-site.conf /etc/nginx/conf.d/default.conf
EOF

    log_success "Generated Dockerfile snippet: $OUTPUT_DIR/Dockerfile.nginx-snippet"
}

# Create version tracking file
create_version_info() {
    cd "$H5BP_DIR"
    H5BP_VERSION=$(git describe --tags --always)
    cd - > /dev/null
    
    cat > "$OUTPUT_DIR/VERSION" << EOF
# H5BP Nginx Configuration Version Information
H5BP_VERSION=$H5BP_VERSION
SYNC_DATE=$(date -u +"%Y-%m-%d %H:%M:%S UTC")
SYNC_SCRIPT_VERSION=1.0.0
EOF

    log_success "Created version tracking file: $OUTPUT_DIR/VERSION"
}

# Validate generated configurations
validate_configurations() {
    log_info "Validating generated nginx configurations..."
    
    # Create temporary directory for validation
    TEMP_DIR=$(mktemp -d)
    
    # Copy all files to temp directory
    cp -r "$H5BP_DIR/h5bp" "$TEMP_DIR/"
    cp -r "$LARAVEL_DIR" "$TEMP_DIR/"
    cp "$OUTPUT_DIR/nginx.conf" "$TEMP_DIR/"
    
    # Test nginx configuration syntax
    if command -v nginx >/dev/null 2>&1; then
        if nginx -t -c "$TEMP_DIR/nginx.conf" -p "$TEMP_DIR" 2>/dev/null; then
            log_success "Nginx configuration syntax is valid"
        else
            log_warning "Nginx configuration syntax validation failed (nginx not available or config issues)"
        fi
    else
        log_warning "Nginx not available for syntax validation"
    fi
    
    # Cleanup
    rm -rf "$TEMP_DIR"
}

# Main execution
main() {
    log_info "=== H5BP Nginx Configuration Sync ==="
    
    check_h5bp_submodule
    update_h5bp_submodule "$1"
    create_laravel_customizations
    generate_merged_config
    generate_dockerfile_snippet
    create_version_info
    validate_configurations
    
    log_success "🎉 H5BP sync completed successfully!"
    log_info "Next steps:"
    echo "  1. Review generated files in: $OUTPUT_DIR"
    echo "  2. Update your Dockerfile using: $OUTPUT_DIR/Dockerfile.nginx-snippet"
    echo "  3. Build and test your container"
    echo "  4. Commit changes to version control"
}

# Run main function with all arguments
main "$@"