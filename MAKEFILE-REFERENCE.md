# SpecSrv Makefile Reference

## 🔥 Hot Reloading Commands (NEW!)

### Quick Start
```bash
make hot-reload                    # Default: simple hot reloading
make hot-reload-help              # Show detailed hot reloading guide
```

### Hot Reloading Methods
```bash
make hot-reload-simple            # 🌟 RECOMMENDED: Production container + local webpack watch
                                  # URL: http://localhost:8080

make hot-reload-advanced          # Full webpack dev server with HMR  
                                  # URLs: http://localhost:8000 (webpack), http://localhost:8001 (php)

make hot-reload-dev               # Development profile method
                                  # URL: http://localhost:8001
```

### Management
```bash
make hot-reload-stop              # Stop ALL hot reloading environments
make webpack-watch                # Start webpack watch separately (run in another terminal)
```

### Logs & Debugging  
```bash
make hot-reload-logs              # Show available log options
make hot-reload-logs-simple       # Logs for simple method
make hot-reload-logs-advanced     # Logs for advanced method  
make hot-reload-logs-dev          # Logs for development profile
```

---

## 🏗️ Docker Commands

### Production
```bash
make prod-build                   # Build production images  
make prod-up                      # Start production environment
make prod-down                    # Stop production environment
```

### Development
```bash
make docker-build                 # Build Docker containers
make docker-up                    # Start Docker containers  
make docker-down                  # Stop Docker containers
make docker-logs                  # Show container logs
make docker-restart               # Restart containers
```

### Quick Start
```bash
make dev                          # Start development environment + migrate DB
make quick-start                  # Quick Docker start
```

---

## 🚀 Local Development

### Server Management
```bash
make start                        # Start local PHP dev server (localhost:8000)
make stop                         # Stop local PHP dev server
make restart                      # Restart local PHP dev server
make devup                        # Alias for start
make devdown                      # Alias for stop
```

---

## 🗄️ Database Commands

```bash
make db-create                    # Create database
make db-migrate                   # Run migrations
make db-seed                      # Seed with test data  
make db-reset                     # Full reset (drop, create, migrate, seed)
```

---

## 🧪 Testing & Code Quality

### Testing
```bash
make test                         # Run all tests
make api-test                     # Run API endpoint tests
```

### Code Quality
```bash
make lint                         # Run code analysis (PHPStan)
make fix-cs                       # Fix code style (PHP CS Fixer)
make analyze                      # Run all code quality checks
make security-check               # Check for security vulnerabilities
```

---

## ⚙️ Setup & Maintenance

### Setup
```bash
make install                      # Install dependencies
make setup                        # Complete project setup
```

### Maintenance
```bash
make clean                        # Clean caches and temporary files
make health                       # Check application health
```

---

## 📖 Help & Information

```bash
make help                         # Show all available commands
make hot-reload-help             # 🔥 Detailed hot reloading guide
```

---

## 💡 Common Workflows

### First Time Setup
```bash
make install
make setup
make hot-reload                   # Start hot reloading development
```

### Daily Development
```bash
make hot-reload                   # Start with hot reloading
# Edit files in assets/, src/, templates/
# Changes automatically reload!
make hot-reload-stop             # Stop when done
```

### Testing & Quality
```bash
make test                         # Run tests
make analyze                      # Code quality checks
make security-check               # Security audit
```

### Production Deployment
```bash
make prod-build                   # Build production images
make prod-up                      # Deploy to production
```

---

## 🎯 Hot Reloading Features

✅ **Frontend Hot Reloading**: CSS/JS auto-compile and refresh  
✅ **Backend Hot Reloading**: PHP/Twig changes reflect immediately  
✅ **Database Persistence**: Data survives container restarts  
✅ **Source Maps**: Full debugging support  
✅ **Multiple Methods**: Choose the approach that works for you  
✅ **Easy Management**: Simple start/stop commands  

The hot reloading setup provides the perfect development experience with instant feedback on changes!