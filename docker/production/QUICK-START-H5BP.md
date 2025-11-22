# H5BP Nginx Configuration - Quick Start Guide

## 🚀 TL;DR

We now use **HTML5 Boilerplate (H5BP) nginx configurations** with automated syncing to stay current with web security and performance best practices.

## ⚡ Quick Commands

```bash
# Update H5BP and regenerate configs
./docker/production/scripts/sync-h5bp.sh --update-submodule

# Build container with H5BP
docker build -f docker/production/Dockerfile.nginx-fpm -t invoicing-nginx:latest .

# Check current H5BP version
cat docker/production/nginx/generated/VERSION
```

## 📁 What Changed

### Before H5BP Integration
```
docker/production/nginx/
├── site.conf           # Manual nginx config
├── nginx.conf          # Custom nginx settings
└── php.ini             # PHP configuration
```

### After H5BP Integration
```
docker/production/nginx/
├── h5bp/                     # 📦 H5BP official configs (submodule)
├── laravel-custom/           # 🎯 Laravel-specific settings  
├── generated/                # ⚙️  Merged configs (auto-generated)
│   ├── nginx.conf           # 🔄 H5BP + Laravel main config
│   └── laravel-site.conf    # 🔄 H5BP + Laravel server block
└── scripts/sync-h5bp.sh     # 🔧 Sync automation script
```

## 🎯 Key Benefits

| Feature | Before | After H5BP |
|---------|--------|------------|
| **Security Headers** | 4 basic | 6+ comprehensive + CSP |
| **Performance** | Manual optimization | H5BP best practices |
| **Updates** | Manual maintenance | Automated monthly checks |
| **Standards** | Custom implementation | Industry best practices |

## 🔄 Automated Updates

- **Monthly Checks**: 1st of every month at 09:00 UTC
- **Auto PR Creation**: When H5BP updates available
- **Manual Trigger**: Available via GitHub Actions

## 🛠️ Configuration Highlights

### Security Enhancements
```nginx
# Enhanced security headers
add_header X-Frame-Options "SAMEORIGIN" always;
add_header Content-Security-Policy "default-src 'self'; ..." always;
add_header Permissions-Policy "geolocation=(), microphone=(), ..." always;

# Rate limiting
limit_req zone=auth burst=5 nodelay;    # Login: 10/min
limit_req zone=api burst=20 nodelay;    # API: 60/min
```

### Performance Optimizations
```nginx
# Advanced compression (15+ MIME types)
gzip_types application/javascript application/json text/css ...;

# File-specific caching
location ~* \.(?:css|js)$ { expires 1y; }      # CSS/JS: 1 year
location ~* \.(?:jpg|png)$ { expires 1M; }     # Images: 1 month
```

## 🔧 Development Workflow

### 1. Local Development
```bash
# Make changes to Laravel customizations
vim docker/production/nginx/laravel-custom/laravel-overrides.conf

# Regenerate merged configs
./docker/production/scripts/sync-h5bp.sh

# Test locally
docker build -f docker/production/Dockerfile.nginx-fpm -t test-nginx .
```

### 2. Production Updates
```bash
# Automated via GitHub Actions workflow
# Creates PR → Review → Merge → Deploy
```

## 📋 Migration Checklist

- [x] ✅ **H5BP Submodule**: Added as `docker/production/nginx/h5bp/`
- [x] ✅ **Sync Script**: Created `scripts/sync-h5bp.sh`
- [x] ✅ **Laravel Customizations**: Moved to `laravel-custom/`
- [x] ✅ **Generated Configs**: Auto-generated in `generated/`
- [x] ✅ **Dockerfile Updated**: Uses merged H5BP configurations
- [x] ✅ **CI/CD Workflow**: Monthly H5BP update checks
- [x] ✅ **Documentation**: Comprehensive guides created

## 🚨 Important Notes

### ⚠️ Don't Edit These Files Manually
- `generated/nginx.conf`
- `generated/laravel-site.conf`
- `h5bp/*` (managed by submodule)

### ✅ Edit These Files
- `laravel-custom/laravel-overrides.conf`
- `laravel-custom/laravel-rate-limits.conf`
- `laravel-custom/laravel-csp.conf`

### 🔄 After Changes, Always Run
```bash
./docker/production/scripts/sync-h5bp.sh
```

## 🎯 Next Steps

1. **Test Current Setup**: Build and test the H5BP-enabled container
2. **Monitor Performance**: Track metrics after deployment
3. **Review Updates**: Check monthly H5BP update PRs
4. **Customize as Needed**: Add Laravel-specific configurations

---

📚 **Full Documentation**: See `docker/production/nginx/README-H5BP-SYNC.md`  
🔗 **H5BP Source**: https://github.com/h5bp/server-configs-nginx  
⚙️ **Current Version**: H5BP 5.0.1 (auto-updating)