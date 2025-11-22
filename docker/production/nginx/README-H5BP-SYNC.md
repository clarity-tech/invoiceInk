# H5BP Nginx Configuration Sync System

This document describes the HTML5 Boilerplate (H5BP) nginx configuration synchronization system that keeps our Laravel application's nginx setup current with the latest H5BP best practices.

## Overview

Our nginx configuration combines:
- **H5BP Base Configurations**: Security, performance, and web standards best practices
- **Laravel Customizations**: Application-specific settings for Laravel/Livewire
- **Automated Sync**: Monthly checks for H5BP updates with automated PR creation

## Architecture

```
docker/production/nginx/
├── h5bp/                    # Git submodule (official H5BP repo)
├── laravel-custom/          # Laravel-specific overrides
├── generated/               # Merged configurations (auto-generated)
├── scripts/                 # Sync and update scripts
└── README-H5BP-SYNC.md     # This documentation
```

## H5BP Version Tracking

- **Current Version**: Tracked in `generated/VERSION`
- **Source Repository**: https://github.com/h5bp/server-configs-nginx
- **Update Frequency**: Monthly automated checks
- **Manual Updates**: Available via script or workflow dispatch

## Quick Start

### 1. Manual Sync (Development)

```bash
# Update H5BP to latest version and regenerate configs
./docker/production/scripts/sync-h5bp.sh --update-submodule

# Sync without updating H5BP version
./docker/production/scripts/sync-h5bp.sh
```

### 2. Build Container with H5BP

```bash
# Build container with merged H5BP configurations
docker build -f docker/production/Dockerfile.nginx-fpm --target production -t invoicing-nginx:latest .
```

### 3. Check Configuration Status

```bash
# Check current H5BP version
cat docker/production/nginx/generated/VERSION

# View generated configurations
ls -la docker/production/nginx/generated/
```

## File Structure Explained

### H5BP Submodule (`h5bp/`)
- **Source**: Official H5BP nginx configurations
- **Management**: Git submodule tracking specific versions
- **Update**: Via sync script or CI/CD workflow

### Laravel Customizations (`laravel-custom/`)
- **`laravel-overrides.conf`**: Laravel-specific nginx directives
- **`laravel-rate-limits.conf`**: Authentication and API rate limiting
- **`laravel-csp.conf`**: Content Security Policy optimized for Livewire

### Generated Configurations (`generated/`)
- **`nginx.conf`**: Main nginx config with H5BP includes
- **`laravel-site.conf`**: Server block with merged settings
- **`Dockerfile.nginx-snippet`**: Copy instructions for Dockerfile
- **`VERSION`**: H5BP version and sync metadata

## Sync Script Features

The `sync-h5bp.sh` script provides:

### ✅ **Automated Merging**
- Combines H5BP base configs with Laravel customizations
- Preserves Laravel-specific settings while updating H5BP features
- Generates production-ready nginx configurations

### ✅ **Version Control**
- Tracks H5BP versions and sync dates
- Creates detailed changelog of what changed
- Maintains audit trail of configuration updates

### ✅ **Validation**
- Syntax checking of generated nginx configurations
- Compatibility verification with Laravel requirements
- Dependency resolution for included modules

### ✅ **Laravel Optimization**
- **Rate Limiting**: 10/min for auth endpoints, 60/min for API
- **CSP Headers**: Livewire-compatible Content Security Policy
- **File Security**: Laravel-specific file access controls
- **Performance**: Optimized caching for Laravel assets

## Automated Update Workflow

### Monthly Checks
- **Schedule**: 1st of every month at 09:00 UTC
- **Action**: Check for new H5BP releases
- **Output**: Automated PR if updates available

### Workflow Steps
1. **Version Detection**: Compare current vs latest H5BP version
2. **Submodule Update**: Checkout latest H5BP tag
3. **Configuration Sync**: Run sync script to merge configs
4. **Validation**: Test nginx syntax and container build
5. **PR Creation**: Submit changes for review

### Manual Trigger
```bash
# Trigger workflow manually via GitHub Actions
# Go to Actions → H5BP Configuration Update Check → Run workflow
```

## Configuration Details

### H5BP Features Included

| Feature | Description | Impact |
|---------|-------------|---------|
| **Security Headers** | X-Frame-Options, CSP, HSTS, etc. | Enhanced security posture |
| **Compression** | Gzip/Brotli for 15+ MIME types | 60-80% bandwidth reduction |
| **Caching** | File-type specific cache rules | Improved performance |
| **Rate Limiting** | Request throttling by endpoint | DDoS and abuse protection |
| **File Security** | Block access to sensitive files | Prevent information disclosure |

### Laravel-Specific Enhancements

| Configuration | Purpose | Implementation |
|---------------|---------|----------------|
| **Health Check** | `/up` endpoint for monitoring | `laravel-overrides.conf` |
| **Front Controller** | Laravel routing pattern | `try_files $uri /index.php` |
| **Auth Rate Limiting** | Login protection | 10 requests/minute |
| **API Rate Limiting** | API abuse prevention | 60 requests/minute |
| **Livewire CSP** | Compatible security policy | Unsafe-inline allowances |

## Update Process

### 1. Automated Updates (Recommended)
- Monthly workflow creates PR with changes
- Review changes in generated configurations
- Test in staging environment
- Merge PR to deploy

### 2. Manual Updates
```bash
# Step 1: Update H5BP
cd docker/production/nginx/h5bp
git fetch origin
git checkout $(git tag --sort=-version:refname | head -1)

# Step 2: Regenerate configurations
cd ../../..
./scripts/sync-h5bp.sh

# Step 3: Build and test
docker build -f Dockerfile.nginx-fpm -t test-nginx .

# Step 4: Commit changes
git add nginx/h5bp nginx/generated nginx/laravel-custom
git commit -m "feat: update H5BP configurations to $(cd nginx/h5bp && git describe --tags)"
```

## Troubleshooting

### Common Issues

#### 1. Nginx Syntax Errors
```bash
# Validate configuration
nginx -t -c docker/production/nginx/generated/nginx.conf

# Check for missing includes
ls -la docker/production/nginx/h5bp/h5bp/
```

#### 2. Container Build Failures
```bash
# Check if all files exist
ls -la docker/production/nginx/generated/
ls -la docker/production/nginx/laravel-custom/

# Rebuild sync
./docker/production/scripts/sync-h5bp.sh
```

#### 3. Submodule Issues
```bash
# Reinitialize submodule
git submodule deinit docker/production/nginx/h5bp
git submodule update --init docker/production/nginx/h5bp
```

### Validation Commands

```bash
# Check H5BP version
cd docker/production/nginx/h5bp && git describe --tags

# Validate syntax (requires nginx)
nginx -t -c $(pwd)/docker/production/nginx/generated/nginx.conf

# Test container build
docker build -f docker/production/Dockerfile.nginx-fpm --target production -t test .
```

## Security Considerations

### Header Security
- **CSP**: Customized for Laravel/Livewire compatibility
- **HSTS**: Available for HTTPS deployments
- **Frame Options**: Prevent clickjacking attacks
- **Content Type**: Prevent MIME type confusion

### File Access Control
- **Hidden Files**: Block `.env`, `.git`, etc.
- **Backup Files**: Block editor backups and temporary files
- **Upload Security**: Prevent PHP execution in uploads
- **Laravel Files**: Block access to `artisan`, `composer.json`, etc.

### Rate Limiting
- **Authentication**: 10 requests/minute for login endpoints
- **API Endpoints**: 60 requests/minute for API calls
- **Burst Handling**: Allow temporary spikes with nodelay

## Performance Monitoring

### Key Metrics to Track
- **Response Times**: Monitor after H5BP updates
- **Compression Ratios**: Verify gzip/brotli effectiveness
- **Cache Hit Rates**: Monitor file caching performance
- **Security Events**: Track blocked requests and rate limits

### Recommended Tools
- **Nginx Access Logs**: Monitor request patterns
- **Performance Monitoring**: New Relic, DataDog, etc.
- **Security Scanning**: OWASP ZAP, Qualys SSL Labs
- **Load Testing**: k6, Apache Bench

## Contributing

### Adding New Laravel Customizations

1. **Edit Laravel Custom Files**:
   ```bash
   # Add to laravel-overrides.conf
   vim docker/production/nginx/laravel-custom/laravel-overrides.conf
   ```

2. **Regenerate Configurations**:
   ```bash
   ./docker/production/scripts/sync-h5bp.sh
   ```

3. **Test and Validate**:
   ```bash
   docker build -f docker/production/Dockerfile.nginx-fpm -t test .
   ```

### Modifying H5BP Integration

1. **Update Sync Script**: Modify `scripts/sync-h5bp.sh`
2. **Test Changes**: Run sync script with test data
3. **Update Documentation**: Update this README
4. **Create PR**: Submit changes for review

## Reference Links

- **H5BP Nginx Configs**: https://github.com/h5bp/server-configs-nginx
- **H5BP Documentation**: https://h5bp.org/
- **Laravel Nginx Guide**: https://laravel.com/docs/deployment#nginx
- **Nginx Documentation**: https://nginx.org/en/docs/

---

**Last Updated**: 2025-07-27  
**H5BP Version**: 5.0.1  
**Maintainer**: Laravel Invoicing DevOps Team