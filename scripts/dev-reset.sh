#!/bin/bash

# specsrv Development Environment Reset Script
# This script completely resets the development environment

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Show usage information
show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Reset the specsrv development environment"
    echo ""
    echo "This script will:"
    echo "  1. Stop all running containers"
    echo "  2. Remove containers and volumes"
    echo "  3. Rebuild images from scratch"
    echo "  4. Restart the environment"
    echo ""
    echo "Options:"
    echo "  --help, -h        Show this help message"
    echo "  --quick, -q       Quick reset (keep dependencies installed)"
    echo "  --hard            Hard reset (also remove node_modules and vendor)"
    echo "  --database-only   Reset only the database"
    echo ""
    echo "WARNING: This will delete all data in the development database!"
}

# Reset database only
reset_database_only() {
    print_status "Resetting development database only..."
    
    # Stop database container
    docker-compose -f docker-compose.dev.yml stop specsrv-postgres || true
    
    # Remove database volume
    docker-compose -f docker-compose.dev.yml down -v specsrv-postgres || true
    
    # Start database container
    docker-compose -f docker-compose.dev.yml up -d specsrv-postgres
    
    # Wait for database to be ready
    print_status "Waiting for database to be ready..."
    sleep 10
    
    # Run migrations
    docker-compose -f docker-compose.dev.yml exec specsrv-backend-dev php bin/console doctrine:migrations:migrate --no-interaction
    
    print_success "Database reset completed"
}

# Hard reset - remove all dependencies
hard_reset() {
    print_status "Performing hard reset - removing all dependencies..."
    
    # Remove node_modules
    if [ -d "frontend/node_modules" ]; then
        print_status "Removing frontend/node_modules..."
        rm -rf frontend/node_modules
    fi
    
    # Remove vendor directory
    if [ -d "backend/vendor" ]; then
        print_status "Removing backend/vendor..."
        rm -rf backend/vendor
    fi
    
    # Remove composer lock files
    if [ -f "backend/composer.lock" ]; then
        print_status "Removing backend/composer.lock..."
        rm -f backend/composer.lock
    fi
    
    # Remove package lock files
    if [ -f "frontend/package-lock.json" ]; then
        print_status "Removing frontend/package-lock.json..."
        rm -f frontend/package-lock.json
    fi
    
    if [ -f "frontend/yarn.lock" ]; then
        print_status "Removing frontend/yarn.lock..."
        rm -f frontend/yarn.lock
    fi
    
    print_success "Hard reset preparation completed"
}

# Main reset function
full_reset() {
    local quick_mode="${1:-false}"
    local hard_mode="${2:-false}"
    
    print_warning "⚠️  This will completely reset your development environment!"
    print_warning "⚠️  All data in the development database will be lost!"
    echo ""
    
    if [[ $quick_mode != "true" ]]; then
        read -p "Are you sure you want to continue? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_status "Reset cancelled"
            exit 0
        fi
    fi
    
    print_status "🔄 Starting full development environment reset..."
    
    # Hard reset if requested
    if [[ $hard_mode == "true" ]]; then
        hard_reset
    fi
    
    # Stop all containers
    print_status "Stopping all containers..."
    docker-compose -f docker-compose.dev.yml down || true
    
    # Remove all containers, networks, and volumes
    print_status "Removing containers, networks, and volumes..."
    docker-compose -f docker-compose.dev.yml down -v --remove-orphans || true
    
    # Remove images to force rebuild
    print_status "Removing Docker images for fresh rebuild..."
    docker-compose -f docker-compose.dev.yml down --rmi all || true
    
    # Clean up dangling images and volumes
    print_status "Cleaning up dangling Docker resources..."
    docker system prune -f || true
    
    # Restart the environment
    print_status "Rebuilding and starting fresh environment..."
    if [[ $quick_mode == "true" ]]; then
        ./scripts/dev-setup.sh --quick
    else
        ./scripts/dev-setup.sh
    fi
    
    print_success "🎉 Development environment reset completed!"
}

# Parse command line arguments
case "${1:-}" in
    --help|-h)
        show_usage
        exit 0
        ;;
    --quick|-q)
        full_reset true false
        ;;
    --hard)
        full_reset false true
        ;;
    --database-only)
        reset_database_only
        ;;
    "")
        full_reset false false
        ;;
    *)
        print_error "Unknown option: $1"
        show_usage
        exit 1
        ;;
esac