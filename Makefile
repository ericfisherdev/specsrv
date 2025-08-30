# SpecSrv - Task Management System Makefile

.PHONY: help install start stop restart build clean test lint fix-cs analyze db-create db-migrate db-seed db-reset docker-build docker-up docker-down docker-logs dev prod devup devdown hot-reload hot-reload-simple hot-reload-advanced hot-reload-dev hot-reload-stop hot-reload-logs webpack-watch webpack-watch-verbose

# Colors for output
YELLOW := \033[33m
GREEN := \033[32m
BLUE := \033[34m
RESET := \033[0m

help: ## Show this help message
	@echo "$(BLUE)SpecSrv - Task Management System$(RESET)"
	@echo ""
	@echo "$(YELLOW)Available commands:$(RESET)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-20s$(RESET) %s\n", $$1, $$2}'

# Installation and Setup
install: ## Install project dependencies
	@echo "$(YELLOW)Installing dependencies...$(RESET)"
	cd backend && composer install
	@echo "$(GREEN)Dependencies installed successfully!$(RESET)"

# Development Commands
start: ## Start development server
	@echo "$(YELLOW)Starting development server...$(RESET)"
	cd backend && php -S localhost:8000 -t public/ > /dev/null 2>&1 & echo $$! > .server.pid
	@echo "$(GREEN)Development server started at http://localhost:8000$(RESET)"

stop: ## Stop development server
	@echo "$(YELLOW)Stopping development server...$(RESET)"
	@if [ -f backend/.server.pid ]; then \
		kill `cat backend/.server.pid` 2>/dev/null || true; \
		rm -f backend/.server.pid; \
	else \
		pkill -f "php -S localhost:8000" 2>/dev/null || true; \
	fi
	@echo "$(GREEN)Development server stopped$(RESET)"

restart: stop start ## Restart development server

devup: start ## Alias for starting development server (same as start)

devdown: stop ## Alias for stopping development server (same as stop)

# Code Quality
test: ## Run all tests
	@echo "$(YELLOW)Running tests...$(RESET)"
	cd backend && php bin/phpunit
	@echo "$(GREEN)Tests completed$(RESET)"

lint: ## Run code linting
	@echo "$(YELLOW)Running code analysis...$(RESET)"
	cd backend && vendor/bin/phpstan analyse src --level=8
	@echo "$(GREEN)Code analysis completed$(RESET)"

fix-cs: ## Fix code style issues
	@echo "$(YELLOW)Fixing code style...$(RESET)"
	cd backend && vendor/bin/php-cs-fixer fix
	@echo "$(GREEN)Code style fixed$(RESET)"

analyze: lint fix-cs ## Run all code quality checks

# Database Commands
db-create: ## Create database
	@echo "$(YELLOW)Creating database...$(RESET)"
	cd backend && php bin/console doctrine:database:create --if-not-exists
	@echo "$(GREEN)Database created$(RESET)"

db-migrate: ## Run database migrations
	@echo "$(YELLOW)Running migrations...$(RESET)"
	cd backend && php bin/console doctrine:migrations:migrate --no-interaction
	@echo "$(GREEN)Migrations completed$(RESET)"

db-seed: ## Seed database with test data
	@echo "$(YELLOW)Seeding database...$(RESET)"
	cd backend && php bin/console doctrine:fixtures:load --no-interaction
	@echo "$(GREEN)Database seeded$(RESET)"

db-reset: ## Reset database (drop, create, migrate, seed)
	@echo "$(YELLOW)Resetting database...$(RESET)"
	cd backend && php bin/console doctrine:database:drop --force --if-exists
	$(MAKE) db-create
	$(MAKE) db-migrate
	$(MAKE) db-seed
	@echo "$(GREEN)Database reset completed$(RESET)"

# Docker Commands
docker-build: ## Build Docker containers
	@echo "$(YELLOW)Building Docker containers...$(RESET)"
	docker-compose build
	@echo "$(GREEN)Docker containers built$(RESET)"

docker-up: ## Start Docker containers
	@echo "$(YELLOW)Starting Docker containers...$(RESET)"
	docker-compose up -d
	@echo "$(GREEN)Docker containers started$(RESET)"

docker-down: ## Stop Docker containers
	@echo "$(YELLOW)Stopping Docker containers...$(RESET)"
	docker-compose down
	@echo "$(GREEN)Docker containers stopped$(RESET)"

docker-logs: ## Show Docker container logs
	docker-compose logs -f

docker-restart: docker-down docker-up ## Restart Docker containers

# Development Environment
dev: docker-up db-migrate ## Start development environment
	@echo "$(GREEN)Development environment ready!$(RESET)"
	@echo "$(BLUE)Web: http://localhost:8080$(RESET)"
	@echo "$(BLUE)API: http://localhost:8080/api$(RESET)"

# Production Commands
prod-build: ## Build production Docker images
	@echo "$(YELLOW)Building production images...$(RESET)"
	docker-compose -f docker-compose.prod.yml build
	@echo "$(GREEN)Production images built$(RESET)"

prod-up: ## Start production environment
	@echo "$(YELLOW)Starting production environment...$(RESET)"
	docker-compose -f docker-compose.prod.yml up -d
	@echo "$(GREEN)Production environment started$(RESET)"

prod-down: ## Stop production environment
	@echo "$(YELLOW)Stopping production environment...$(RESET)"
	docker-compose -f docker-compose.prod.yml down
	@echo "$(GREEN)Production environment stopped$(RESET)"

# Maintenance
clean: ## Clean up temporary files and caches
	@echo "$(YELLOW)Cleaning up...$(RESET)"
	cd backend && php bin/console cache:clear
	cd backend && rm -rf var/cache/* var/log/* var/sessions/*
	docker system prune -f
	@echo "$(GREEN)Cleanup completed$(RESET)"

# API Testing
api-test: ## Run API endpoint tests
	@echo "$(YELLOW)Testing API endpoints...$(RESET)"
	cd backend && php bin/phpunit tests/Api/
	@echo "$(GREEN)API tests completed$(RESET)"

# Security
security-check: ## Check for security vulnerabilities
	@echo "$(YELLOW)Checking for security vulnerabilities...$(RESET)"
	cd backend && composer audit
	@echo "$(GREEN)Security check completed$(RESET)"

# Full Setup
setup: install db-create db-migrate db-seed ## Complete project setup
	@echo "$(GREEN)Project setup completed!$(RESET)"
	@echo "$(BLUE)Run 'make start' to start the development server$(RESET)"

# Quick Development Start
quick-start: docker-up ## Quick start for development
	@echo "$(GREEN)Quick start completed!$(RESET)"
	@echo "$(BLUE)Application running at http://localhost:8080$(RESET)"

# Health Check
health: ## Check application health
	@echo "$(YELLOW)Checking application health...$(RESET)"
	@curl -s http://localhost:8080/api/health || echo "Application not responding"
	@echo "$(GREEN)Health check completed$(RESET)"

# Hot Reloading Development Environment
hot-reload: hot-reload-simple ## Start hot reloading development (default: simple method)

hot-reload-simple: ## Start simple hot reloading (production container + local webpack watch)
	@echo "$(YELLOW)Starting simple hot reloading development...$(RESET)"
	@echo "$(BLUE)Step 1: Starting production Docker container...$(RESET)"
	docker-compose -f docker-compose.fixed.yml up specsrv-app -d
	@echo "$(BLUE)Step 2: Starting webpack in watch mode...$(RESET)"
	@echo "$(YELLOW)Note: Webpack will run in the foreground. Press Ctrl+C to stop.$(RESET)"
	@echo "$(GREEN)🔥 Hot reloading ready!$(RESET)"
	@echo "$(BLUE)Frontend: Auto-compiles CSS/JS on changes$(RESET)"
	@echo "$(BLUE)Backend: PHP/Twig changes reflect immediately$(RESET)"
	@echo "$(BLUE)URL: http://localhost:8080$(RESET)"
	@echo ""
	@echo "$(GREEN)=== WEBPACK WATCH OUTPUT ====$(RESET)"
	cd backend && npm run watch-quiet

hot-reload-advanced: ## Start advanced hot reloading (full dev environment with webpack dev server)
	@echo "$(YELLOW)Starting advanced hot reloading development...$(RESET)"
	@echo "$(BLUE)Building and starting full development environment...$(RESET)"
	docker-compose -f docker-compose.dev.yml up -d --build
	@echo "$(GREEN)🔥 Advanced hot reloading ready!$(RESET)"
	@echo "$(BLUE)Main URL (Webpack Dev Server): http://localhost:8000$(RESET)"
	@echo "$(BLUE)PHP Direct Access: http://localhost:8001$(RESET)"
	@echo "$(BLUE)Features: Hot Module Replacement, Proxying, Source Maps$(RESET)"

hot-reload-dev: ## Start development profile hot reloading
	@echo "$(YELLOW)Starting development profile hot reloading...$(RESET)"
	docker-compose -f docker-compose.fixed.yml --profile dev up specsrv-dev-simple -d --build
	@echo "$(GREEN)🔥 Development profile hot reloading ready!$(RESET)"
	@echo "$(BLUE)URL: http://localhost:8001$(RESET)"
	@echo "$(YELLOW)Run 'make webpack-watch' in another terminal for frontend hot reloading$(RESET)"

webpack-watch: ## Start webpack in watch mode (run in separate terminal)
	@echo "$(YELLOW)Starting webpack watch mode (quiet)...$(RESET)"
	@echo "$(BLUE)Watching for changes in assets/ directory...$(RESET)"
	@echo "$(BLUE)Note: Using quiet mode to reduce console spam. Use 'make webpack-watch-verbose' for full output.$(RESET)"
	@echo "$(GREEN)=== WEBPACK WATCH OUTPUT ====$(RESET)"
	cd backend && npm run watch-quiet

webpack-watch-verbose: ## Start webpack in watch mode with verbose output
	@echo "$(YELLOW)Starting webpack watch mode (verbose)...$(RESET)"
	@echo "$(BLUE)Watching for changes in assets/ directory...$(RESET)"
	@echo "$(GREEN)=== WEBPACK WATCH OUTPUT (VERBOSE) ====$(RESET)"
	cd backend && npm run watch

hot-reload-stop: ## Stop all hot reloading development environments
	@echo "$(YELLOW)Stopping hot reloading environments...$(RESET)"
	@echo "$(BLUE)Stopping simple hot reload containers...$(RESET)"
	-docker-compose -f docker-compose.fixed.yml down 2>/dev/null
	@echo "$(BLUE)Stopping advanced hot reload containers...$(RESET)"
	-docker-compose -f docker-compose.dev.yml down 2>/dev/null
	@echo "$(BLUE)Stopping development profile containers...$(RESET)"
	-docker-compose -f docker-compose.fixed.yml --profile dev down 2>/dev/null
	@echo "$(BLUE)Killing any remaining webpack processes...$(RESET)"
	-pkill -f "npm run watch" 2>/dev/null || true
	-pkill -f "webpack" 2>/dev/null || true
	@echo "$(GREEN)All hot reloading environments stopped$(RESET)"

hot-reload-logs: ## Show logs from hot reloading containers
	@echo "$(YELLOW)Available log options:$(RESET)"
	@echo "$(BLUE)1. Simple method logs:$(RESET) make hot-reload-logs-simple"
	@echo "$(BLUE)2. Advanced method logs:$(RESET) make hot-reload-logs-advanced" 
	@echo "$(BLUE)3. Development profile logs:$(RESET) make hot-reload-logs-dev"

hot-reload-logs-simple: ## Show logs from simple hot reloading setup
	@echo "$(YELLOW)Showing simple hot reload logs...$(RESET)"
	docker-compose -f docker-compose.fixed.yml logs -f specsrv-app

hot-reload-logs-advanced: ## Show logs from advanced hot reloading setup
	@echo "$(YELLOW)Showing advanced hot reload logs...$(RESET)"
	docker-compose -f docker-compose.dev.yml logs -f

hot-reload-logs-dev: ## Show logs from development profile hot reloading
	@echo "$(YELLOW)Showing development profile logs...$(RESET)"
	docker-compose -f docker-compose.fixed.yml --profile dev logs -f

# Hot Reloading Help
hot-reload-help: ## Show detailed hot reloading help
	@echo "$(BLUE)🔥 SpecSrv Hot Reloading Guide$(RESET)"
	@echo ""
	@echo "$(YELLOW)Available Methods:$(RESET)"
	@echo "$(GREEN)1. Simple (Recommended):$(RESET)"
	@echo "   make hot-reload-simple"
	@echo "   - Production container + local webpack watch"
	@echo "   - Fastest setup, most reliable"
	@echo "   - URL: http://localhost:8080"
	@echo ""
	@echo "$(GREEN)2. Advanced:$(RESET)"
	@echo "   make hot-reload-advanced"
	@echo "   - Full webpack dev server with HMR"
	@echo "   - Complete development environment"
	@echo "   - URL: http://localhost:8000 (webpack dev server)"
	@echo ""
	@echo "$(GREEN)3. Development Profile:$(RESET)"
	@echo "   make hot-reload-dev"
	@echo "   - Docker development profile"
	@echo "   - URL: http://localhost:8001"
	@echo ""
	@echo "$(YELLOW)Management:$(RESET)"
	@echo "   make hot-reload-stop     # Stop all environments"
	@echo "   make hot-reload-logs     # View logs options"
	@echo "   make webpack-watch       # Start webpack watch separately (quiet mode)"
	@echo "   make webpack-watch-verbose # Start webpack watch with full output"
	@echo ""
	@echo "$(YELLOW)Features:$(RESET)"
	@echo "   ✅ Frontend: Auto-compile CSS/JS on file changes"
	@echo "   ✅ Backend: PHP/Twig changes reflect immediately"
	@echo "   ✅ Database: Persistent across restarts"
	@echo "   ✅ Debugging: Source maps and dev tools available"
	@echo ""
	@echo "$(BLUE)For detailed setup instructions, see:$(RESET)"
	@echo "   - HOT-RELOADING-SETUP.md"
	@echo "   - DOCKER-DEV-README.md"