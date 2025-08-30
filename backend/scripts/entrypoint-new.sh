#!/bin/sh
set -e

echo "=== SpecSrv Container Starting ==="

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

# Ensure proper permissions
if [ "$(id -u)" = "0" ]; then
    echo "Running as root, fixing permissions..."
    chown -R app:app /app/var
    chmod -R 775 /app/var
    echo "Switching to app user..."
    exec su app -s /bin/sh -c "$0 $*"
fi

# Environment setup
export APP_ENV="${APP_ENV:-prod}"
export APP_DEBUG="${APP_DEBUG:-0}"

echo "Environment: APP_ENV=$APP_ENV, APP_DEBUG=$APP_DEBUG"

# Database setup for production
if [ "$APP_ENV" = "prod" ]; then
    echo "=== Production Database Setup ==="
    
    # Skip database creation for SQLite (file-based)
    echo "Using SQLite database file"
    
    # Run migrations if needed
    php bin/console doctrine:migrations:migrate --no-interaction --env=prod || echo "Migration skipped"
    
    # Skip cache operations as they're causing permission issues
    echo "Skipping cache operations for now"
fi

# Development setup
if [ "$APP_ENV" = "dev" ]; then
    echo "=== Development Database Setup ==="
    php bin/console doctrine:database:create --if-not-exists --env=dev --no-interaction || echo "Database creation skipped"
    php bin/console doctrine:migrations:migrate --no-interaction --env=dev || echo "Migration skipped"
    
    # Clear cache for development
    php bin/console cache:clear --env=dev
fi

echo "=== Starting Services ==="

# Create nginx temp directories if they don't exist
mkdir -p /tmp/nginx_client_temp /tmp/nginx_proxy_temp /tmp/nginx_fastcgi_temp /tmp/nginx_uwsgi_temp /tmp/nginx_scgi_temp

# Start services based on environment
if command -v supervisord >/dev/null 2>&1; then
    echo "Starting services with supervisord..."
    exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
else
    echo "Starting services manually..."
    
    # Start PHP-FPM in background
    php-fpm --daemonize
    
    # Start nginx in foreground
    exec nginx -g 'daemon off; error_log stderr info;'
fi