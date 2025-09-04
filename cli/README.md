# SpecSrv CLI

The SpecSrv Command Line Interface (CLI) is a comprehensive tool for managing projects, tasks, and development workflows. Built with Go and designed for developers and AI agents, it provides seamless integration with the containerized SpecSrv backend via REST API calls.

## Features

- **Project Management**: Create, read, update, and delete projects
- **Task Management**: Full CRUD operations for tasks with status tracking
- **File Management**: Upload, download, and manage files attached to projects and tasks
- **Git Integration**: Link commits, pull requests, and branches to tasks
- **Multiple Output Formats**: Support for table, JSON, and YAML output
- **Configuration Management**: Flexible configuration with profiles
- **Cross-platform**: Native binaries for Linux, macOS, and Windows
- **Shell Completion**: Auto-completion for bash, zsh, fish, and PowerShell

## Installation

### Pre-built Binaries

Download the latest release for your platform from the [releases page](../../releases):

```bash
# Linux/macOS
OS=$(uname -s | tr '[:upper:]' '[:lower:]')
ARCH=$(uname -m | tr '[:upper:]' '[:lower:]')
curl -L https://github.com/specsrv/specsrv-cli/releases/latest/download/specsrv-${OS}-${ARCH}.tar.gz | tar xz
sudo mv specsrv /usr/local/bin/

# Windows (PowerShell)
Invoke-WebRequest -Uri "https://github.com/specsrv/specsrv-cli/releases/latest/download/specsrv-windows-amd64.zip" -OutFile "specsrv.zip"
Expand-Archive -Path "specsrv.zip" -DestinationPath "."
Move-Item "specsrv.exe" "C:\Windows\System32\"
```

### Build from Source

Requirements:
- Go 1.21 or later
- Make (optional, for convenience)

```bash
# Clone the repository
git clone https://github.com/specsrv/specsrv-cli.git
cd specsrv-cli/cli

# Build with Make (recommended)
make build

# Or build directly with Go
go build -o specsrv ./main.go

# Install to GOPATH/bin
make install
```

### Package Managers

**Coming Soon** — Package manager formulas/manifests are pending publication:

- **Homebrew (macOS/Linux)**: Coming Soon — formula pending
- **Scoop (Windows)**: Coming Soon — manifest pending  
- **APT (Ubuntu/Debian)**: Coming Soon — package pending

Use build from source or pre-built binaries until packages are published.

## Quick Start

1. **Configure the CLI**:
   ```bash
   # Set the SpecSrv server URL (containerized backend)
   specsrv config set server.url http://localhost:8000
   
   # Or use environment variable
   export SPECSRV_SERVER_URL=http://localhost:8000
   ```

2. **Authenticate** (if required by your backend):
   ```bash
   # Set authentication token
   specsrv config set auth.token your-api-token
   ```

3. **Create your first project**:
   ```bash
   specsrv projects create --name "My Project" --description "A sample project"
   ```

4. **List projects**:
   ```bash
   specsrv projects list
   ```

## Usage

### Global Options

```bash
      --config string   config file (default: $HOME/.specsrv/config.yaml)
  -h, --help            help for specsrv
  -o, --output string   output format (table|json|yaml) (default "table")
  -s, --server string   SpecSrv server URL (overrides config)
  -v, --verbose         verbose output
```

### Project Management

```bash
# List all projects
specsrv projects list

# Filter projects by status
specsrv projects list --status active

# Search projects
specsrv projects list --search "development"

# Show project details
specsrv projects show 1

# Create a new project
specsrv projects create --name "New Project" --description "Project description"

# Update project
specsrv projects update 1 --name "Updated Name" --status inactive

# Delete project
specsrv projects delete 1
specsrv projects delete 1 --force  # Skip confirmation
```

### Task Management (Coming Soon)

```bash
# List tasks
specsrv tasks list
specsrv tasks list --project 1 --status todo

# Show task details
specsrv tasks show 1

# Create task
specsrv tasks create --project 1 --title "New Task" --description "Task description"

# Update task status
specsrv tasks move 1 working
specsrv tasks done 1

# Assign task to project
specsrv tasks assign 1 2
```

### File Management (Coming Soon)

```bash
# Upload file to project
specsrv files upload project 1 /path/to/file.txt

# Upload file to task
specsrv files upload task 1 /path/to/file.txt

# List files
specsrv files list project 1

# Download file
specsrv files download 1 /path/to/download/
```

### Git Integration (Coming Soon)

```bash
# Link commit to task
specsrv git link 1 commit abc123

# Link pull request to task
specsrv git link 1 pr https://github.com/user/repo/pull/123

# Show git links for task
specsrv git show 1
```

### Configuration

```bash
# Show current configuration
specsrv config get

# Set configuration values
specsrv config set server.url http://localhost:8000
specsrv config set output.format json
specsrv config set auth.token your-token

# Create and switch profiles
specsrv profile create development
specsrv profile switch development
```

### Output Formats

The CLI supports multiple output formats:

```bash
# Table format (default)
specsrv projects list

# JSON format
specsrv projects list --output json

# YAML format
specsrv projects list --output yaml
```

### Shell Completion

Enable shell completion for enhanced productivity:

```bash
# Bash
specsrv completion bash | sudo tee /etc/bash_completion.d/specsrv

# Zsh
specsrv completion zsh > ~/.specsrv-completion.zsh
echo 'source ~/.specsrv-completion.zsh' >> ~/.zshrc

# Fish
specsrv completion fish > ~/.config/fish/completions/specsrv.fish

# PowerShell
specsrv completion powershell | Out-String | Invoke-Expression
```

## Configuration

### Configuration File

The CLI uses a YAML configuration file located at `~/.specsrv/config.yaml`:

```yaml
server:
  url: "http://localhost:8000"
  timeout: 30
  headers:
    X-Custom-Header: "value"

auth:
  token: "your-api-token"
  method: "token"

output:
  format: "table"
  color: true

verbose: false
```

### Environment Variables

All configuration options can be set via environment variables with the `SPECSRV_` prefix:

```bash
export SPECSRV_SERVER_URL=http://localhost:8000
export SPECSRV_AUTH_TOKEN=your-token
export SPECSRV_OUTPUT_FORMAT=json
export SPECSRV_VERBOSE=true
```

### Configuration Profiles

**Coming Soon** — Profile and config management commands are not yet implemented.

The following commands will be available in a future release:
- `specsrv profile create <name>` — Create a new profile
- `specsrv profile switch <name>` — Switch to a profile  
- `specsrv config set <key> <value>` — Set configuration values
- `specsrv config get <key>` — Get configuration values

## Development

### Project Structure

```
cli/
├── cmd/specsrv/          # Main command and root setup
├── internal/
│   ├── commands/         # Command implementations
│   ├── client/           # HTTP client for API communication
│   └── config/           # Configuration management
├── pkg/
│   ├── models/           # Data models
│   └── errors/           # Custom error types
├── build/                # Build artifacts
├── dist/                 # Cross-compiled binaries
├── docs/                 # Generated documentation
├── Makefile              # Build system
├── main.go               # Entry point
└── README.md             # This file
```

### Building

```bash
# Development build
make dev

# Production build
make build

# Build for all platforms
make build-all

# Create release packages
make package

# Run tests
make test
make test-coverage

# Format and lint
make fmt
make lint

# Install development tools
make setup
```

### Available Make Targets

- `make build` - Build for current platform
- `make build-all` - Cross-compile for all platforms
- `make clean` - Remove build artifacts
- `make test` - Run tests
- `make fmt` - Format code
- `make lint` - Run linter
- `make deps` - Update dependencies
- `make docs` - Generate completion scripts
- `make install` - Install locally
- `make package` - Create release packages

### Testing

```bash
# Run all tests
make test

# Run tests with coverage
make test-coverage

# Run specific test
go test -v ./internal/commands

# Run benchmarks
go test -bench=. ./...
```

## Architecture

The CLI follows a modular architecture designed for maintainability and extensibility:

### Command Pattern

- Each command group (projects, tasks, files) is implemented as a separate module
- Commands use the Cobra library for consistent CLI experience
- Subcommands are organized hierarchically

### Configuration Management

- Viper-based configuration with YAML files
- Environment variable support with `SPECSRV_` prefix
- Multi-profile support for different environments

### API Communication

- HTTP client wrapper for consistent API interaction
- Support for authentication, headers, and timeouts
- JSON-based communication with the containerized backend

### Error Handling

- Custom error types with context
- Graceful error reporting to users
- Verbose mode for debugging

### Output Formatting

- Pluggable output formatters (table, JSON, YAML)
- Consistent formatting across all commands
- Color support with automatic terminal detection

## Integration with SpecSrv Backend

The CLI is designed to work seamlessly with the containerized SpecSrv backend:

### Container Communication

- Connects to containerized SpecSrv backend via HTTP API
- Configurable server URLs for different container deployments
- Support for development, staging, and production container environments

### API Endpoints

The CLI communicates with these backend endpoints:

- `GET /api/v1/projects` - List projects
- `POST /api/v1/projects` - Create project
- `GET /api/v1/projects/{id}` - Get project details
- `PUT /api/v1/projects/{id}` - Update project
- `DELETE /api/v1/projects/{id}` - Delete project
- `GET /api/v1/tasks` - List tasks
- `POST /api/v1/tasks` - Create task
- And more...

### Authentication

- Token-based authentication
- Configurable auth methods
- Secure credential storage

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests (`make test`)
5. Format code (`make fmt`)
6. Commit changes (`git commit -m 'Add amazing feature'`)
7. Push to branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

### Development Guidelines

- Follow Go best practices and idioms
- Add tests for new functionality
- Update documentation as needed
- Use semantic commit messages
- Ensure cross-platform compatibility

## Troubleshooting

### Common Issues

**Connection Issues**:
```bash
# Check server connectivity
curl -f http://localhost:8000/health

# Verify configuration
specsrv config get server.url

# Test with verbose output
specsrv --verbose projects list
```

**Authentication Issues**:
```bash
# Check token configuration
specsrv config get auth.token

# Test API access manually
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/v1/projects
```

**Configuration Issues**:
```bash
# Reset configuration
rm ~/.specsrv/config.yaml

# Use environment variables temporarily
SPECSRV_SERVER_URL=http://localhost:8000 specsrv projects list
```

### Debug Mode

Enable verbose output for debugging:

```bash
specsrv --verbose projects list
# or
export SPECSRV_VERBOSE=true
```

### Logging

The CLI logs to stderr when in verbose mode. For persistent logging:

```bash
specsrv --verbose projects list 2>debug.log
```

## License

This project is licensed under the MIT License - see the [LICENSE](../LICENSE) file for details.

## Support

- **Documentation**: [https://docs.specsrv.com](https://docs.specsrv.com)
- **Issues**: [GitHub Issues](https://github.com/specsrv/specsrv-cli/issues)
- **Discussions**: [GitHub Discussions](https://github.com/specsrv/specsrv-cli/discussions)
- **Email**: support@specsrv.com

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and changes.