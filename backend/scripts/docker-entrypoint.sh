#!/bin/bash

# Docker entrypoint script for SpecSrv backend
# This script handles container initialization and startup

set -e

echo "🐳 Starting SpecSrv backend container..."

# Wait for dependencies if needed (useful for multi-container setups)
if [ -n "$WAIT_FOR_SERVICES" ]; then
    echo "⏳ Waiting for dependent services..."
    # Add service waiting logic here if needed in the future
fi

# Initialize the database
echo "📊 Initializing database..."
./scripts/init-db.sh

# Warm up Symfony cache
echo "🔥 Warming up Symfony cache..."
php bin/console cache:warmup --env="${APP_ENV:-prod}"

# Clear and regenerate cache for good measure
echo "🧹 Clearing cache..."
php bin/console cache:clear --env="${APP_ENV:-prod}" --no-debug

# Ensure proper file permissions for web server
echo "🔒 Setting file permissions..."
if [ "$(id -u)" = "0" ]; then
    # Running as root, set ownership for web server
    chown -R www-data:www-data /app/var
    chmod -R 755 /app/var
else
    # Running as non-root, just set permissions
    chmod -R 755 /app/var
fi

echo "✅ Container initialization completed!"

# Execute the main command
echo "🚀 Starting application..."
exec "$@"