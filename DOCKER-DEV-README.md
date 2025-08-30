# Docker Development Environment with Hot Reloading

This setup provides a complete development environment with hot reloading for both frontend and backend changes.

## Quick Start

### Start Development Environment
```bash
# Start all development services
docker-compose -f docker-compose.dev.yml up -d

# Or build and start (first time or after Dockerfile changes)
docker-compose -f docker-compose.dev.yml up -d --build
```

### Access the Application
- **Main Development URL**: http://localhost:8000 (Webpack Dev Server with hot reloading)
- **PHP Application Direct**: http://localhost:8001 (Direct PHP container access)

### Stop Development Environment
```bash
docker-compose -f docker-compose.dev.yml down
```

## How Hot Reloading Works

### Frontend Hot Reloading
- **What**: Automatic browser refresh/update when you modify CSS, JavaScript, or asset files
- **Files watched**: `assets/**/*`, `webpack.config.js`, `postcss.config.js`, `tailwind.config.js`
- **URL**: http://localhost:8000

### Backend Hot Reloading  
- **What**: Automatic reload when you modify PHP files or Twig templates
- **Files watched**: `src/**/*.php`, `templates/**/*.twig`
- **How**: Webpack dev server proxy + Docker volume mounts

### Database & File Changes
- **Database**: Changes persist in Docker volumes
- **Uploaded files**: Stored in Docker volumes
- **Logs**: Available in Docker volumes

## Development Workflow

### Making Changes

1. **Frontend Changes** (CSS/JS):
   ```bash
   # Edit files in assets/
   # Changes are automatically reflected at http://localhost:8000
   ```

2. **Backend Changes** (PHP/Twig):
   ```bash
   # Edit files in src/ or templates/
   # Changes are automatically available (no rebuild needed)
   ```

3. **Dependency Changes**:
   ```bash
   # PHP dependencies (composer.json)
   docker-compose -f docker-compose.dev.yml exec specsrv-app-dev composer install
   
   # NPM dependencies (package.json)
   docker-compose -f docker-compose.dev.yml exec specsrv-webpack-dev npm install
   ```

### Debugging

1. **View Logs**:
   ```bash
   # All services
   docker-compose -f docker-compose.dev.yml logs -f
   
   # Specific service
   docker-compose -f docker-compose.dev.yml logs -f specsrv-webpack-dev
   docker-compose -f docker-compose.dev.yml logs -f specsrv-app-dev
   ```

2. **Access Container**:
   ```bash
   # PHP container
   docker-compose -f docker-compose.dev.yml exec specsrv-app-dev sh
   
   # Webpack container
   docker-compose -f docker-compose.dev.yml exec specsrv-webpack-dev sh
   ```

3. **Xdebug**: Available in the PHP container for debugging

## Architecture

### Services
- **specsrv-webpack-dev**: Webpack dev server (port 8000) with hot reloading
- **specsrv-app-dev**: PHP-FPM + Nginx (port 8001) serving the PHP application

### Volume Mounts
- `./backend:/app` - Full source code mount for live changes
- `specsrv-dev-data` - Persistent database storage  
- `specsrv-dev-logs` - Application logs
- `specsrv-dev-uploads` - Uploaded files

### Network
- Both containers communicate on `specsrv-dev-network`
- Webpack proxies PHP requests to the PHP container

## Troubleshooting

### Port Conflicts
If ports 8000 or 8001 are in use:
```bash
# Check what's using the ports
sudo lsof -i :8000
sudo lsof -i :8001

# Or modify docker-compose.dev.yml to use different ports
```

### Hot Reloading Not Working
```bash
# Restart webpack dev server
docker-compose -f docker-compose.dev.yml restart specsrv-webpack-dev

# Check logs
docker-compose -f docker-compose.dev.yml logs specsrv-webpack-dev
```

### Permission Issues
```bash
# Fix file permissions
docker-compose -f docker-compose.dev.yml exec specsrv-app-dev chown -R app:app /app/var
```

### Clean Start
```bash
# Remove containers and volumes
docker-compose -f docker-compose.dev.yml down -v

# Rebuild everything
docker-compose -f docker-compose.dev.yml up -d --build
```

## Production vs Development

- **Production**: Use `docker-compose.fixed.yml` for production deployment
- **Development**: Use `docker-compose.dev.yml` for development with hot reloading
- **Existing**: Use regular `docker-compose.yml` or local development server

This setup gives you the best of both worlds: a containerized environment that matches production while providing the developer experience of instant feedback on changes.