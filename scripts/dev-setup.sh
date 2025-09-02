#!/bin/bash

# specsrv Development Environment Setup Script
# This script sets up the complete development environment for both backend and frontend

set -e

echo "🚀 Starting specsrv development environment setup..."

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

# Check if Docker and Docker Compose are installed
check_docker() {
    if ! command -v docker &> /dev/null; then
        print_error "Docker is not installed. Please install Docker first."
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi
    
    print_success "Docker and Docker Compose are installed"
}

# Install backend dependencies
setup_backend() {
    print_status "Setting up backend dependencies..."
    cd backend
    
    if [ ! -f "composer.json" ]; then
        print_error "composer.json not found in backend directory"
        exit 1
    fi
    
    # Install composer dependencies
    if command -v composer &> /dev/null; then
        print_status "Installing PHP dependencies with Composer..."
        composer install --no-scripts
    else
        print_warning "Composer not found locally, will use Docker container for dependencies"
    fi
    
    cd ..
    print_success "Backend setup completed"
}

# Install frontend dependencies
setup_frontend() {
    print_status "Setting up frontend dependencies..."
    cd frontend
    
    if [ ! -f "package.json" ]; then
        print_error "package.json not found in frontend directory"
        exit 1
    fi
    
    # Install npm dependencies
    if command -v npm &> /dev/null; then
        print_status "Installing Node.js dependencies with npm..."
        npm install
    else
        print_warning "npm not found locally, will use Docker container for dependencies"
    fi
    
    cd ..
    print_success "Frontend setup completed"
}

# Create environment files if they don't exist
setup_env_files() {
    print_status "Setting up environment files..."
    
    # Backend .env file
    if [ ! -f "backend/.env.local" ]; then
        print_status "Creating backend .env.local file..."
        cat > backend/.env.local << EOF
APP_ENV=dev
APP_DEBUG=1
DATABASE_URL="postgresql://specsrv-db-user:specsrv1234@localhost:5433/specsrv_dev?serverVersion=15&charset=utf8"
CORS_ALLOW_ORIGIN="http://localhost:3000"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase_here
EOF
        print_success "Created backend/.env.local"
    fi
    
    # Frontend environment variables
    if [ ! -f "frontend/.env.development" ]; then
        print_status "Creating frontend .env.development file..."
        cat > frontend/.env.development << EOF
NODE_ENV=development
API_BASE_URL=http://localhost:8080/api/v1
APP_NAME=SpecSrv
EOF
        print_success "Created frontend/.env.development"
    fi
}

# Start development environment
start_dev_environment() {
    print_status "Starting development environment with Docker Compose..."
    
    # Stop any running containers first
    docker-compose -f docker-compose.dev.yml down 2>/dev/null || true
    
    # Build and start containers
    docker-compose -f docker-compose.dev.yml up --build -d
    
    print_success "Development environment started!"
    print_status "Services running:"
    print_status "  - Backend API: http://localhost:8080"
    print_status "  - Frontend: http://localhost:3000"
    print_status "  - PostgreSQL: localhost:5433"
}

# Wait for services to be ready
wait_for_services() {
    print_status "Waiting for services to be ready..."
    
    # Wait for backend to be ready
    timeout=60
    count=0
    while ! curl -s http://localhost:8080/health >/dev/null 2>&1; do
        if [ $count -ge $timeout ]; then
            print_error "Backend service did not start within $timeout seconds"
            exit 1
        fi
        sleep 1
        count=$((count + 1))
        if [ $((count % 10)) -eq 0 ]; then
            print_status "Still waiting for backend... ($count/$timeout)"
        fi
    done
    
    print_success "Backend service is ready"
    
    # Wait for frontend to be ready (if running in dev mode)
    if curl -s http://localhost:3000 >/dev/null 2>&1; then
        print_success "Frontend service is ready"
    else
        print_warning "Frontend service may still be starting up"
    fi
}

# Run database migrations
run_migrations() {
    print_status "Running database migrations..."
    
    # Wait a bit more for database to be fully ready
    sleep 5
    
    docker-compose -f docker-compose.dev.yml exec specsrv-backend-dev php bin/console doctrine:migrations:migrate --no-interaction || {
        print_warning "Migrations failed, database might not be ready yet"
        print_status "You can run migrations manually with: docker-compose -f docker-compose.dev.yml exec specsrv-backend-dev php bin/console doctrine:migrations:migrate --no-interaction"
    }
}

# Show final status
show_status() {
    print_success "🎉 Development environment is ready!"
    echo ""
    print_status "Available commands:"
    print_status "  • View logs: docker-compose -f docker-compose.dev.yml logs -f"
    print_status "  • Stop environment: docker-compose -f docker-compose.dev.yml down"
    print_status "  • Restart services: docker-compose -f docker-compose.dev.yml restart"
    print_status "  • Run backend commands: docker-compose -f docker-compose.dev.yml exec specsrv-backend-dev <command>"
    print_status "  • Run frontend commands: docker-compose -f docker-compose.dev.yml exec specsrv-frontend-dev <command>"
    echo ""
    print_status "Frontend development server: http://localhost:3000"
    print_status "Backend API: http://localhost:8080/api/v1"
    print_status "API Health check: http://localhost:8080/health"
    echo ""
    print_warning "If this is your first time running the setup, you might need to:"
    print_warning "  1. Generate JWT keys (if using JWT authentication)"
    print_warning "  2. Load initial data or fixtures"
    print_warning "  3. Configure your database settings in backend/.env.local"
}

# Main execution
main() {
    echo "🏗️  specsrv Development Environment Setup"
    echo "========================================="
    
    check_docker
    setup_env_files
    setup_backend
    setup_frontend
    start_dev_environment
    wait_for_services
    run_migrations
    show_status
}

# Parse command line arguments
case "${1:-}" in
    --help|-h)
        echo "Usage: $0 [OPTIONS]"
        echo ""
        echo "Options:"
        echo "  --help, -h     Show this help message"
        echo "  --quick, -q    Skip dependency installation (assumes already installed)"
        echo ""
        exit 0
        ;;
    --quick|-q)
        print_status "Running quick setup (skipping dependency installation)..."
        check_docker
        setup_env_files
        start_dev_environment
        wait_for_services
        run_migrations
        show_status
        ;;
    *)
        main
        ;;
esac