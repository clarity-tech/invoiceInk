#!/bin/bash

# Cleanup script for obsolete nginx configuration files
# Removes files that have been superseded by H5BP integration

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

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

# Script configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
NGINX_DIR="$(dirname "$SCRIPT_DIR")/nginx"

# Files to remove (obsolete after H5BP integration)
OBSOLETE_FILES=(
    "$NGINX_DIR/h5bp-main.conf"
    "$NGINX_DIR/h5bp-performance.conf" 
    "$NGINX_DIR/h5bp-security.conf"
    "$NGINX_DIR/nginx.conf"
    "$NGINX_DIR/site.conf"
    "$NGINX_DIR/nginx-lb.conf"
    "$NGINX_DIR/proxy_params"
)

# Backup directory for safety
BACKUP_DIR="$NGINX_DIR/obsolete-backup-$(date +%Y%m%d-%H%M%S)"

main() {
    log_info "=== Nginx Configuration Cleanup ==="
    log_info "Removing files superseded by H5BP integration"
    
    # Create backup directory
    mkdir -p "$BACKUP_DIR"
    log_info "Created backup directory: $BACKUP_DIR"
    
    # Process each obsolete file
    local files_removed=0
    local files_backed_up=0
    
    for file in "${OBSOLETE_FILES[@]}"; do
        if [ -f "$file" ]; then
            filename=$(basename "$file")
            
            # Backup the file first
            cp "$file" "$BACKUP_DIR/"
            log_info "Backed up: $filename"
            files_backed_up=$((files_backed_up + 1))
            
            # Remove the original file
            rm "$file"
            log_success "Removed: $filename"
            files_removed=$((files_removed + 1))
        else
            log_info "Not found (already clean): $(basename "$file")"
        fi
    done
    
    # Summary
    echo ""
    log_info "=== Cleanup Summary ==="
    log_success "Files removed: $files_removed"
    log_info "Files backed up: $files_backed_up"
    
    if [ $files_removed -gt 0 ]; then
        log_warning "Backup created at: $BACKUP_DIR"
        log_info "If everything works correctly, you can remove the backup directory"
        echo ""
        log_info "Current nginx configuration now uses:"
        echo "  ✅ H5BP submodule:      nginx/h5bp/ (official configs)"
        echo "  ✅ Laravel customizations: nginx/laravel-custom/ (app-specific)"
        echo "  ✅ Generated configs:      nginx/generated/ (auto-merged)"
        echo ""
        log_success "🎉 Cleanup completed! Nginx configuration is now fully H5BP-integrated."
    else
        log_success "No obsolete files found - configuration is already clean!"
        # Remove empty backup directory
        rmdir "$BACKUP_DIR" 2>/dev/null || true
    fi
}

# Show what will be removed
show_preview() {
    log_info "=== Files to be removed (obsolete after H5BP integration) ==="
    
    for file in "${OBSOLETE_FILES[@]}"; do
        if [ -f "$file" ]; then
            echo "  ❌ $(basename "$file") - $(du -h "$file" | cut -f1)"
        else
            echo "  ✅ $(basename "$file") - already removed"
        fi
    done
    
    echo ""
    log_warning "These files will be backed up before removal"
    echo ""
}

# Check if running in preview mode
if [ "$1" = "--preview" ]; then
    show_preview
    exit 0
fi

# Confirm before proceeding
if [ "$1" != "--yes" ]; then
    show_preview
    read -p "Proceed with cleanup? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log_info "Cleanup cancelled"
        exit 0
    fi
fi

# Run the cleanup
main