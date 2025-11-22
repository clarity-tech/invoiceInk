#!/bin/bash

# Production Docker Build Test Script
# Tests all Docker variants locally before deployment

set -e

echo "🚀 Starting comprehensive Docker build tests..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test results tracking
RESULTS=()
FAILED_BUILDS=()

# Helper functions
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

# Test individual Docker build
test_build() {
    local dockerfile=$1
    local tag=$2
    local target=$3
    local description=$4
    
    log_info "Building $description..."
    
    if [ -n "$target" ]; then
        build_cmd="docker build -f $dockerfile --target $target -t $tag ../.."
    else
        build_cmd="docker build -f $dockerfile -t $tag ../.."
    fi
    
    echo "Command: $build_cmd"
    
    if eval $build_cmd; then
        log_success "$description build successful"
        RESULTS+=("✅ $description")
        return 0
    else
        log_error "$description build failed"
        RESULTS+=("❌ $description")
        FAILED_BUILDS+=("$description")
        return 1
    fi
}

# Test container startup
test_container() {
    local image=$1
    local name=$2
    local port=$3
    local description=$4
    
    log_info "Testing $description container startup..."
    
    # Stop and remove existing container if it exists
    docker stop $name 2>/dev/null || true
    docker rm $name 2>/dev/null || true
    
    # Start container
    if docker run -d --name $name -p $port:$port \
        -e APP_KEY=base64:$(openssl rand -base64 32) \
        -e APP_ENV=testing \
        -e DB_CONNECTION=sqlite \
        -e DB_DATABASE=:memory: \
        -e CACHE_DRIVER=array \
        -e SESSION_DRIVER=array \
        -e QUEUE_CONNECTION=sync \
        $image; then
        
        # Wait for container to start
        sleep 15
        
        # Test if container is running
        if docker ps | grep -q $name; then
            log_success "$description container started successfully"
            
            # Basic connectivity test
            if curl -f -s http://localhost:$port/up >/dev/null 2>&1; then
                log_success "$description health check passed"
                RESULTS+=("✅ $description startup & health")
            else
                log_warning "$description started but health check failed"
                RESULTS+=("⚠️ $description startup only")
            fi
        else
            log_error "$description container failed to start"
            RESULTS+=("❌ $description startup")
        fi
        
        # Cleanup
        docker stop $name 2>/dev/null || true
        docker rm $name 2>/dev/null || true
    else
        log_error "$description container failed to run"
        RESULTS+=("❌ $description run")
    fi
}

# Test image size
test_image_size() {
    local image=$1
    local description=$2
    
    size=$(docker image inspect $image --format='{{.Size}}' | awk '{print int($1/1024/1024)}')
    log_info "$description image size: ${size}MB"
    RESULTS+=("📏 $description: ${size}MB")
}

# Main test execution
main() {
    cd "$(dirname "$0")"
    
    echo "Working directory: $(pwd)"
    echo "Testing from: $(pwd)/../.."
    
    # Test 1: FrankenPHP + Octane
    log_info "=== Testing FrankenPHP + Octane ==="
    if test_build "Dockerfile.frankenphp" "invoicing-frankenphp:test" "frankenphp" "FrankenPHP + Octane"; then
        test_image_size "invoicing-frankenphp:test" "FrankenPHP"
        test_container "invoicing-frankenphp:test" "test-frankenphp" "8000" "FrankenPHP"
    fi
    
    echo ""
    
    # Test 2: Nginx + PHP-FPM
    log_info "=== Testing Nginx + PHP-FPM ==="
    if test_build "Dockerfile.nginx-fpm" "invoicing-nginx:test" "production" "Nginx + PHP-FPM"; then
        test_image_size "invoicing-nginx:test" "Nginx"
        test_container "invoicing-nginx:test" "test-nginx" "80" "Nginx"
    fi
    
    echo ""
    
    # Test 3: Standalone Binary (Runtime)
    log_info "=== Testing Standalone Binary (Debian) ==="
    if command -v go >/dev/null 2>&1; then
        if test_build "Dockerfile.standalone" "invoicing-standalone:test" "runtime" "Standalone Binary (Debian)"; then
            test_image_size "invoicing-standalone:test" "Standalone"
            test_container "invoicing-standalone:test" "test-standalone" "8000" "Standalone"
        fi
    else
        log_warning "Go not available, skipping standalone binary tests"
        RESULTS+=("⚠️ Standalone Binary (Go required)")
    fi
    
    echo ""
    
    # Test 4: Standalone Binary (Alpine)
    log_info "=== Testing Standalone Binary (Alpine) ==="
    if command -v go >/dev/null 2>&1; then
        if test_build "Dockerfile.standalone" "invoicing-standalone-alpine:test" "standalone-alpine" "Standalone Binary (Alpine)"; then
            test_image_size "invoicing-standalone-alpine:test" "Standalone Alpine"
            test_container "invoicing-standalone-alpine:test" "test-standalone-alpine" "8000" "Standalone Alpine"
        fi
    else
        log_warning "Go not available, skipping standalone Alpine binary tests"
        RESULTS+=("⚠️ Standalone Alpine (Go required)")
    fi
    
    echo ""
    
    # Test 5: Docker Compose validation
    log_info "=== Testing Docker Compose Files ==="
    
    if docker-compose -f docker-compose.prod.yml config >/dev/null 2>&1; then
        log_success "Production docker-compose.yml is valid"
        RESULTS+=("✅ Production compose validation")
    else
        log_error "Production docker-compose.yml is invalid"
        RESULTS+=("❌ Production compose validation")
    fi
    
    if docker-compose -f docker-compose.standalone.yml config >/dev/null 2>&1; then
        log_success "Standalone docker-compose.yml is valid"
        RESULTS+=("✅ Standalone compose validation")
    else
        log_error "Standalone docker-compose.yml is invalid"
        RESULTS+=("❌ Standalone compose validation")
    fi
    
    echo ""
    
    # Summary
    log_info "=== Test Summary ==="
    
    for result in "${RESULTS[@]}"; do
        echo -e "$result"
    done
    
    echo ""
    
    if [ ${#FAILED_BUILDS[@]} -eq 0 ]; then
        log_success "🎉 All builds completed successfully!"
        echo ""
        log_info "Next steps:"
        echo "1. Commit your changes"
        echo "2. Push to trigger GitHub Actions"
        echo "3. Monitor the automated builds"
        echo ""
        echo "Available images for testing:"
        echo "- invoicing-frankenphp:test (FrankenPHP + Octane)"
        echo "- invoicing-nginx:test (Nginx + PHP-FPM)"  
        echo "- invoicing-standalone:test (Standalone Binary)"
        exit 0
    else
        log_error "❌ Some builds failed:"
        for failed in "${FAILED_BUILDS[@]}"; do
            echo "  - $failed"
        done
        echo ""
        log_error "Please fix the failed builds before proceeding."
        exit 1
    fi
}

# Cleanup function
cleanup() {
    log_info "Cleaning up test containers..."
    docker stop test-frankenphp test-nginx test-standalone test-standalone-alpine 2>/dev/null || true
    docker rm test-frankenphp test-nginx test-standalone test-standalone-alpine 2>/dev/null || true
}

# Set trap for cleanup
trap cleanup EXIT

# Run main function
main "$@"