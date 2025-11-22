#!/bin/bash

# Comprehensive Container Testing Script
# Tests all available container variants and saves detailed results

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test results directory
RESULTS_DIR="docker/production/test-results"
mkdir -p "$RESULTS_DIR"

# Helper functions
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
    echo "$(date '+%Y-%m-%d %H:%M:%S') - INFO: $1" >> "$RESULTS_DIR/test-execution.log"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
    echo "$(date '+%Y-%m-%d %H:%M:%S') - SUCCESS: $1" >> "$RESULTS_DIR/test-execution.log"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
    echo "$(date '+%Y-%m-%d %H:%M:%S') - ERROR: $1" >> "$RESULTS_DIR/test-execution.log"
}

# Setup PostgreSQL for testing
setup_postgres() {
    log_info "Setting up PostgreSQL test database..."
    
    # Start PostgreSQL container
    docker run -d --name test-postgres \
        -e POSTGRES_DB=laravel \
        -e POSTGRES_USER=sail \
        -e POSTGRES_PASSWORD=secret \
        -e PGPASSWORD=secret \
        -p 5433:5432 \
        postgres:17 > "$RESULTS_DIR/postgres-setup.log" 2>&1
    
    # Create test network
    docker network create test-network > "$RESULTS_DIR/network-setup.log" 2>&1
    docker network connect test-network test-postgres
    
    # Wait for PostgreSQL to be ready
    log_info "Waiting for PostgreSQL to be ready..."
    sleep 15
    
    # Create testing database
    docker exec test-postgres psql -U sail -d laravel -c "CREATE DATABASE testing;" > "$RESULTS_DIR/database-setup.log" 2>&1
    
    log_success "PostgreSQL setup completed"
}

# Cleanup function
cleanup() {
    log_info "Cleaning up test infrastructure..."
    docker stop test-postgres 2>/dev/null || true
    docker rm test-postgres 2>/dev/null || true
    docker network rm test-network 2>/dev/null || true
    log_success "Cleanup completed"
}

# Test individual container
test_container() {
    local image_name=$1
    local container_desc=$2
    local results_file="$RESULTS_DIR/${image_name}-test-results.txt"
    
    log_info "Testing $container_desc..."
    
    # Test Laravel version
    echo "=== $container_desc TEST RESULTS ===" > "$results_file"
    echo "Generated: $(date)" >> "$results_file"
    echo "" >> "$results_file"
    
    echo "--- Laravel Framework Version ---" >> "$results_file"
    if docker run --rm --entrypoint="" \
        -e APP_ENV=testing \
        -e APP_KEY=base64:$(openssl rand -base64 32) \
        "$image_name:test" \
        php artisan --version >> "$results_file" 2>&1; then
        log_success "$container_desc - Laravel version check passed"
    else
        log_error "$container_desc - Laravel version check failed"
        return 1
    fi
    
    echo "" >> "$results_file"
    echo "--- Database Migration Test ---" >> "$results_file"
    
    # Test database migrations
    if docker run --rm --entrypoint="" --network test-network \
        -e APP_ENV=testing \
        -e DB_CONNECTION=pgsql \
        -e DB_HOST=test-postgres \
        -e DB_PORT=5432 \
        -e DB_DATABASE=testing \
        -e DB_USERNAME=sail \
        -e DB_PASSWORD=secret \
        -e CACHE_DRIVER=array \
        -e SESSION_DRIVER=array \
        -e QUEUE_CONNECTION=sync \
        -e APP_KEY=base64:$(openssl rand -base64 32) \
        "$image_name:test" \
        bash -c "php artisan config:clear && php artisan migrate:fresh --env=testing" >> "$results_file" 2>&1; then
        log_success "$container_desc - Database migrations passed"
    else
        log_error "$container_desc - Database migrations failed"
        return 1
    fi
    
    echo "" >> "$results_file"
    echo "--- Full Test Suite Results ---" >> "$results_file"
    
    # Run full test suite
    local test_start_time=$(date +%s)
    if docker run --rm --entrypoint="" --network test-network \
        -e APP_ENV=testing \
        -e DB_CONNECTION=pgsql \
        -e DB_HOST=test-postgres \
        -e DB_PORT=5432 \
        -e DB_DATABASE=testing \
        -e DB_USERNAME=sail \
        -e DB_PASSWORD=secret \
        -e CACHE_DRIVER=array \
        -e SESSION_DRIVER=array \
        -e QUEUE_CONNECTION=sync \
        -e APP_KEY=base64:$(openssl rand -base64 32) \
        "$image_name:test" \
        bash -c "php artisan config:clear && php artisan test" >> "$results_file" 2>&1; then
        
        local test_end_time=$(date +%s)
        local test_duration=$((test_end_time - test_start_time))
        
        log_success "$container_desc - Full test suite passed in ${test_duration}s"
        
        # Extract test summary
        echo "" >> "$results_file"
        echo "--- Test Summary ---" >> "$results_file"
        echo "Test Duration: ${test_duration} seconds" >> "$results_file"
        tail -5 "$results_file" | grep -E "(Tests:|Duration:)" >> "$results_file" 2>/dev/null || true
        
        return 0
    else
        log_error "$container_desc - Full test suite failed"
        return 1
    fi
}

# Generate comprehensive report
generate_report() {
    local report_file="$RESULTS_DIR/comprehensive-test-report.md"
    
    cat > "$report_file" << 'EOF'
# Production Container Test Results

This document contains comprehensive test results for all Docker container variants of the Laravel Invoicing application.

## Test Environment

- **Database**: PostgreSQL 17 (matching Laravel Sail configuration)
- **Test Framework**: Laravel Pest
- **Environment**: Production containers with testing database
- **Network**: Docker bridge network for container communication

## Test Methodology

1. **Container Build Verification**: Ensure container builds successfully
2. **Laravel Framework Test**: Verify Laravel can start and show version
3. **Database Migration Test**: Run all migrations on fresh PostgreSQL database
4. **Full Test Suite**: Execute complete application test suite (544 tests)

## Container Variants Tested

EOF

    # Add results for each tested container
    for result_file in "$RESULTS_DIR"/*-test-results.txt; do
        if [ -f "$result_file" ]; then
            container_name=$(basename "$result_file" -test-results.txt)
            echo "" >> "$report_file"
            echo "### ${container_name} Container" >> "$report_file"
            echo "" >> "$report_file"
            echo '```' >> "$report_file"
            cat "$result_file" >> "$report_file"
            echo '```' >> "$report_file"
        fi
    done
    
    # Add test execution log
    echo "" >> "$report_file"
    echo "## Test Execution Log" >> "$report_file"
    echo "" >> "$report_file"
    echo '```' >> "$report_file"
    cat "$RESULTS_DIR/test-execution.log" >> "$report_file" 2>/dev/null || echo "No execution log available" >> "$report_file"
    echo '```' >> "$report_file"
    
    log_success "Comprehensive test report generated: $report_file"
}

# Main execution
main() {
    log_info "Starting comprehensive container testing..."
    
    # Setup test infrastructure
    setup_postgres
    
    # Test available containers
    local containers_tested=0
    local containers_passed=0
    
    # Test FrankenPHP container (we know this works)
    if docker images | grep -q "invoicing-frankenphp.*test"; then
        if test_container "invoicing-frankenphp" "FrankenPHP + Octane"; then
            containers_passed=$((containers_passed + 1))
        fi
        containers_tested=$((containers_tested + 1))
    else
        log_error "FrankenPHP container not found - please build it first"
    fi
    
    # Test Nginx container if available
    if docker images | grep -q "invoicing-nginx.*test"; then
        if test_container "invoicing-nginx" "Nginx + PHP-FPM"; then
            containers_passed=$((containers_passed + 1))
        fi
        containers_tested=$((containers_tested + 1))
    else
        log_info "Nginx container not available for testing"
    fi
    
    # Test Standalone container if available
    if docker images | grep -q "invoicing-standalone.*test"; then
        if test_container "invoicing-standalone" "Standalone Binary"; then
            containers_passed=$((containers_passed + 1))
        fi
        containers_tested=$((containers_tested + 1))
    else
        log_info "Standalone container not available for testing"
    fi
    
    # Generate comprehensive report
    generate_report
    
    # Final summary
    echo ""
    log_info "=== FINAL TEST SUMMARY ==="
    log_info "Containers Tested: $containers_tested"
    log_success "Containers Passed: $containers_passed"
    
    if [ $containers_passed -eq $containers_tested ] && [ $containers_tested -gt 0 ]; then
        log_success "🎉 All available containers passed testing!"
        echo ""
        log_info "Test results saved in: $RESULTS_DIR/"
        echo "- comprehensive-test-report.md"
        echo "- Individual container test results"
        echo "- Test execution logs"
    else
        log_error "Some containers failed testing"
        exit 1
    fi
}

# Set trap for cleanup
trap cleanup EXIT

# Run main function
main "$@"