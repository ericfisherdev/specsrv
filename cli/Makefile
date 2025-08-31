# SpecSrv CLI Makefile
# Build configuration
APP_NAME = specsrv
VERSION ?= 1.0.0
BUILD_DATE = $(shell date -u +"%Y-%m-%dT%H:%M:%SZ")
GIT_COMMIT = $(shell git rev-parse --short HEAD 2>/dev/null || echo "unknown")
GIT_BRANCH = $(shell git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "unknown")

# Go configuration
GO = go
GOARCH = $(shell $(GO) env GOARCH)
GOOS = $(shell $(GO) env GOOS)
GOPROXY = $(shell $(GO) env GOPROXY)

# Build flags
LDFLAGS = -ldflags "-s -w \
	-X 'github.com/ericfisherdev/specsrv/cli/internal/commands.version=$(VERSION)' \
	-X 'github.com/ericfisherdev/specsrv/cli/internal/commands.buildDate=$(BUILD_DATE)' \
	-X 'github.com/ericfisherdev/specsrv/cli/internal/commands.gitCommit=$(GIT_COMMIT)'"

# Directories
BUILD_DIR = build
DIST_DIR = dist
DOCS_DIR = docs

# Platforms for cross-compilation
PLATFORMS = \
	linux/amd64 \
	linux/arm64 \
	darwin/amd64 \
	darwin/arm64 \
	windows/amd64 \
	windows/arm64

# Default target
.PHONY: all
all: clean build

# Build for current platform
.PHONY: build
build:
	@echo "Building $(APP_NAME) v$(VERSION) for $(GOOS)/$(GOARCH)..."
	@mkdir -p $(BUILD_DIR)
	$(GO) build $(LDFLAGS) -o $(BUILD_DIR)/$(APP_NAME)$(GOEXE) ./main.go

# Build for all platforms
.PHONY: build-all
build-all: clean
	@echo "Building $(APP_NAME) v$(VERSION) for all platforms..."
	@mkdir -p $(DIST_DIR)
	@for platform in $(PLATFORMS); do \
		GOOS=$$(echo $$platform | cut -d'/' -f1); \
		GOARCH=$$(echo $$platform | cut -d'/' -f2); \
		output_name=$(APP_NAME); \
		if [ $$GOOS = "windows" ]; then output_name=$(APP_NAME).exe; fi; \
		output_path=$(DIST_DIR)/$(APP_NAME)-$$GOOS-$$GOARCH; \
		mkdir -p $$output_path; \
		echo "Building for $$GOOS/$$GOARCH..."; \
		env GOOS=$$GOOS GOARCH=$$GOARCH $(GO) build $(LDFLAGS) -o $$output_path/$$output_name ./main.go; \
		if [ $$? -eq 0 ]; then \
			echo "✓ Built $$output_path/$$output_name"; \
		else \
			echo "✗ Failed to build for $$GOOS/$$GOARCH"; \
		fi; \
	done

# Create release packages
.PHONY: package
package: build-all
	@echo "Creating release packages..."
	@cd $(DIST_DIR) && for dir in */; do \
		if [ -d "$$dir" ]; then \
			echo "Packaging $$dir..."; \
			tar -czf "$${dir%/}.tar.gz" "$$dir"; \
		fi; \
	done
	@echo "Packages created in $(DIST_DIR)/"

# Install locally
.PHONY: install
install: build
	@echo "Installing $(APP_NAME)..."
	@GOBIN=$$(go env GOBIN); \
	if [ -z "$$GOBIN" ]; then \
		GOBIN=$$(go env GOPATH)/bin; \
	fi; \
	GOEXE=$$(go env GOEXE); \
	DEST=$$GOBIN/$(APP_NAME)$$GOEXE; \
	cp $(BUILD_DIR)/$(APP_NAME)$(GOEXE) $$DEST; \
	echo "✓ $(APP_NAME) installed to $$DEST"

# Clean build artifacts
.PHONY: clean
clean:
	@echo "Cleaning build artifacts..."
	@rm -rf $(BUILD_DIR) $(DIST_DIR)
	@echo "✓ Clean completed"

# Run tests
.PHONY: test
test:
	@echo "Running tests..."
	$(GO) test -v -race ./...

# Run tests with coverage
.PHONY: test-coverage
test-coverage:
	@echo "Running tests with coverage..."
	$(GO) test -v -race -coverprofile=coverage.out ./...
	$(GO) tool cover -html=coverage.out -o coverage.html
	@echo "Coverage report generated: coverage.html"

# Format code
.PHONY: fmt
fmt:
	@echo "Formatting code..."
	$(GO) fmt ./...
	@echo "✓ Code formatted"

# Run linter
.PHONY: lint
lint:
	@echo "Running linter..."
	@if command -v golangci-lint >/dev/null; then \
		golangci-lint run ./...; \
	else \
		echo "golangci-lint not found, skipping lint"; \
	fi

# Download dependencies
.PHONY: deps
deps:
	@echo "Downloading dependencies..."
	$(GO) mod download
	$(GO) mod tidy
	@echo "✓ Dependencies updated"

# Generate documentation
.PHONY: docs
docs: build
	@echo "Generating documentation..."
	@mkdir -p $(DOCS_DIR)
	@./$(BUILD_DIR)/$(APP_NAME)$(GOEXE) completion bash > $(DOCS_DIR)/$(APP_NAME)-completion.bash
	@./$(BUILD_DIR)/$(APP_NAME)$(GOEXE) completion zsh > $(DOCS_DIR)/$(APP_NAME)-completion.zsh
	@./$(BUILD_DIR)/$(APP_NAME)$(GOEXE) completion fish > $(DOCS_DIR)/$(APP_NAME)-completion.fish
	@./$(BUILD_DIR)/$(APP_NAME)$(GOEXE) completion powershell > $(DOCS_DIR)/$(APP_NAME)-completion.ps1
	@echo "✓ Documentation generated in $(DOCS_DIR)/"

# Development build (with debug info)
.PHONY: dev
dev:
	@echo "Building development version..."
	@mkdir -p $(BUILD_DIR)
	$(GO) build -race -o $(BUILD_DIR)/$(APP_NAME)-dev ./main.go

# Quick development cycle
.PHONY: quick
quick: fmt build

# Run the built binary
.PHONY: run
run: build
	@./$(BUILD_DIR)/$(APP_NAME) $(ARGS)

# Check for security vulnerabilities
.PHONY: security
security:
	@echo "Checking for security vulnerabilities..."
	@if command -v govulncheck >/dev/null; then \
		govulncheck ./...; \
	else \
		echo "govulncheck not found, install with: go install golang.org/x/vuln/cmd/govulncheck@latest"; \
	fi

# Development setup
.PHONY: setup
setup:
	@echo "Setting up development environment..."
	$(GO) install golang.org/x/tools/cmd/goimports@latest
	$(GO) install github.com/golangci/golangci-lint/cmd/golangci-lint@latest
	$(GO) install golang.org/x/vuln/cmd/govulncheck@latest
	@echo "✓ Development tools installed"

# Show help
.PHONY: help
help:
	@echo "SpecSrv CLI Build System"
	@echo ""
	@echo "Available targets:"
	@echo "  all           - Clean and build for current platform (default)"
	@echo "  build         - Build for current platform"
	@echo "  build-all     - Build for all supported platforms"
	@echo "  package       - Create release packages (tar.gz)"
	@echo "  install       - Install locally to GOPATH/bin"
	@echo "  clean         - Remove build artifacts"
	@echo "  test          - Run tests"
	@echo "  test-coverage - Run tests with coverage report"
	@echo "  fmt           - Format code"
	@echo "  lint          - Run linter"
	@echo "  deps          - Download and tidy dependencies"
	@echo "  docs          - Generate completion scripts"
	@echo "  dev           - Build development version with debug info"
	@echo "  quick         - Format and build quickly"
	@echo "  run           - Build and run (use ARGS='...' for arguments)"
	@echo "  security      - Check for security vulnerabilities"
	@echo "  setup         - Install development tools"
	@echo "  help          - Show this help message"
	@echo ""
	@echo "Variables:"
	@echo "  VERSION       - Version number (default: $(VERSION))"
	@echo "  ARGS          - Arguments for 'make run' target"
	@echo ""
	@echo "Examples:"
	@echo "  make build"
	@echo "  make VERSION=1.1.0 build-all"
	@echo "  make ARGS='--help' run"

# Info target
.PHONY: info
info:
	@echo "Build Information:"
	@echo "  App Name:     $(APP_NAME)"
	@echo "  Version:      $(VERSION)"
	@echo "  Build Date:   $(BUILD_DATE)"
	@echo "  Git Commit:   $(GIT_COMMIT)"
	@echo "  Git Branch:   $(GIT_BRANCH)"
	@echo "  Go Version:   $(shell $(GO) version)"
	@echo "  GOOS:         $(GOOS)"
	@echo "  GOARCH:       $(GOARCH)"
	@echo "  GOPROXY:      $(GOPROXY)"