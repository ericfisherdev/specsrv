#!/bin/sh
# Basic healthcheck script for the Symfony application

set -e

# Check if PHP-FPM is running
if ! pgrep php-fpm > /dev/null; then
    echo "PHP-FPM is not running"
    exit 1
fi

# Check if Nginx is running
if ! pgrep nginx > /dev/null; then
    echo "Nginx is not running"
    exit 1
fi

# Check if the application responds with HTTP 200
if ! curl --silent --fail --show-error --head 127.0.0.1:8080/health > /dev/null 2>&1; then
    echo "Application is not responding"
    exit 1
fi

echo "Health check passed"
exit 0