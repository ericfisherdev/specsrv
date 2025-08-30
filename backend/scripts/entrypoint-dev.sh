#!/bin/sh
set -e

echo "=== SpecSrv Development Container Starting ==="

# Function to wait for a service to be ready
wait_for_service() {
    local host="$1"
    local port="$2"
    local service="$3"
    
    echo "Waiting for $service to be ready..."
    while ! nc -z "$host" "$port"; do
        sleep 1
    done
    echo "$service is ready!"
}

# Ensure proper permissions for development
if [ "$(id -u)" = "0" ]; then
    echo "Running as root, fixing permissions..."
    chown -R app:app /app/var
    chmod -R 775 /app/var
    echo "Switching to app user..."
    exec su app -s /bin/sh -c "$0 $*"
fi

# Environment setup
export APP_ENV="${APP_ENV:-dev}"
export APP_DEBUG="${APP_DEBUG:-1}"

echo "Environment: APP_ENV=$APP_ENV, APP_DEBUG=$APP_DEBUG"

# Database setup for development
if [ "$APP_ENV" = "dev" ]; then
    echo "=== Development Database Setup ==="
    
    # Create database if it doesn't exist (for SQLite this just ensures the file exists)
    touch /app/var/data/db.sqlite
    chmod 664 /app/var/data/db.sqlite
    
    # Run migrations
    php bin/console doctrine:migrations:migrate --no-interaction --env=dev || echo "Migration skipped"
    
    # Install dev dependencies if they're not present
    if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
        echo "Installing PHP dependencies..."
        composer install --prefer-dist
    fi
    
    # Install/update npm dependencies if needed
    if [ ! -d "node_modules" ] || [ ! -f "node_modules/.package-lock.json" ]; then
        echo "Installing Node.js dependencies..."
        npm ci
    fi
    
    echo "Development setup complete"
fi

echo "=== Starting Development Services ==="

# Create nginx temp directories if they don't exist
mkdir -p /tmp/nginx_client_temp /tmp/nginx_proxy_temp /tmp/nginx_fastcgi_temp /tmp/nginx_uwsgi_temp /tmp/nginx_scgi_temp

# For development, we only need PHP-FPM since webpack dev server will handle static assets
if command -v supervisord >/dev/null 2>&1; then
    echo "Starting services with supervisord..."
    exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
else
    echo "Starting PHP-FPM..."
    exec php-fpm --nodaemonize
fi