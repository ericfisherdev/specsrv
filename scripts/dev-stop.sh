#!/bin/bash

# specsrv Development Environment Stop Script
# This script stops the development environment gracefully

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

# Main function to stop development environment
stop_dev_environment() {
    print_status "Stopping specsrv development environment..."
    
    # Check if docker-compose.dev.yml exists
    if [ ! -f "docker-compose.dev.yml" ]; then
        print_error "docker-compose.dev.yml not found. Make sure you're in the project root directory."
        exit 1
    fi
    
    # Stop and remove containers
    if docker-compose -f docker-compose.dev.yml ps -q | grep -q .; then
        print_status "Stopping running containers..."
        docker-compose -f docker-compose.dev.yml down
        
        if [ "${1:-}" = "--volumes" ] || [ "${1:-}" = "-v" ]; then
            print_status "Removing volumes as requested..."
            docker-compose -f docker-compose.dev.yml down -v
        fi
        
        print_success "Development environment stopped successfully"
    else
        print_warning "No running containers found for specsrv development environment"
    fi
    
    # Show status
    print_status "Container status:"
    docker-compose -f docker-compose.dev.yml ps
}

# Show usage information
show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Stop the specsrv development environment"
    echo ""
    echo "Options:"
    echo "  --help, -h       Show this help message"
    echo "  --volumes, -v    Also remove volumes (WARNING: This will delete data!)"
    echo ""
    echo "Examples:"
    echo "  $0               Stop containers only"
    echo "  $0 --volumes     Stop containers and remove volumes"
}

# Parse command line arguments
case "${1:-}" in
    --help|-h)
        show_usage
        exit 0
        ;;
    --volumes|-v)
        print_warning "This will remove ALL data volumes!"
        read -p "Are you sure you want to continue? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            stop_dev_environment --volumes
        else
            print_status "Operation cancelled"
            exit 0
        fi
        ;;
    "")
        stop_dev_environment
        ;;
    *)
        print_error "Unknown option: $1"
        show_usage
        exit 1
        ;;
esac