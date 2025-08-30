# Hot Reloading Setup for SpecSrv

## Simple Hot Reloading Solution

The easiest way to enable hot reloading is to use the production Docker container with volume mounts and run webpack in watch mode separately.

### Method 1: Production Container + Local Webpack Watch

1. **Start the Production Container:**
```bash
docker-compose -f docker-compose.fixed.yml up specsrv-app -d
```

2. **Run Webpack in Watch Mode (in a separate terminal):**
```bash
cd backend
npm run watch
```

3. **Access the Application:**
- **URL**: http://localhost:8080
- **Hot Reloading**: Frontend changes (CSS/JS) are automatically compiled and served
- **Backend Changes**: PHP/Twig changes are automatically reflected (due to volume mounts)

### Method 2: Development Container with Volume Mounts

Add this service to your existing `docker-compose.fixed.yml`:

```yaml
# Add to docker-compose.fixed.yml services:
specsrv-dev:
  build:
    context: ./backend
    dockerfile: Dockerfile.new
    target: production
  container_name: specsrv-dev
  ports:
    - "8001:8080"
  environment:
    - APP_ENV=dev
    - APP_DEBUG=1
    - DATABASE_URL=sqlite:///%kernel.project_dir%/var/data/db.sqlite
  volumes:
    # Mount source code for live changes
    - ./backend:/app
    # Persistent data
    - specsrv-dev-data:/app/var/data
    - specsrv-dev-logs:/app/var/log
    - specsrv-dev-uploads:/app/var/uploads
  networks:
    - specsrv-network
  profiles:
    - dev
```

Then run:
```bash
# Start development container
docker-compose -f docker-compose.fixed.yml --profile dev up -d

# Run webpack watch locally
cd backend && npm run watch
```

## How It Works

### Frontend Hot Reloading
- **Webpack Watch**: Monitors `assets/` directory for changes
- **Auto Compilation**: Automatically recompiles CSS/JS when files change
- **Live Updates**: Browser automatically refreshes or hot-swaps modules
- **Source Maps**: Full debugging support in development

### Backend Hot Reloading
- **Volume Mounts**: Docker volumes mount your local source code
- **PHP-FPM**: Automatically serves updated PHP files without restart
- **Twig Templates**: Template changes are immediately available
- **No Rebuild**: No need to rebuild Docker images for code changes

### Database & Files
- **Persistent Data**: Database changes are saved in Docker volumes
- **File Uploads**: Stored in persistent volumes
- **Logs**: Available in mounted volumes for debugging

## Package.json Scripts

Make sure these scripts exist in your `package.json`:

```json
{
  "scripts": {
    "dev": "encore dev",
    "watch": "encore dev --watch",
    "dev-server": "encore dev-server",
    "build": "encore production"
  }
}
```

## Workflow

### Making Changes

1. **Frontend Changes** (CSS/JS in `assets/`):
   - Save your changes
   - Webpack automatically recompiles
   - Browser refreshes automatically

2. **Backend Changes** (PHP in `src/`, Twig in `templates/`):
   - Save your changes
   - Refresh browser to see updates
   - No container restart needed

3. **Configuration Changes**:
   - If you change `webpack.config.js`, restart webpack watch
   - If you change Docker config, restart containers

### Debugging

```bash
# View webpack compilation output
npm run watch

# View container logs
docker-compose -f docker-compose.fixed.yml logs specsrv-app

# Access container for debugging
docker-compose -f docker-compose.fixed.yml exec specsrv-app sh

# Check asset compilation
ls -la backend/public/build/
```

## Benefits

✅ **Fast Development**: Changes reflected immediately
✅ **Production-like Environment**: Uses same Docker setup as production  
✅ **Full Debugging**: Source maps and dev tools available
✅ **Persistent Data**: Database and uploads persist between restarts
✅ **Easy Setup**: Uses existing Docker configuration
✅ **No Complex Config**: Simple volume mounts and watch scripts

## Troubleshooting

### Webpack Watch Not Working
```bash
# Check if files are being watched
npm run watch -- --verbose

# Check file permissions
ls -la backend/assets/
```

### Container Issues
```bash
# Restart containers
docker-compose -f docker-compose.fixed.yml restart

# Check logs
docker-compose -f docker-compose.fixed.yml logs -f specsrv-app
```

### Port Conflicts
```bash
# Check what's using ports
lsof -i :8080
lsof -i :8001

# Change ports in docker-compose.fixed.yml if needed
```

This approach gives you reliable hot reloading without complex Docker dev server setups!