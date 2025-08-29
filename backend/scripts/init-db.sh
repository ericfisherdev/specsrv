#!/bin/bash

# Database initialization script for containers
# This script ensures the database is properly set up in containerized environments

set -e

echo "🚀 Initializing database..."

# Check if we're in a container environment
if [ -f /.dockerenv ]; then
    echo "📦 Container environment detected"
    DB_PATH="/app/var/data/database.sqlite"
else
    echo "🖥️ Development environment detected"
    DB_PATH="./var/data/database.sqlite"
fi

# Ensure the data directory exists
echo "📁 Creating data directory..."
mkdir -p "$(dirname "$DB_PATH")"

# Check if database already exists
if [ -f "$DB_PATH" ]; then
    echo "💾 Database file already exists at: $DB_PATH"
    
    # Check if we should force recreate
    if [ "${FORCE_DB_RECREATE:-false}" = "true" ]; then
        echo "🔄 FORCE_DB_RECREATE is set, removing existing database..."
        rm -f "$DB_PATH"
    fi
fi

# Run migrations
echo "🔧 Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Load fixtures if in development mode or explicitly requested
if [ "${APP_ENV:-prod}" = "dev" ] || [ "${LOAD_FIXTURES:-false}" = "true" ]; then
    echo "🌱 Loading development fixtures..."
    php bin/console doctrine:fixtures:load --no-interaction
else
    echo "ℹ️ Skipping fixtures load (production environment)"
fi

# Set proper permissions for the database file
if [ -f "$DB_PATH" ]; then
    echo "🔐 Setting database file permissions..."
    chmod 664 "$DB_PATH"
    
    # In container, ensure www-data can access the database
    if [ -f /.dockerenv ] && [ "$(id -u)" = "0" ]; then
        chown www-data:www-data "$DB_PATH"
        chown www-data:www-data "$(dirname "$DB_PATH")"
    fi
fi

echo "✅ Database initialization completed successfully!"

# Validate database by running a simple query
echo "🔍 Validating database connection..."
php bin/console doctrine:query:sql "SELECT COUNT(*) as user_count FROM users" --quiet

echo "🎉 Database is ready to use!"