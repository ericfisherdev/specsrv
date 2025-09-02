#!/bin/bash

# specsrv Development Environment Logs Script
# This script provides easy access to development environment logs

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
    echo "Usage: $0 [SERVICE] [OPTIONS]"
    echo ""
    echo "View logs for specsrv development environment services"
    echo ""
    echo "Services:"
    echo "  backend      Backend API service logs"
    echo "  frontend     Frontend service logs"
    echo "  postgres     Database service logs"
    echo "  all          All services (default)"
    echo ""
    echo "Options:"
    echo "  --help, -h   Show this help message"
    echo "  --follow, -f Follow log output (tail -f mode)"
    echo "  --tail, -t N Show last N lines (default: 100)"
    echo ""
    echo "Examples:"
    echo "  $0                    Show all logs (last 100 lines)"
    echo "  $0 backend --follow   Follow backend logs"
    echo "  $0 frontend -t 50     Show last 50 lines of frontend logs"
    echo "  $0 all -f             Follow all service logs"
}

# Main function to show logs
show_logs() {
    local service="${1:-all}"
    local follow_flag=""
    local tail_lines="100"
    
    # Parse additional arguments
    shift || true  # Remove service argument
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            --follow|-f)
                follow_flag="-f"
                tail_lines=""  # When following, don't limit lines
                shift
                ;;
            --tail|-t)
                if [[ -n $2 && $2 =~ ^[0-9]+$ ]]; then
                    tail_lines="$2"
                    shift 2
                else
                    print_error "Invalid tail value. Must be a number."
                    exit 1
                fi
                ;;
            *)
                print_error "Unknown option: $1"
                show_usage
                exit 1
                ;;
        esac
    done
    
    # Check if docker-compose.dev.yml exists
    if [ ! -f "docker-compose.dev.yml" ]; then
        print_error "docker-compose.dev.yml not found. Make sure you're in the project root directory."
        exit 1
    fi
    
    # Map service names to container names
    local container_service=""
    case $service in
        backend)
            container_service="specsrv-backend-dev"
            print_status "Showing backend service logs..."
            ;;
        frontend)
            container_service="specsrv-frontend-dev"
            print_status "Showing frontend service logs..."
            ;;
        postgres|database|db)
            container_service="specsrv-postgres"
            print_status "Showing database service logs..."
            ;;
        all)
            print_status "Showing all service logs..."
            ;;
        *)
            print_error "Unknown service: $service"
            print_status "Available services: backend, frontend, postgres, all"
            exit 1
            ;;
    esac
    
    # Build docker-compose logs command
    local cmd="docker-compose -f docker-compose.dev.yml logs"
    
    if [[ -n $follow_flag ]]; then
        cmd="$cmd $follow_flag"
    fi
    
    if [[ -n $tail_lines ]]; then
        cmd="$cmd --tail=$tail_lines"
    fi
    
    if [[ $service != "all" ]]; then
        cmd="$cmd $container_service"
    fi
    
    # Execute the command
    if [[ -n $follow_flag ]]; then
        print_status "Following logs... Press Ctrl+C to exit"
        echo ""
    fi
    
    eval "$cmd"
}

# Check if any containers are running
check_containers() {
    if ! docker-compose -f docker-compose.dev.yml ps -q | grep -q .; then
        print_warning "No containers are currently running."
        print_status "Start the development environment with: ./scripts/dev-setup.sh"
        exit 1
    fi
}

# Parse command line arguments
case "${1:-}" in
    --help|-h)
        show_usage
        exit 0
        ;;
    "")
        check_containers
        show_logs "all"
        ;;
    backend|frontend|postgres|database|db|all)
        check_containers
        show_logs "$@"
        ;;
    *)
        if [[ $1 == --* ]] || [[ $1 == -* ]]; then
            # First argument is an option, treat as 'all' service
            check_containers
            show_logs "all" "$@"
        else
            print_error "Unknown service: $1"
            show_usage
            exit 1
        fi
        ;;
esac