#!/bin/sh

# Health check script for SpecSrv container
# Can be used by Docker, Kubernetes, or other orchestration tools

set -e

# Configuration
HEALTH_ENDPOINT="${HEALTH_ENDPOINT:-http://localhost:8080/health}"
READINESS_ENDPOINT="${READINESS_ENDPOINT:-http://localhost:8080/health/readiness}"
LIVENESS_ENDPOINT="${LIVENESS_ENDPOINT:-http://localhost:8080/health/liveness}"
TIMEOUT="${HEALTH_TIMEOUT:-10}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

# Check if curl is available
if ! command -v curl > /dev/null 2>&1; then
    log "${RED}ERROR: curl is not installed${NC}"
    exit 1
fi

# Function to check an endpoint
check_endpoint() {
    local endpoint="$1"
    local name="$2"
    
    log "Checking $name at $endpoint..."
    
    if response=$(curl -s --fail --max-time "$TIMEOUT" "$endpoint" 2>&1); then
        log "${GREEN}✓ $name check passed${NC}"
        return 0
    else
        log "${RED}✗ $name check failed: $response${NC}"
        return 1
    fi
}

# Function to check detailed health status
check_detailed_health() {
    local endpoint="$1"
    
    log "Fetching detailed health status..."
    
    if response=$(curl -s --max-time "$TIMEOUT" "$endpoint" 2>&1); then
        # Parse JSON response (basic parsing without jq)
        if echo "$response" | grep -q '"status":"healthy"'; then
            log "${GREEN}✓ Application is healthy${NC}"
            
            # Extract and display check details
            if echo "$response" | grep -q '"database"'; then
                if echo "$response" | grep -q '"database":{"healthy":true'; then
                    log "  - Database: ${GREEN}OK${NC}"
                else
                    log "  - Database: ${RED}FAILED${NC}"
                fi
            fi
            
            if echo "$response" | grep -q '"frontend_assets"'; then
                if echo "$response" | grep -q '"frontend_assets":{"healthy":true'; then
                    log "  - Frontend Assets: ${GREEN}OK${NC}"
                else
                    log "  - Frontend Assets: ${RED}FAILED${NC}"
                fi
            fi
            
            if echo "$response" | grep -q '"filesystem"'; then
                if echo "$response" | grep -q '"filesystem":{"healthy":true'; then
                    log "  - Filesystem: ${GREEN}OK${NC}"
                else
                    log "  - Filesystem: ${RED}FAILED${NC}"
                fi
            fi
            
            return 0
        else
            log "${RED}✗ Application is unhealthy${NC}"
            log "Response: $response"
            return 1
        fi
    else
        log "${RED}✗ Failed to fetch health status: $response${NC}"
        return 1
    fi
}

# Main execution
main() {
    local check_type="${1:-health}"
    local exit_code=0
    
    case "$check_type" in
        "liveness")
            log "Performing liveness check..."
            check_endpoint "$LIVENESS_ENDPOINT" "Liveness" || exit_code=1
            ;;
        "readiness")
            log "Performing readiness check..."
            check_endpoint "$READINESS_ENDPOINT" "Readiness" || exit_code=1
            ;;
        "health"|"detailed")
            log "Performing detailed health check..."
            check_detailed_health "$HEALTH_ENDPOINT" || exit_code=1
            ;;
        "all")
            log "Performing all health checks..."
            check_endpoint "$LIVENESS_ENDPOINT" "Liveness" || exit_code=1
            check_endpoint "$READINESS_ENDPOINT" "Readiness" || exit_code=1
            check_detailed_health "$HEALTH_ENDPOINT" || exit_code=1
            ;;
        *)
            echo "Usage: $0 [liveness|readiness|health|detailed|all]"
            echo ""
            echo "Environment variables:"
            echo "  HEALTH_ENDPOINT      - Health check endpoint (default: http://localhost:8080/health)"
            echo "  READINESS_ENDPOINT   - Readiness endpoint (default: http://localhost:8080/health/readiness)"
            echo "  LIVENESS_ENDPOINT    - Liveness endpoint (default: http://localhost:8080/health/liveness)"
            echo "  HEALTH_TIMEOUT       - Request timeout in seconds (default: 10)"
            exit 1
            ;;
    esac
    
    if [ $exit_code -eq 0 ]; then
        log "${GREEN}All checks passed successfully!${NC}"
    else
        log "${RED}Some checks failed!${NC}"
    fi
    
    exit $exit_code
}

# Run main function
main "$@"