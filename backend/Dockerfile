# Multi-stage Dockerfile for SpecSrv Backend
# Optimized for production deployment with minimal image size

# Stage 1: Frontend Assets Build
FROM node:20-alpine AS frontend

WORKDIR /app

# Copy package files for dependency resolution
COPY package.json package-lock.json ./

# Install Node.js dependencies (including dev dependencies for build tools)
RUN npm ci

# Copy source files needed for build
COPY assets/ ./assets/
COPY webpack.config.js ./
COPY postcss.config.js ./
COPY tailwind.config.js ./

# Build production assets
RUN npm run build

# Stage 2: Composer dependencies
FROM composer:2.7 AS composer

WORKDIR /app

# Copy composer files for dependency resolution
COPY composer.json composer.lock ./

# Install production dependencies only (no dev dependencies)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --optimize-autoloader

# Copy application source (including all PHP files, templates, config, etc)
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Stage 3: PHP Runtime with Nginx
FROM php:8.2-fpm-alpine AS runtime

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    nginx \
    supervisor \
    sqlite \
    sqlite-dev \
    curl \
    && docker-php-ext-install \
    pdo \
    pdo_sqlite \
    opcache

# Create application user
RUN addgroup -g 1000 app && adduser -u 1000 -G app -s /bin/sh -D app

# Configure PHP-FPM to use TCP instead of socket
RUN sed -i 's/user = www-data/user = app/g' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/group = www-data/group = app/g' /usr/local/etc/php-fpm.d/www.conf

# Configure Nginx
COPY --chown=app:app scripts/nginx.conf /etc/nginx/nginx.conf

# Configure Supervisor
COPY --chown=app:app scripts/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Configure PHP for production
COPY --chown=app:app scripts/php.ini /usr/local/etc/php/conf.d/99-app.ini

# Set working directory
WORKDIR /app

# Copy application from composer stage
COPY --from=composer --chown=app:app /app .

# Copy built frontend assets from frontend stage
COPY --from=frontend --chown=app:app /app/public/build ./public/build

# Copy health check script
COPY --chown=app:app scripts/healthcheck.sh /usr/local/bin/healthcheck.sh
RUN chmod +x /usr/local/bin/healthcheck.sh

# Copy entrypoint script
COPY --chown=app:app scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Create required directories with proper permissions
RUN mkdir -p \
    var/cache \
    var/log \
    var/data \
    var/sessions \
    var/uploads \
    public \
    /tmp/nginx_client_temp \
    /tmp/nginx_proxy_temp \
    /tmp/nginx_fastcgi_temp \
    /tmp/nginx_uwsgi_temp \
    /tmp/nginx_scgi_temp \
    && chown -R app:app var/ /tmp/nginx_* \
    && chmod -R 755 var/ /tmp/nginx_* \
    && chmod -R 777 var/cache var/log var/sessions var/uploads

# Switch to application user
USER app

# Expose port 8080 (Nginx)
EXPOSE 8080

# Health check with comprehensive frontend readiness check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD /usr/local/bin/healthcheck.sh readiness

# Use entrypoint script to manage processes
CMD ["/usr/local/bin/entrypoint.sh"]

# Stage 4: Development image (extends runtime)
FROM runtime AS development

# Switch back to root for installations
USER root

# Install development dependencies including build tools for Xdebug
RUN apk add --no-cache \
    git \
    unzip \
    curl \
    autoconf \
    g++ \
    make \
    linux-headers

# Install Xdebug for development
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Copy Xdebug configuration
COPY --chown=app:app scripts/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Copy composer binary from composer stage
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Install dev composer dependencies
COPY composer.json composer.lock ./
RUN composer install --prefer-dist --no-scripts

# Switch back to app user
USER app

# Development environment variables
ENV APP_ENV=dev
ENV APP_DEBUG=1

# Stage 5: Testing image (extends development)
FROM development AS testing

# Switch to root for test setup
USER root

# Install additional testing tools
RUN apk add --no-cache \
    make

# Switch back to app user  
USER app

# Set testing environment
ENV APP_ENV=test

# Run tests as part of build process
RUN php bin/console doctrine:database:create --env=test --if-not-exists
RUN php bin/console doctrine:migrations:migrate --env=test --no-interaction
RUN php bin/phpunit