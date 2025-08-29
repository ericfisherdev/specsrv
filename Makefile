# SpecSrv - Task Management System Makefile

.PHONY: help install start stop restart build clean test lint fix-cs analyze db-create db-migrate db-seed db-reset docker-build docker-up docker-down docker-logs dev prod

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
	cd backend && symfony server:start -d
	@echo "$(GREEN)Development server started at http://localhost:8000$(RESET)"

stop: ## Stop development server
	@echo "$(YELLOW)Stopping development server...$(RESET)"
	cd backend && symfony server:stop
	@echo "$(GREEN)Development server stopped$(RESET)"

restart: stop start ## Restart development server

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