# SpecSrv Frontend Rollback Procedures

This document provides comprehensive procedures for rolling back the SpecSrv frontend application in case of deployment failures, critical bugs, or other emergency situations.

## Table of Contents

1. [Rollback Overview](#rollback-overview)
2. [Pre-Rollback Assessment](#pre-rollback-assessment)
3. [Rollback Types](#rollback-types)
4. [Emergency Rollback](#emergency-rollback)
5. [Planned Rollback](#planned-rollback)
6. [Database Rollback Considerations](#database-rollback-considerations)
7. [Blue-Green Rollback](#blue-green-rollback)
8. [Rollback Verification](#rollback-verification)
9. [Post-Rollback Procedures](#post-rollback-procedures)
10. [Rollback Prevention](#rollback-prevention)

## Rollback Overview

A rollback is the process of reverting the frontend application to a previous, known-good version. This process should be:

- **Fast**: Complete within minutes
- **Reliable**: Minimal risk of introducing new issues
- **Auditable**: All actions logged and tracked
- **Testable**: Verified through automated checks

### When to Rollback

Immediate rollback scenarios:
- **Critical security vulnerabilities**
- **Application not loading (blank page)**
- **Data corruption or loss**
- **Performance degradation >50%**
- **Core functionality broken**

Consider rollback scenarios:
- **High error rates (>5%)**
- **User complaints about key features**
- **Monitoring alerts indicating issues**
- **Failed smoke tests in production**

## Pre-Rollback Assessment

Before initiating a rollback, perform this quick assessment:

### Decision Matrix

```bash
#!/bin/bash
# scripts/rollback-decision.sh

echo "=== ROLLBACK DECISION MATRIX ==="
echo

echo "1. Is this a CRITICAL issue? (security, data loss, complete outage)"
read -p "Critical? (y/n): " CRITICAL

echo "2. Can the issue be fixed with a hotfix in <30 minutes?"
read -p "Quick fix possible? (y/n): " QUICK_FIX

echo "3. How many users are affected?"
echo "   a) All users"
echo "   b) >50% of users"
echo "   c) <50% of users"
echo "   d) Specific user group"
read -p "User impact (a/b/c/d): " USER_IMPACT

echo "4. Is there a known-good rollback target?"
read -p "Rollback target available? (y/n): " ROLLBACK_AVAILABLE

# Decision logic
if [[ "$CRITICAL" == "y" && "$ROLLBACK_AVAILABLE" == "y" ]]; then
    echo
    echo "🚨 RECOMMENDATION: IMMEDIATE ROLLBACK"
    echo "Critical issue detected with available rollback target"
elif [[ "$QUICK_FIX" == "y" && "$USER_IMPACT" != "a" ]]; then
    echo
    echo "⚠️  RECOMMENDATION: ATTEMPT HOTFIX FIRST"
    echo "Quick fix possible and not all users affected"
else
    echo
    echo "🔄 RECOMMENDATION: EVALUATE ROLLBACK"
    echo "Consider rollback vs fix based on impact and complexity"
fi
```

### System Status Check

```bash
#!/bin/bash
# scripts/pre-rollback-check.sh

echo "=== PRE-ROLLBACK SYSTEM CHECK ==="
echo "Timestamp: $(date)"
echo

# Check current deployment
echo "Current deployment:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Image}}" | grep specsrv

echo
echo "Available rollback targets:"
docker images | grep specsrv-frontend | head -5

echo
echo "System resources:"
echo "Memory: $(free -m | awk 'NR==2{printf "%.1f%% used\n", $3*100/$2 }')"
echo "Disk: $(df -h / | awk 'NR==2{print $5 " used"}')"
echo "Load: $(uptime | cut -d',' -f3-)"

echo
echo "Current error rate:"
# Check error logs from last 5 minutes
ERROR_COUNT=$(docker logs specsrv-frontend --since=5m 2>&1 | grep -i error | wc -l)
TOTAL_REQUESTS=$(docker logs specsrv-frontend --since=5m 2>&1 | wc -l)
if [ $TOTAL_REQUESTS -gt 0 ]; then
    ERROR_RATE=$(( (ERROR_COUNT * 100) / TOTAL_REQUESTS ))
    echo "Error rate: ${ERROR_RATE}% (${ERROR_COUNT}/${TOTAL_REQUESTS})"
else
    echo "Error rate: No recent requests"
fi

echo
echo "Health check status:"
if curl -f -s http://localhost/health > /dev/null; then
    echo "✅ Health check: PASSING"
else
    echo "❌ Health check: FAILING"
fi
```

## Rollback Types

### 1. Container Rollback (Fastest)

Roll back to a previous container version without rebuilding.

**Time: 1-3 minutes**

```bash
#!/bin/bash
# scripts/container-rollback.sh

set -e

VERSION=${1}
if [ -z "$VERSION" ]; then
    echo "Available versions:"
    docker images ghcr.io/your-org/specsrv-frontend --format "table {{.Tag}}\t{{.CreatedAt}}"
    echo
    read -p "Enter version to rollback to: " VERSION
fi

IMAGE="ghcr.io/your-org/specsrv-frontend:$VERSION"

echo "🔄 Starting container rollback to $VERSION"
echo "Timestamp: $(date)"

# Pre-rollback backup
echo "Creating backup of current deployment..."
CURRENT_IMAGE=$(docker inspect specsrv-frontend --format='{{.Config.Image}}' 2>/dev/null || echo "")
BACKUP_TAG="rollback-backup-$(date +%Y%m%d-%H%M%S)"

if [ -n "$CURRENT_IMAGE" ]; then
    docker tag "$CURRENT_IMAGE" "$BACKUP_TAG"
else
    echo "Warning: Could not determine current image for backup"
fi

# Stop current container
echo "Stopping current container..."
docker stop specsrv-frontend || true
docker rm specsrv-frontend || true

# Start rollback version
echo "Starting rollback version..."
docker run -d \
    --name specsrv-frontend \
    --restart unless-stopped \
    -p 80:80 \
    -p 443:443 \
    -v /opt/specsrv/ssl:/etc/nginx/ssl:ro \
    -v /opt/specsrv/logs:/var/log/nginx \
    -v /opt/specsrv/cache:/var/cache/nginx \
    --network specsrv-network \
    --label "rollback=true" \
    --label "rollback-from=$(docker inspect specsrv-frontend --format='{{.Config.Image}}' 2>/dev/null || echo 'unknown')" \
    --label "rollback-timestamp=$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
    $IMAGE

echo "Waiting for container to start..."
sleep 10

# Verify rollback
if curl -f -s http://localhost/health > /dev/null; then
    echo "✅ Container rollback successful!"
    echo "Frontend is now running version: $VERSION"
    
    # Log rollback
    echo "$(date): Container rollback to $VERSION completed successfully" >> /var/log/specsrv-rollback.log
else
    echo "❌ Container rollback failed - health check failing"
    echo "Attempting to restore previous version..."
    
    # Attempt to restore
    docker stop specsrv-frontend || true
    docker rm specsrv-frontend || true
    if [ -n "$BACKUP_TAG" ]; then
        docker run -d --name specsrv-frontend "$BACKUP_TAG" || echo "Failed to restore backup"
    else
        echo "Failed to restore backup: No backup tag available"
    fi
    
    exit 1
fi
```

### 2. Configuration Rollback

Roll back only configuration changes while keeping the same application version.

```bash
#!/bin/bash
# scripts/config-rollback.sh

set -e

CONFIG_VERSION=${1:-"previous"}
BACKUP_TIMESTAMP=$(date +%Y%m%d-%H%M%S)

echo "🔧 Starting configuration rollback"

# Backup current config
echo "Backing up current configuration..."
cp /opt/specsrv/nginx.conf /opt/specsrv/nginx.conf.backup.${BACKUP_TIMESTAMP}
cp /opt/specsrv/.env /opt/specsrv/.env.backup.${BACKUP_TIMESTAMP}

# Restore previous config
if [ "$CONFIG_VERSION" = "previous" ]; then
    PREVIOUS_CONFIG=$(ls -t /opt/specsrv/backups/nginx.conf.* | head -1)
    PREVIOUS_ENV=$(ls -t /opt/specsrv/backups/.env.* | head -1)
    
    if [ -n "$PREVIOUS_CONFIG" ] && [ -n "$PREVIOUS_ENV" ]; then
        echo "Restoring configuration from: $PREVIOUS_CONFIG"
        cp "$PREVIOUS_CONFIG" /opt/specsrv/nginx.conf
        cp "$PREVIOUS_ENV" /opt/specsrv/.env
    else
        echo "❌ No previous configuration found"
        exit 1
    fi
else
    echo "Restoring specific configuration version: $CONFIG_VERSION"
    cp "/opt/specsrv/backups/nginx.conf.$CONFIG_VERSION" /opt/specsrv/nginx.conf
    cp "/opt/specsrv/backups/.env.$CONFIG_VERSION" /opt/specsrv/.env
fi

# Reload nginx configuration
echo "Reloading nginx configuration..."
docker exec specsrv-frontend nginx -s reload

# Verify configuration
echo "Verifying configuration..."
if docker exec specsrv-frontend nginx -t; then
    echo "✅ Configuration rollback successful"
else
    echo "❌ Configuration rollback failed - restoring backup"
    cp /opt/specsrv/nginx.conf.backup.${BACKUP_TIMESTAMP} /opt/specsrv/nginx.conf
    docker exec specsrv-frontend nginx -s reload
    exit 1
fi
```

### 3. Full Stack Rollback

Roll back both frontend and backend to a consistent previous state.

```bash
#!/bin/bash
# scripts/full-stack-rollback.sh

set -e

RELEASE_VERSION=${1}
if [ -z "$RELEASE_VERSION" ]; then
    echo "Available releases:"
    git tag -l --sort=-version:refname | head -10
    echo
    read -p "Enter release version to rollback to: " RELEASE_VERSION
fi

echo "🔄 Starting full stack rollback to $RELEASE_VERSION"

# Check if release exists
if ! git rev-parse "$RELEASE_VERSION" >/dev/null 2>&1; then
    echo "❌ Release $RELEASE_VERSION not found"
    exit 1
fi

# Create rollback checkpoint
CHECKPOINT_NAME="rollback-checkpoint-$(date +%Y%m%d-%H%M%S)"
echo "Creating rollback checkpoint: $CHECKPOINT_NAME"
git tag "$CHECKPOINT_NAME"

# Stop services
echo "Stopping services..."
docker-compose down

# Rollback code
echo "Rolling back code to $RELEASE_VERSION..."
git checkout "$RELEASE_VERSION"

# Restore database (if needed)
echo "Checking if database rollback is needed..."
if [ -f "migrations/rollback-$RELEASE_VERSION.sql" ]; then
    echo "⚠️  Database rollback required"
    read -p "Continue with database rollback? (y/n): " CONFIRM_DB
    if [ "$CONFIRM_DB" = "y" ]; then
        # Backup current database
        docker exec specsrv-postgres pg_dump -U specsrv specsrv > "/opt/specsrv/db-backup-$(date +%Y%m%d-%H%M%S).sql"
        
        # Apply rollback migration
        docker exec -i specsrv-postgres psql -U specsrv specsrv < "migrations/rollback-$RELEASE_VERSION.sql"
    fi
fi

# Rebuild and start services
echo "Building and starting services..."
docker-compose build
docker-compose up -d

# Wait for services
echo "Waiting for services to start..."
sleep 30

# Verify rollback
echo "Verifying rollback..."
if curl -f -s http://localhost/health > /dev/null && \
   curl -f -s http://localhost:8080/api/v1/health > /dev/null; then
    echo "✅ Full stack rollback successful!"
    echo "System rolled back to: $RELEASE_VERSION"
else
    echo "❌ Full stack rollback failed"
    echo "Attempting to restore checkpoint..."
    git checkout "$CHECKPOINT_NAME"
    docker-compose up -d
    exit 1
fi
```

## Emergency Rollback

For critical situations requiring immediate action.

### One-Command Emergency Rollback

```bash
#!/bin/bash
# scripts/emergency-rollback.sh

set -e

echo "🚨🚨🚨 EMERGENCY ROLLBACK INITIATED 🚨🚨🚨"
echo "Timestamp: $(date)"
echo "User: $(whoami)"
echo "Host: $(hostname)"

# No confirmation required for emergency rollback
echo "This is an EMERGENCY rollback - no confirmation required"

# Find the most recent backup/working version
BACKUP_IMAGE=$(docker images --format "{{.Repository}}:{{.Tag}}" | grep -E "(backup|stable)" | head -1)
PREVIOUS_IMAGE=$(docker images ghcr.io/your-org/specsrv-frontend --format "{{.Repository}}:{{.Tag}}" | sed -n '2p')

TARGET_IMAGE="${BACKUP_IMAGE:-$PREVIOUS_IMAGE}"

if [ -z "$TARGET_IMAGE" ]; then
    echo "❌ CRITICAL: No rollback target found!"
    echo "Available images:"
    docker images ghcr.io/your-org/specsrv-frontend
    exit 1
fi

echo "🔄 Rolling back to: $TARGET_IMAGE"

# Create emergency log entry
echo "$(date -u +%Y-%m-%dT%H:%M:%SZ): EMERGENCY ROLLBACK to $TARGET_IMAGE by $(whoami)" >> /var/log/specsrv-emergency.log

# Immediate rollback
docker stop specsrv-frontend >/dev/null 2>&1 || true
docker rm specsrv-frontend >/dev/null 2>&1 || true

docker run -d \
    --name specsrv-frontend \
    --restart unless-stopped \
    -p 80:80 \
    -p 443:443 \
    -v /opt/specsrv/ssl:/etc/nginx/ssl:ro \
    -v /opt/specsrv/logs:/var/log/nginx \
    --network specsrv-network \
    --label "emergency-rollback=true" \
    --label "emergency-timestamp=$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
    $TARGET_IMAGE

# Quick verification
sleep 5
for i in {1..12}; do
    if curl -f -s http://localhost/health >/dev/null 2>&1; then
        echo "✅ EMERGENCY ROLLBACK SUCCESSFUL"
        echo "Service restored with: $TARGET_IMAGE"
        
        # Send notification
        if command -v mail >/dev/null 2>&1; then
            echo "Emergency rollback completed at $(date) to $TARGET_IMAGE" | \
                mail -s "SpecSrv Emergency Rollback Completed" admin@your-domain.com
        fi
        
        break
    else
        echo "Attempt $i/12: Waiting for service..."
        sleep 5
    fi
    
    if [ $i -eq 12 ]; then
        echo "❌ EMERGENCY ROLLBACK FAILED"
        echo "Manual intervention required!"
        exit 1
    fi
done

echo "Emergency rollback completed at $(date)"
```

### Emergency Rollback Triggers

Set up automated triggers for emergency situations:

```bash
#!/bin/bash
# scripts/rollback-triggers.sh

# Monitor health check failures
while true; do
    FAIL_COUNT=0
    
    # Check health endpoint 5 times
    for i in {1..5}; do
        if ! curl -f -s http://localhost/health >/dev/null 2>&1; then
            ((FAIL_COUNT++))
        fi
        sleep 2
    done
    
    # Trigger emergency rollback if 4+ failures
    if [ $FAIL_COUNT -ge 4 ]; then
        echo "Health check failures detected: $FAIL_COUNT/5"
        echo "Triggering emergency rollback..."
        /opt/specsrv/scripts/emergency-rollback.sh
        break
    fi
    
    # Check error rate
    ERROR_RATE=$(docker logs specsrv-frontend --since=1m 2>&1 | grep -c "ERROR" || echo 0)
    if [ $ERROR_RATE -gt 50 ]; then
        echo "High error rate detected: $ERROR_RATE errors in last minute"
        echo "Triggering emergency rollback..."
        /opt/specsrv/scripts/emergency-rollback.sh
        break
    fi
    
    sleep 30
done
```

## Planned Rollback

For non-emergency situations where you have time to plan and execute carefully.

### Planned Rollback Procedure

```bash
#!/bin/bash
# scripts/planned-rollback.sh

set -e

VERSION=${1}
REASON=${2:-"Planned rollback"}

echo "=== PLANNED ROLLBACK PROCEDURE ==="
echo "Target version: ${VERSION:-TBD}"
echo "Reason: $REASON"
echo "Timestamp: $(date)"
echo

# Confirmation required for planned rollback
echo "This is a PLANNED rollback. Please confirm details:"
echo
if [ -z "$VERSION" ]; then
    echo "Available versions:"
    docker images ghcr.io/your-org/specsrv-frontend --format "table {{.Tag}}\t{{.CreatedAt}}" | head -10
    echo
    read -p "Enter target version: " VERSION
fi

echo "Rollback details:"
echo "  From: $(docker inspect specsrv-frontend --format='{{.Config.Image}}' 2>/dev/null || echo 'current')"
echo "  To: ghcr.io/your-org/specsrv-frontend:$VERSION"
echo "  Reason: $REASON"
echo

read -p "Proceed with rollback? (yes/no): " CONFIRM
if [ "$CONFIRM" != "yes" ]; then
    echo "Rollback cancelled"
    exit 0
fi

# Pre-rollback steps
echo "Executing pre-rollback steps..."

# 1. Notify users (if configured)
if [ -f "/opt/specsrv/scripts/notify-maintenance.sh" ]; then
    echo "Notifying users of maintenance..."
    /opt/specsrv/scripts/notify-maintenance.sh "System rollback in progress"
fi

# 2. Create full backup
echo "Creating complete backup..."
BACKUP_DIR="/opt/specsrv/backups/rollback-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Backup current container
CURRENT_IMAGE=$(docker inspect --format='{{.Config.Image}}' specsrv-frontend 2>/dev/null || docker inspect --format='{{.Image}}' specsrv-frontend)
echo "Backing up image: $CURRENT_IMAGE"
docker save "$CURRENT_IMAGE" > "$BACKUP_DIR/current-container.tar"

# Backup configuration
cp -r /opt/specsrv/config "$BACKUP_DIR/"
cp /opt/specsrv/.env "$BACKUP_DIR/"

# Backup logs
cp -r /opt/specsrv/logs "$BACKUP_DIR/"

# 3. Run pre-rollback tests
echo "Running pre-rollback tests..."
if [ -f "/opt/specsrv/scripts/pre-rollback-tests.sh" ]; then
    /opt/specsrv/scripts/pre-rollback-tests.sh || {
        echo "❌ Pre-rollback tests failed"
        exit 1
    }
fi

# 4. Execute rollback
echo "Executing rollback..."
TARGET_IMAGE="ghcr.io/your-org/specsrv-frontend:$VERSION"

# Pull target image
docker pull "$TARGET_IMAGE"

# Graceful shutdown
echo "Gracefully stopping current service..."
docker exec specsrv-frontend nginx -s quit || docker stop specsrv-frontend
docker rm specsrv-frontend

# Start rollback version
echo "Starting rollback version..."
docker run -d \
    --name specsrv-frontend \
    --restart unless-stopped \
    -p 80:80 \
    -p 443:443 \
    -v /opt/specsrv/ssl:/etc/nginx/ssl:ro \
    -v /opt/specsrv/logs:/var/log/nginx \
    -v /opt/specsrv/cache:/var/cache/nginx \
    --network specsrv-network \
    --label "rollback=planned" \
    --label "rollback-reason=$REASON" \
    --label "rollback-timestamp=$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
    --label "rollback-backup=$BACKUP_DIR" \
    "$TARGET_IMAGE"

# 5. Post-rollback verification
echo "Running post-rollback verification..."
sleep 10

# Health check
for i in {1..20}; do
    if curl -f -s http://localhost/health >/dev/null 2>&1; then
        echo "✅ Health check passed"
        break
    else
        echo "Attempt $i/20: Waiting for service..."
        sleep 3
    fi
    
    if [ $i -eq 20 ]; then
        echo "❌ Health check failed after rollback"
        echo "Attempting to restore from backup..."
        
        docker stop specsrv-frontend || true
        docker rm specsrv-frontend || true
        docker load < "$BACKUP_DIR/current-container.tar"
        docker run -d --name specsrv-frontend specsrv-frontend
        
        exit 1
    fi
done

# Smoke tests
if [ -f "/opt/specsrv/scripts/smoke-tests.sh" ]; then
    echo "Running smoke tests..."
    /opt/specsrv/scripts/smoke-tests.sh || {
        echo "❌ Smoke tests failed after rollback"
        exit 1
    }
fi

# 6. Post-rollback cleanup
echo "Completing rollback..."

# Log rollback
echo "$(date -u +%Y-%m-%dT%H:%M:%SZ): Planned rollback to $VERSION completed. Reason: $REASON. Backup: $BACKUP_DIR" >> /var/log/specsrv-rollback.log

# Clean up old images (keep last 3)
docker images ghcr.io/your-org/specsrv-frontend --format "{{.Repository}}:{{.Tag}}" | tail -n +4 | xargs -r docker rmi

# Notify completion
if [ -f "/opt/specsrv/scripts/notify-maintenance.sh" ]; then
    /opt/specsrv/scripts/notify-maintenance.sh "Rollback completed successfully"
fi

echo "✅ PLANNED ROLLBACK COMPLETED SUCCESSFULLY"
echo "System is now running version: $VERSION"
echo "Backup location: $BACKUP_DIR"
```

## Database Rollback Considerations

### Database Schema Compatibility

```bash
#!/bin/bash
# scripts/check-db-compatibility.sh

CURRENT_VERSION=${1:-$(git describe --tags)}
TARGET_VERSION=${2}

echo "Checking database compatibility between $CURRENT_VERSION and $TARGET_VERSION"

# Check for breaking schema changes
git diff "$TARGET_VERSION".."$CURRENT_VERSION" --name-only | grep -E "(migration|schema)" > /tmp/db_changes.txt

if [ -s /tmp/db_changes.txt ]; then
    echo "⚠️  Database schema changes detected:"
    cat /tmp/db_changes.txt
    echo
    echo "Manual database rollback may be required"
    echo "Review the following migration files:"
    
    while IFS= read -r file; do
        echo "  - $file"
        git diff "$TARGET_VERSION".."$CURRENT_VERSION" -- "$file" | head -20
        echo "  ..."
        echo
    done < /tmp/db_changes.txt
    
    echo "Do you need to create a database rollback script?"
    read -p "Continue with rollback? (y/n): " CONTINUE
    if [ "$CONTINUE" != "y" ]; then
        echo "Rollback cancelled"
        exit 1
    fi
else
    echo "✅ No database schema changes detected"
fi
```

### Database Backup Before Rollback

```bash
#!/bin/bash
# scripts/backup-database.sh

BACKUP_NAME="rollback-backup-$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="/opt/specsrv/db-backups"

echo "Creating database backup: $BACKUP_NAME"

mkdir -p "$BACKUP_DIR"

# Create PostgreSQL backup
docker exec specsrv-postgres pg_dump -U specsrv -d specsrv --verbose > "$BACKUP_DIR/$BACKUP_NAME.sql"

# Compress backup
gzip "$BACKUP_DIR/$BACKUP_NAME.sql"

# Verify backup
if [ -f "$BACKUP_DIR/$BACKUP_NAME.sql.gz" ]; then
    BACKUP_SIZE=$(du -h "$BACKUP_DIR/$BACKUP_NAME.sql.gz" | cut -f1)
    echo "✅ Database backup created: $BACKUP_NAME.sql.gz ($BACKUP_SIZE)"
    echo "$BACKUP_DIR/$BACKUP_NAME.sql.gz"
else
    echo "❌ Database backup failed"
    exit 1
fi

# Clean up old backups (keep last 10)
ls -t "$BACKUP_DIR"/*.sql.gz | tail -n +11 | xargs -r rm
```

## Blue-Green Rollback

For zero-downtime rollbacks using blue-green deployment.

```bash
#!/bin/bash
# scripts/blue-green-rollback.sh

set -e

echo "🔄 Starting Blue-Green Rollback"

# Determine current and target environments
CURRENT_ENV=$(docker ps --format "{{.Names}}" | grep -E "(blue|green)" | grep specsrv-frontend)
if [[ "$CURRENT_ENV" == *"blue"* ]]; then
    TARGET_ENV="green"
    CURRENT_COLOR="blue"
else
    TARGET_ENV="blue"
    CURRENT_COLOR="green"
fi

echo "Current environment: $CURRENT_COLOR"
echo "Target environment: $TARGET_ENV"

# Check if target environment exists and is healthy
if docker ps -q --filter name=specsrv-frontend-$TARGET_ENV >/dev/null 2>&1; then
    echo "Target environment found, checking health..."
    
    # Get target environment port
    TARGET_PORT=$(docker port specsrv-frontend-$TARGET_ENV 80/tcp | cut -d: -f2)
    
    if curl -f -s "http://localhost:$TARGET_PORT/health" >/dev/null 2>&1; then
        echo "✅ Target environment is healthy"
    else
        echo "❌ Target environment is not healthy"
        exit 1
    fi
else
    echo "❌ Target environment not found"
    echo "Blue-green rollback requires both environments to be running"
    exit 1
fi

# Switch load balancer
echo "Switching load balancer from $CURRENT_COLOR to $TARGET_ENV..."

# Update nginx upstream configuration
cat > /tmp/upstream.conf << EOF
upstream frontend_servers {
    server specsrv-frontend-$TARGET_ENV:80;
}
EOF

# Apply new configuration
docker cp /tmp/upstream.conf nginx-lb:/etc/nginx/conf.d/upstream.conf
docker exec nginx-lb nginx -s reload

# Verify switch
echo "Waiting for traffic switch..."
sleep 5

if curl -f -s http://localhost/health >/dev/null 2>&1; then
    echo "✅ Blue-Green rollback successful"
    echo "Traffic is now routed to: $TARGET_ENV"
    
    # Optional: Stop old environment after delay
    echo "Keeping old environment ($CURRENT_COLOR) running for 5 minutes..."
    echo "Use 'docker stop specsrv-frontend-$CURRENT_COLOR' to stop it manually"
    
    # Schedule automatic cleanup
    (sleep 300 && docker stop specsrv-frontend-$CURRENT_COLOR) &
    
else
    echo "❌ Blue-Green rollback failed"
    echo "Rolling back load balancer configuration..."
    
    cat > /tmp/upstream.conf << EOF
upstream frontend_servers {
    server specsrv-frontend-$CURRENT_COLOR:80;
}
EOF
    
    docker cp /tmp/upstream.conf nginx-lb:/etc/nginx/conf.d/upstream.conf
    docker exec nginx-lb nginx -s reload
    
    exit 1
fi
```

## Rollback Verification

### Automated Verification Suite

```bash
#!/bin/bash
# scripts/rollback-verification.sh

set -e

echo "🔍 Starting Rollback Verification"
echo "Timestamp: $(date)"

VERIFICATION_FAILED=false

# 1. Health Check
echo "1. Health Check..."
if curl -f -s http://localhost/health >/dev/null 2>&1; then
    echo "   ✅ Health check passed"
else
    echo "   ❌ Health check failed"
    VERIFICATION_FAILED=true
fi

# 2. Page Load Test
echo "2. Page Load Test..."
LOAD_TIME=$(curl -o /dev/null -s -w "%{time_total}" http://localhost/)
if (( $(echo "$LOAD_TIME < 3.0" | bc -l) )); then
    echo "   ✅ Page load time: ${LOAD_TIME}s"
else
    echo "   ❌ Page load too slow: ${LOAD_TIME}s"
    VERIFICATION_FAILED=true
fi

# 3. API Connectivity
echo "3. API Connectivity..."
if curl -f -s http://localhost/api/v1/health >/dev/null 2>&1; then
    echo "   ✅ API connectivity working"
else
    echo "   ❌ API connectivity failed"
    VERIFICATION_FAILED=true
fi

# 4. Static Assets
echo "4. Static Assets..."
ASSET_ERRORS=0
for asset in "/assets/css/main.css" "/assets/js/main.js" "/favicon.ico"; do
    if ! curl -f -s "http://localhost$asset" >/dev/null 2>&1; then
        echo "   ❌ Asset not loading: $asset"
        ((ASSET_ERRORS++))
    fi
done

if [ $ASSET_ERRORS -eq 0 ]; then
    echo "   ✅ All static assets loading"
else
    echo "   ❌ $ASSET_ERRORS static assets failed to load"
    VERIFICATION_FAILED=true
fi

# 5. Error Rate Check
echo "5. Error Rate Check..."
ERROR_COUNT=$(docker logs specsrv-frontend --since=2m 2>&1 | grep -c "ERROR" || echo 0)
if [ $ERROR_COUNT -lt 5 ]; then
    echo "   ✅ Error rate acceptable: $ERROR_COUNT errors"
else
    echo "   ❌ High error rate: $ERROR_COUNT errors in last 2 minutes"
    VERIFICATION_FAILED=true
fi

# 6. Memory Usage
echo "6. Memory Usage..."
MEMORY_USAGE=$(docker stats specsrv-frontend --no-stream --format "{{.MemUsage}}" | cut -d'/' -f1 | sed 's/MiB//')
if (( $(echo "$MEMORY_USAGE < 500" | bc -l) )); then
    echo "   ✅ Memory usage normal: ${MEMORY_USAGE}MB"
else
    echo "   ❌ High memory usage: ${MEMORY_USAGE}MB"
    VERIFICATION_FAILED=true
fi

# 7. SSL Certificate
echo "7. SSL Certificate..."
if [ -f "/opt/specsrv/ssl/cert.pem" ]; then
    CERT_EXPIRY=$(openssl x509 -in /opt/specsrv/ssl/cert.pem -noout -enddate | cut -d= -f2)
    DAYS_UNTIL_EXPIRY=$(( ($(date -d "$CERT_EXPIRY" +%s) - $(date +%s)) / 86400 ))
    
    if [ $DAYS_UNTIL_EXPIRY -gt 30 ]; then
        echo "   ✅ SSL certificate valid for $DAYS_UNTIL_EXPIRY days"
    else
        echo "   ⚠️  SSL certificate expires in $DAYS_UNTIL_EXPIRY days"
    fi
else
    echo "   ⚠️  SSL certificate not found"
fi

# Final result
echo
echo "=== VERIFICATION RESULT ==="
if [ "$VERIFICATION_FAILED" = true ]; then
    echo "❌ ROLLBACK VERIFICATION FAILED"
    echo "Manual inspection required"
    exit 1
else
    echo "✅ ROLLBACK VERIFICATION PASSED"
    echo "System is operating normally"
fi

# Log verification result
echo "$(date -u +%Y-%m-%dT%H:%M:%SZ): Rollback verification $([ "$VERIFICATION_FAILED" = true ] && echo "FAILED" || echo "PASSED")" >> /var/log/specsrv-rollback.log
```

## Post-Rollback Procedures

### Post-Rollback Checklist

```bash
#!/bin/bash
# scripts/post-rollback-checklist.sh

echo "=== POST-ROLLBACK CHECKLIST ==="
echo "Complete the following tasks after rollback:"
echo

CHECKLIST=(
    "Verify system functionality with smoke tests"
    "Monitor error logs for 30 minutes"
    "Check application performance metrics"
    "Notify stakeholders of rollback completion"
    "Document rollback reason and lessons learned"
    "Schedule root cause analysis meeting"
    "Update monitoring alerts if needed"
    "Plan forward-fix for rolled back changes"
    "Review rollback procedure effectiveness"
    "Update deployment documentation"
)

for i in "${!CHECKLIST[@]}"; do
    echo "$((i+1)). ${CHECKLIST[i]}"
    read -p "   Completed? (y/n): " COMPLETED
    if [ "$COMPLETED" != "y" ]; then
        echo "   ⚠️  Mark as incomplete - requires attention"
    else
        echo "   ✅ Completed"
    fi
    echo
done

echo "Post-rollback checklist completed at $(date)"
```

### Root Cause Analysis Template

```bash
#!/bin/bash
# scripts/create-rca-template.sh

RCA_FILE="/opt/specsrv/docs/rca-$(date +%Y%m%d-%H%M%S).md"

cat > "$RCA_FILE" << 'EOF'
# Root Cause Analysis - Rollback Incident

**Date:** $(date)
**Rollback Performed By:** $(whoami)
**Systems Affected:** SpecSrv Frontend

## Incident Summary

### Timeline
- **Issue Detected:** 
- **Rollback Initiated:** 
- **Rollback Completed:** 
- **Service Restored:** 

### Impact
- **Users Affected:** 
- **Duration of Outage:** 
- **Business Impact:** 

## Root Cause Analysis

### What Happened?
[Detailed description of the issue]

### Why Did It Happen?
[Root cause analysis - go through the "5 Whys"]

1. Why did the issue occur?
2. Why wasn't it caught earlier?
3. Why didn't our tests catch this?
4. Why wasn't the monitoring sufficient?
5. Why did the rollback take as long as it did?

### What Was the Impact?
[Quantify the impact on users, business, etc.]

## Response Evaluation

### What Went Well?
- [ ] Detection time
- [ ] Communication
- [ ] Rollback procedure
- [ ] Team coordination

### What Could Be Improved?
- [ ] Earlier detection
- [ ] Faster response
- [ ] Better communication
- [ ] Improved procedures

## Action Items

### Immediate (within 24 hours)
- [ ] Action item 1
- [ ] Action item 2

### Short-term (within 1 week)
- [ ] Action item 1
- [ ] Action item 2

### Long-term (within 1 month)
- [ ] Action item 1
- [ ] Action item 2

## Prevention Measures

### Process Improvements
- [ ] Updated deployment checklist
- [ ] Enhanced testing procedures
- [ ] Improved monitoring

### Technical Improvements
- [ ] Additional automated tests
- [ ] Better monitoring alerts
- [ ] Improved rollback procedures

## Lessons Learned

[Key takeaways from this incident]

---
**RCA Completed By:** [Name]
**Date:** [Date]
**Reviewed By:** [Name]
EOF

echo "RCA template created: $RCA_FILE"
echo "Please complete the root cause analysis"
```

## Rollback Prevention

### Pre-Deployment Checks

```bash
#!/bin/bash
# scripts/pre-deployment-checks.sh

echo "🔍 PRE-DEPLOYMENT CHECKS"
echo "Running comprehensive checks before deployment..."

CHECKS_FAILED=0

# 1. Code Quality Checks
echo "1. Code Quality..."
if npm run lint && npm run test; then
    echo "   ✅ Code quality checks passed"
else
    echo "   ❌ Code quality checks failed"
    ((CHECKS_FAILED++))
fi

# 2. Build Verification
echo "2. Build Verification..."
if npm run build; then
    echo "   ✅ Build successful"
else
    echo "   ❌ Build failed"
    ((CHECKS_FAILED++))
fi

# 3. Security Scan
echo "3. Security Scan..."
if npm audit --audit-level=high; then
    echo "   ✅ No high-severity security issues"
else
    echo "   ❌ Security issues detected"
    ((CHECKS_FAILED++))
fi

# 4. Performance Budget
echo "4. Performance Budget..."
BUNDLE_SIZE=$(du -k dist/main.js | cut -f1)
if [ $BUNDLE_SIZE -lt 500 ]; then
    echo "   ✅ Bundle size within budget: ${BUNDLE_SIZE}KB"
else
    echo "   ❌ Bundle size too large: ${BUNDLE_SIZE}KB"
    ((CHECKS_FAILED++))
fi

# 5. API Compatibility
echo "5. API Compatibility..."
if curl -f -s http://localhost:8080/api/v1/health >/dev/null 2>&1; then
    echo "   ✅ API compatibility verified"
else
    echo "   ❌ API compatibility issues"
    ((CHECKS_FAILED++))
fi

# Results
echo
if [ $CHECKS_FAILED -eq 0 ]; then
    echo "✅ ALL PRE-DEPLOYMENT CHECKS PASSED"
    echo "Deployment can proceed"
    exit 0
else
    echo "❌ $CHECKS_FAILED PRE-DEPLOYMENT CHECKS FAILED"
    echo "Deployment should not proceed"
    exit 1
fi
```

### Deployment Gates

```yaml
# .github/workflows/deploy-with-gates.yml
name: Deploy with Safety Gates

on:
  push:
    branches: [main]

jobs:
  safety-gates:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Run Pre-deployment Checks
        run: ./scripts/pre-deployment-checks.sh
      
      - name: Check for Breaking Changes
        id: check_changes
        run: |
          # Compare with previous release
          git diff HEAD~1 --name-only | grep -E "(api|schema|config)" > changes.txt
          if [ -s changes.txt ]; then
            echo "Breaking changes detected - manual approval required"
            echo "breaking_changes=true" >> $GITHUB_OUTPUT
          fi
      
      - name: Manual Approval Gate
        if: steps.check_changes.outputs.breaking_changes == 'true'
        uses: trstringer/manual-approval@v1
        with:
          secret: ${{ secrets.GITHUB_TOKEN }}
          approvers: admin1,admin2
          minimum-approvals: 1

  deploy:
    needs: safety-gates
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to Production
        run: ./scripts/deploy.sh
```

This comprehensive rollback documentation ensures that the SpecSrv frontend can be quickly and safely reverted to a previous working state when issues arise, with proper procedures for different scenarios and thorough verification processes.