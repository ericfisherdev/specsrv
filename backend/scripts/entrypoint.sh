#!/bin/sh
set -e

# Create nginx temp directories if they don't exist
mkdir -p /tmp/nginx_client_temp /tmp/nginx_proxy_temp /tmp/nginx_fastcgi_temp /tmp/nginx_uwsgi_temp /tmp/nginx_scgi_temp

# Start php-fpm in background
php-fpm --daemonize

# Start nginx in foreground with error log to stderr
exec nginx -g 'daemon off; error_log stderr;'