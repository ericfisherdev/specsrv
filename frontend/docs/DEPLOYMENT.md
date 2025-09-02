# SpecSrv Production Deployment Guide

This document provides comprehensive instructions for deploying the SpecSrv frontend to production environments, including Docker containerization, CI/CD pipelines, and monitoring setup.

## Table of Contents

1. [Deployment Overview](#deployment-overview)
2. [Prerequisites](#prerequisites)
3. [Build Process](#build-process)
4. [Docker Deployment](#docker-deployment)
5. [Nginx Configuration](#nginx-configuration)
6. [Environment Configuration](#environment-configuration)
7. [CI/CD Pipeline](#cicd-pipeline)
8. [Health Checks](#health-checks)
9. [Monitoring and Logging](#monitoring-and-logging)
10. [Security Considerations](#security-considerations)
11. [Performance Optimization](#performance-optimization)
12. [Backup and Recovery](#backup-and-recovery)

## Deployment Overview

The SpecSrv frontend is deployed as a containerized static web application served by Nginx. The deployment architecture includes:

- **Static Assets**: Built and optimized frontend bundle
- **Nginx Server**: Serves static files and proxies API requests
- **Docker Container**: Isolated runtime environment
- **Load Balancer**: Distributes traffic (optional)
- **CDN**: Serves static assets globally (recommended)

## Prerequisites

### System Requirements

- Docker Engine 20.10+
- Docker Compose 2.0+
- Node.js 18+ (for local builds)
- Nginx 1.20+ (if not using Docker)

### Domain and SSL

- Registered domain name
- Valid SSL certificate (Let's Encrypt recommended)
- DNS configuration pointing to server

### Infrastructure

- Minimum 1GB RAM, 1 CPU core
- 10GB storage for logs and cache
- Network access to backend API

## Build Process

### Production Build

1. **Install Dependencies**
   ```bash
   cd frontend
   npm ci --production=false
   ```

2. **Run Tests**
   ```bash
   npm run test
   npm run test:e2e
   ```

3. **Build for Production**
   ```bash
   npm run build
   ```

4. **Verify Build Output**
   ```bash
   ls -la dist/
   # Should contain: index.html, assets/, and other static files
   ```

### Build Configuration

```javascript
// webpack.config.js (production optimizations)
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const OptimizeCSSAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = {
  mode: 'production',
  
  optimization: {
    minimizer: [
      new TerserPlugin({
        parallel: true,
        terserOptions: {
          compress: {
            drop_console: true, // Remove console.log in production
          },
        },
      }),
      new OptimizeCSSAssetsPlugin(),
    ],
    splitChunks: {
      chunks: 'all',
      cacheGroups: {
        vendor: {
          test: /[\\/]node_modules[\\/]/,
          name: 'vendors',
          chunks: 'all',
        },
      },
    },
  },
  
  plugins: [
    new MiniCssExtractPlugin({
      filename: 'assets/css/[name].[contenthash].css',
    }),
  ],
};
```

## Docker Deployment

### Multi-Stage Dockerfile

```dockerfile
# Multi-stage build for optimized production image
FROM node:18-alpine AS builder

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies (including dev dependencies for build)
RUN npm ci

# Copy source code
COPY . .

# Build application
RUN npm run build

# Production stage
FROM nginx:alpine AS production

# Install security updates
RUN apk update && apk upgrade && apk add --no-cache curl

# Copy custom nginx configuration
COPY nginx.conf /etc/nginx/nginx.conf
COPY nginx-default.conf /etc/nginx/conf.d/default.conf

# Copy built application from builder stage
COPY --from=builder /app/dist /usr/share/nginx/html

# Create nginx user and set permissions
RUN chown -R nginx:nginx /usr/share/nginx/html && \
    chown -R nginx:nginx /var/cache/nginx && \
    chown -R nginx:nginx /var/log/nginx && \
    chown -R nginx:nginx /etc/nginx/conf.d

# Switch to non-root user
USER nginx

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
  CMD curl -f http://localhost:80/health || exit 1

# Start nginx
CMD ["nginx", "-g", "daemon off;"]
```

### Docker Compose Configuration

```yaml
# docker-compose.prod.yml
version: '3.8'

services:
  specsrv-frontend:
    build:
      context: .
      dockerfile: Dockerfile
      target: production
    container_name: specsrv-frontend
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    environment:
      - NGINX_WORKER_PROCESSES=auto
      - NGINX_WORKER_CONNECTIONS=1024
    volumes:
      - ./ssl:/etc/nginx/ssl:ro  # SSL certificates
      - ./logs:/var/log/nginx    # Log files
      - ./cache:/var/cache/nginx # Nginx cache
    networks:
      - specsrv-network
    depends_on:
      - specsrv-backend
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.frontend.rule=Host(`your-domain.com`)"
      - "traefik.http.routers.frontend.tls=true"
      - "traefik.http.routers.frontend.tls.certresolver=letsencrypt"

  specsrv-backend:
    # Backend service configuration
    networks:
      - specsrv-network

networks:
  specsrv-network:
    external: true
```

## Nginx Configuration

### Main Nginx Configuration

```nginx
# nginx.conf
user nginx;
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 1024;
    use epoll;
    multi_accept on;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    # Logging
    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';
    access_log /var/log/nginx/access.log main;

    # Performance
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1000;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/js
        text/xml
        text/javascript
        application/javascript
        application/json
        application/xml+rss
        application/atom+xml
        image/svg+xml;

    # Security headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    include /etc/nginx/conf.d/*.conf;
}
```

### Virtual Host Configuration

```nginx
# nginx-default.conf
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /usr/share/nginx/html;
    index index.html;

    # Security
    server_tokens off;

    # Logging
    access_log /var/log/nginx/frontend-access.log main;
    error_log /var/log/nginx/frontend-error.log;

    # Static files caching
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary Accept-Encoding;
        
        # CORS for fonts
        location ~* \.(woff|woff2|ttf|eot)$ {
            add_header Access-Control-Allow-Origin *;
        }
    }

    # API proxy
    location /api/ {
        proxy_pass http://specsrv-backend:8080/api/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
        
        # Buffer settings
        proxy_buffering on;
        proxy_buffer_size 128k;
        proxy_buffers 4 256k;
    }

    # WebSocket support (if needed)
    location /ws/ {
        proxy_pass http://specsrv-backend:8080/ws/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }

    # Health check endpoint
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }

    # Security.txt
    location /.well-known/security.txt {
        return 200 "Contact: security@your-domain.com\nExpires: 2024-12-31T23:59:59.000Z";
        add_header Content-Type text/plain;
    }

    # SPA routing - serve index.html for client-side routes
    location / {
        try_files $uri $uri/ /index.html;
        
        # Security headers for HTML
        add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self' https:; font-src 'self' data:";
        add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    }

    # Prevent access to sensitive files
    location ~ /\.(ht|git|env) {
        deny all;
    }

    location ~ \.(log|conf)$ {
        deny all;
    }
}

# HTTPS redirect (if not handled by load balancer)
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}

# HTTPS configuration
server {
    listen 443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    
    # SSL configuration
    ssl_certificate /etc/nginx/ssl/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/privkey.pem;
    
    # SSL security
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Include the main server configuration here
    # ... (same as above)
}
```

## Environment Configuration

### Environment Variables

```bash
# .env.production
NODE_ENV=production

# API Configuration
API_BASE_URL=https://your-domain.com/api/v1
API_TIMEOUT=30000

# Application Settings
APP_NAME=SpecSrv
APP_VERSION=1.0.0

# Security
ENABLE_CSP=true
ENABLE_HSTS=true

# Performance
ENABLE_COMPRESSION=true
ENABLE_CACHING=true

# Monitoring
ENABLE_ANALYTICS=true
SENTRY_DSN=https://your-sentry-dsn@sentry.io/project-id

# CDN Configuration (if using)
CDN_URL=https://cdn.your-domain.com
ASSETS_URL=https://cdn.your-domain.com/assets
```

### Build-time Configuration

```javascript
// config/production.js
module.exports = {
  api: {
    baseUrl: process.env.API_BASE_URL || 'https://api.your-domain.com/v1',
    timeout: parseInt(process.env.API_TIMEOUT) || 30000,
  },
  
  app: {
    name: process.env.APP_NAME || 'SpecSrv',
    version: process.env.APP_VERSION || '1.0.0',
  },
  
  security: {
    enableCSP: process.env.ENABLE_CSP === 'true',
    enableHSTS: process.env.ENABLE_HSTS === 'true',
  },
  
  monitoring: {
    enableAnalytics: process.env.ENABLE_ANALYTICS === 'true',
    sentryDSN: process.env.SENTRY_DSN,
  },
};
```

## CI/CD Pipeline

### GitHub Actions Deployment

```yaml
# .github/workflows/deploy-production.yml
name: Deploy to Production

on:
  push:
    branches: [main]
    tags: ['v*']

env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}/frontend

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
          cache: 'npm'
          cache-dependency-path: frontend/package-lock.json
      
      - name: Install dependencies
        working-directory: frontend
        run: npm ci
      
      - name: Run tests
        working-directory: frontend
        run: |
          npm run lint
          npm run test
          npm run test:e2e

  build:
    needs: test
    runs-on: ubuntu-latest
    outputs:
      image: ${{ steps.image.outputs.image }}
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v2
      
      - name: Login to Container Registry
        uses: docker/login-action@v2
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}
      
      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@v4
        with:
          images: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}
          tags: |
            type=ref,event=branch
            type=ref,event=pr
            type=semver,pattern={{version}}
            type=semver,pattern={{major}}.{{minor}}
      
      - name: Build and push Docker image
        uses: docker/build-push-action@v4
        with:
          context: ./frontend
          push: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
      
      - name: Output image
        id: image
        run: echo "image=${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:${{ github.sha }}" >> $GITHUB_OUTPUT

  deploy:
    needs: build
    runs-on: ubuntu-latest
    environment: production
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Deploy to production
        uses: appleboy/ssh-action@v0.1.7
        with:
          host: ${{ secrets.PRODUCTION_HOST }}
          username: ${{ secrets.PRODUCTION_USER }}
          key: ${{ secrets.PRODUCTION_SSH_KEY }}
          script: |
            # Pull latest image
            docker pull ${{ needs.build.outputs.image }}
            
            # Stop old container
            docker stop specsrv-frontend || true
            docker rm specsrv-frontend || true
            
            # Start new container
            docker run -d \
              --name specsrv-frontend \
              --restart unless-stopped \
              -p 80:80 \
              -p 443:443 \
              -v /opt/specsrv/ssl:/etc/nginx/ssl:ro \
              -v /opt/specsrv/logs:/var/log/nginx \
              --network specsrv-network \
              ${{ needs.build.outputs.image }}
            
            # Clean up old images
            docker image prune -f
      
      - name: Verify deployment
        run: |
          sleep 30
          curl -f https://your-domain.com/health || exit 1
```

### Deployment Script

```bash
#!/bin/bash
# deploy.sh

set -e

# Configuration
REGISTRY="ghcr.io"
IMAGE_NAME="your-org/specsrv-frontend"
CONTAINER_NAME="specsrv-frontend"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
}

# Parse arguments
VERSION=${1:-latest}
ENVIRONMENT=${2:-production}

log "Starting deployment of $IMAGE_NAME:$VERSION to $ENVIRONMENT"

# Pre-deployment checks
log "Running pre-deployment checks..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    error "Docker is not running"
    exit 1
fi

# Check if image exists
if ! docker pull $REGISTRY/$IMAGE_NAME:$VERSION; then
    error "Failed to pull image $REGISTRY/$IMAGE_NAME:$VERSION"
    exit 1
fi

# Health check function
health_check() {
    local max_attempts=30
    local attempt=1
    
    log "Performing health check..."
    
    while [ $attempt -le $max_attempts ]; do
        if curl -f http://localhost/health > /dev/null 2>&1; then
            log "Health check passed"
            return 0
        fi
        
        log "Health check attempt $attempt/$max_attempts failed, waiting..."
        sleep 5
        ((attempt++))
    done
    
    error "Health check failed after $max_attempts attempts"
    return 1
}

# Backup current deployment
log "Creating backup of current deployment..."
if docker ps -q -f name=$CONTAINER_NAME > /dev/null; then
    docker tag $REGISTRY/$IMAGE_NAME:latest $REGISTRY/$IMAGE_NAME:backup-$(date +%Y%m%d-%H%M%S) || true
fi

# Stop and remove old container
log "Stopping old container..."
docker stop $CONTAINER_NAME > /dev/null 2>&1 || true
docker rm $CONTAINER_NAME > /dev/null 2>&1 || true

# Start new container
log "Starting new container..."
docker run -d \
    --name $CONTAINER_NAME \
    --restart unless-stopped \
    -p 80:80 \
    -p 443:443 \
    -v /opt/specsrv/ssl:/etc/nginx/ssl:ro \
    -v /opt/specsrv/logs:/var/log/nginx \
    -v /opt/specsrv/cache:/var/cache/nginx \
    --network specsrv-network \
    --label "version=$VERSION" \
    --label "deployed=$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
    $REGISTRY/$IMAGE_NAME:$VERSION

# Wait for container to start
log "Waiting for container to start..."
sleep 10

# Verify deployment
if health_check; then
    log "Deployment successful!"
    
    # Clean up old images
    log "Cleaning up old images..."
    docker image prune -f > /dev/null 2>&1 || true
    
    log "Deployment of $IMAGE_NAME:$VERSION completed successfully"
else
    error "Deployment failed - rolling back..."
    
    # Stop failed container
    docker stop $CONTAINER_NAME > /dev/null 2>&1 || true
    docker rm $CONTAINER_NAME > /dev/null 2>&1 || true
    
    # Restore backup if it exists
    if docker images -q $REGISTRY/$IMAGE_NAME:backup-* > /dev/null; then
        BACKUP_IMAGE=$(docker images --format "table {{.Repository}}:{{.Tag}}" | grep backup | head -n1)
        log "Restoring backup: $BACKUP_IMAGE"
        
        docker run -d \
            --name $CONTAINER_NAME \
            --restart unless-stopped \
            -p 80:80 \
            -p 443:443 \
            -v /opt/specsrv/ssl:/etc/nginx/ssl:ro \
            -v /opt/specsrv/logs:/var/log/nginx \
            --network specsrv-network \
            $BACKUP_IMAGE
    fi
    
    exit 1
fi
```

## Health Checks

### Application Health Check

```javascript
// src/utils/healthCheck.js
export class HealthChecker {
  constructor() {
    this.checks = new Map();
  }
  
  addCheck(name, checkFunction) {
    this.checks.set(name, checkFunction);
  }
  
  async runChecks() {
    const results = {};
    
    for (const [name, checkFunction] of this.checks) {
      try {
        const startTime = Date.now();
        const result = await checkFunction();
        const duration = Date.now() - startTime;
        
        results[name] = {
          status: result ? 'healthy' : 'unhealthy',
          duration: `${duration}ms`,
          timestamp: new Date().toISOString()
        };
      } catch (error) {
        results[name] = {
          status: 'error',
          error: error.message,
          timestamp: new Date().toISOString()
        };
      }
    }
    
    return {
      status: Object.values(results).every(r => r.status === 'healthy') ? 'healthy' : 'unhealthy',
      checks: results,
      timestamp: new Date().toISOString()
    };
  }
}

// Initialize health checks
const healthChecker = new HealthChecker();

// API connectivity check
healthChecker.addCheck('api', async () => {
  const response = await fetch('/api/v1/health', { 
    timeout: 5000 
  });
  return response.ok;
});

// Local storage check
healthChecker.addCheck('localStorage', () => {
  try {
    const testKey = '__health_check__';
    localStorage.setItem(testKey, 'test');
    const value = localStorage.getItem(testKey);
    localStorage.removeItem(testKey);
    return value === 'test';
  } catch {
    return false;
  }
});

export default healthChecker;
```

### Docker Health Check

```dockerfile
# Health check in Dockerfile
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
  CMD curl -f http://localhost:80/health || exit 1
```

### Kubernetes Health Check

```yaml
# k8s-deployment.yml (if using Kubernetes)
apiVersion: apps/v1
kind: Deployment
metadata:
  name: specsrv-frontend
spec:
  replicas: 3
  selector:
    matchLabels:
      app: specsrv-frontend
  template:
    spec:
      containers:
      - name: frontend
        image: ghcr.io/your-org/specsrv-frontend:latest
        ports:
        - containerPort: 80
        
        livenessProbe:
          httpGet:
            path: /health
            port: 80
          initialDelaySeconds: 30
          periodSeconds: 10
          timeoutSeconds: 5
          failureThreshold: 3
        
        readinessProbe:
          httpGet:
            path: /health
            port: 80
          initialDelaySeconds: 5
          periodSeconds: 5
          timeoutSeconds: 3
          failureThreshold: 3
```

## Monitoring and Logging

### Log Configuration

```nginx
# Custom log format for better monitoring
log_format json_combined escape=json
  '{'
    '"time_local":"$time_local",'
    '"remote_addr":"$remote_addr",'
    '"remote_user":"$remote_user",'
    '"request":"$request",'
    '"status": "$status",'
    '"body_bytes_sent":"$body_bytes_sent",'
    '"request_time":"$request_time",'
    '"http_referrer":"$http_referer",'
    '"http_user_agent":"$http_user_agent",'
    '"http_x_forwarded_for":"$http_x_forwarded_for"'
  '}';

access_log /var/log/nginx/access.log json_combined;
```

### Log Rotation

```bash
# /etc/logrotate.d/specsrv-frontend
/opt/specsrv/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 nginx nginx
    postrotate
        docker exec specsrv-frontend nginx -s reload > /dev/null 2>&1 || true
    endscript
}
```

### Application Monitoring

```javascript
// src/utils/monitoring.js
export class Monitor {
  constructor() {
    this.metrics = {
      pageViews: 0,
      apiCalls: 0,
      errors: 0,
      loadTime: 0
    };
    
    this.init();
  }
  
  init() {
    // Track page views
    this.trackPageView();
    
    // Track API calls
    this.trackApiCalls();
    
    // Track errors
    this.trackErrors();
    
    // Track performance
    this.trackPerformance();
  }
  
  trackPageView() {
    this.metrics.pageViews++;
    this.sendMetric('page_view', { path: window.location.pathname });
  }
  
  trackApiCalls() {
    const originalFetch = window.fetch;
    window.fetch = async (...args) => {
      const startTime = performance.now();
      
      try {
        const response = await originalFetch(...args);
        const duration = performance.now() - startTime;
        
        this.metrics.apiCalls++;
        this.sendMetric('api_call', {
          url: args[0],
          status: response.status,
          duration
        });
        
        return response;
      } catch (error) {
        this.metrics.errors++;
        this.sendMetric('api_error', {
          url: args[0],
          error: error.message
        });
        throw error;
      }
    };
  }
  
  sendMetric(name, data) {
    // Send to monitoring service (e.g., DataDog, New Relic)
    if (window.gtag) {
      window.gtag('event', name, data);
    }
    
    // Send to custom analytics endpoint
    fetch('/api/v1/analytics', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ metric: name, data, timestamp: Date.now() })
    }).catch(() => {}); // Ignore analytics errors
  }
}
```

## Security Considerations

### Content Security Policy

```html
<!-- In index.html -->
<meta http-equiv="Content-Security-Policy" content="
  default-src 'self';
  script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;
  style-src 'self' 'unsafe-inline';
  img-src 'self' data: https:;
  connect-src 'self' https://api.your-domain.com;
  font-src 'self' data:;
  object-src 'none';
  base-uri 'self';
  form-action 'self';
">
```

### Security Headers

```nginx
# Security headers in nginx
add_header X-Frame-Options DENY;
add_header X-Content-Type-Options nosniff;
add_header X-XSS-Protection "1; mode=block";
add_header Referrer-Policy "strict-origin-when-cross-origin";
add_header Permissions-Policy "camera=(), microphone=(), geolocation=()";
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
```

### Environment Secrets

```bash
# Use external secrets management
# Never commit secrets to repository

# Example with Docker secrets
docker run -d \
  --name specsrv-frontend \
  --secret source=ssl_cert,target=/etc/nginx/ssl/cert.pem \
  --secret source=ssl_key,target=/etc/nginx/ssl/key.pem \
  specsrv-frontend:latest
```

## Performance Optimization

### CDN Configuration

```javascript
// webpack.config.js - CDN integration
module.exports = {
  output: {
    publicPath: process.env.NODE_ENV === 'production' 
      ? 'https://cdn.your-domain.com/assets/' 
      : '/',
  },
};
```

### Cache Headers

```nginx
# Aggressive caching for static assets
location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
    add_header Vary Accept-Encoding;
    
    # Enable brotli compression if available
    brotli on;
    brotli_comp_level 6;
    brotli_types text/css application/javascript text/javascript application/json;
}
```

### Resource Hints

```html
<!-- In index.html -->
<link rel="preconnect" href="https://api.your-domain.com">
<link rel="dns-prefetch" href="https://cdn.your-domain.com">
<link rel="preload" href="/assets/fonts/main.woff2" as="font" type="font/woff2" crossorigin>
```

## Backup and Recovery

### Automated Backup Script

```bash
#!/bin/bash
# backup.sh

BACKUP_DIR="/opt/backups/specsrv-frontend"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup current deployment
log "Creating backup..."
docker save specsrv-frontend:latest | gzip > $BACKUP_DIR/frontend_$DATE.tar.gz

# Backup configuration
tar -czf $BACKUP_DIR/config_$DATE.tar.gz \
  /opt/specsrv/nginx.conf \
  /opt/specsrv/docker-compose.yml \
  /opt/specsrv/.env

# Clean old backups
find $BACKUP_DIR -name "*.tar.gz" -mtime +$RETENTION_DAYS -delete

log "Backup completed: $BACKUP_DIR/frontend_$DATE.tar.gz"
```

### Disaster Recovery

```bash
#!/bin/bash
# restore.sh

BACKUP_FILE=$1
CONFIG_BACKUP=$2

if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: $0 <backup_file> [config_backup]"
    exit 1
fi

# Stop current service
docker stop specsrv-frontend
docker rm specsrv-frontend

# Restore image
gunzip -c $BACKUP_FILE | docker load

# Restore configuration if provided
if [ -n "$CONFIG_BACKUP" ]; then
    tar -xzf $CONFIG_BACKUP -C /
fi

# Restart service
docker-compose up -d specsrv-frontend

echo "Restore completed"
```

This comprehensive deployment guide ensures that the SpecSrv frontend can be deployed reliably and securely to production environments with proper monitoring, backup, and recovery procedures in place.