# SpecSrv Environment Configuration Guide

This document provides comprehensive documentation for configuring environment variables and settings across different deployment environments for the SpecSrv frontend application.

## Table of Contents

1. [Environment Overview](#environment-overview)
2. [Environment Types](#environment-types)
3. [Configuration Files](#configuration-files)
4. [Environment Variables](#environment-variables)
5. [Build Configuration](#build-configuration)
6. [Runtime Configuration](#runtime-configuration)
7. [Security Considerations](#security-considerations)
8. [Environment-Specific Settings](#environment-specific-settings)
9. [Configuration Validation](#configuration-validation)
10. [Troubleshooting](#troubleshooting)

## Environment Overview

The SpecSrv frontend supports multiple environments with different configurations:

- **Development** - Local development with hot reload
- **Testing** - Automated testing environment
- **Staging** - Pre-production testing
- **Production** - Live production environment

Each environment has specific requirements for API endpoints, feature flags, security settings, and performance optimizations.

## Environment Types

### Development Environment
- Local development server
- Hot reload enabled
- Debug logging
- Development API endpoints
- Relaxed security policies

### Testing Environment
- Automated testing
- Mock API responses
- Test database
- Minimal logging
- Isolated test data

### Staging Environment
- Production-like setup
- Real API endpoints
- Production build
- Enhanced logging
- User acceptance testing

### Production Environment
- Optimized build
- CDN integration
- Security hardened
- Performance monitoring
- Error tracking

## Configuration Files

### Environment File Structure

```
frontend/
├── .env                    # Default environment variables
├── .env.local              # Local overrides (git ignored)
├── .env.development        # Development-specific variables
├── .env.test              # Testing environment variables
├── .env.staging           # Staging environment variables
├── .env.production        # Production environment variables
└── config/
    ├── development.js      # Development configuration
    ├── testing.js         # Testing configuration
    ├── staging.js         # Staging configuration
    └── production.js      # Production configuration
```

### Base Environment File

```bash
# .env
# Base configuration that applies to all environments

# Application Information
APP_NAME=SpecSrv
APP_VERSION=1.0.0
APP_DESCRIPTION="Specification Management System"

# Default API Configuration
API_BASE_URL=http://localhost:8080
API_VERSION=v1
API_TIMEOUT=30000

# Feature Flags
ENABLE_DEBUG_MODE=false
ENABLE_OFFLINE_MODE=true
ENABLE_DARK_MODE=true
ENABLE_ANALYTICS=false

# UI Configuration
DEFAULT_THEME=light
DEFAULT_LANGUAGE=en
DEFAULT_TIMEZONE=UTC

# Cache Configuration
CACHE_ENABLED=true
CACHE_TTL=300000

# Security Configuration
ENABLE_CSP=false
ENABLE_HSTS=false
ENABLE_XSS_PROTECTION=true
```

## Environment Variables

### Application Variables

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `NODE_ENV` | Environment type | `development` | Yes |
| `APP_NAME` | Application name | `SpecSrv` | No |
| `APP_VERSION` | Application version | `1.0.0` | No |
| `APP_DESCRIPTION` | Application description | - | No |
| `PUBLIC_URL` | Public URL for assets | `/` | No |

### API Configuration

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `API_BASE_URL` | Backend API base URL | `http://localhost:8080` | Yes |
| `API_VERSION` | API version | `v1` | No |
| `API_TIMEOUT` | Request timeout (ms) | `30000` | No |
| `WS_URL` | WebSocket URL | - | No |

### Authentication Variables

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `AUTH_TOKEN_KEY` | localStorage key for auth token | `specsrv-token` | No |
| `AUTH_REFRESH_THRESHOLD` | Token refresh threshold (ms) | `300000` | No |
| `AUTH_REDIRECT_URL` | Post-login redirect | `/dashboard` | No |

### Feature Flags

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `ENABLE_DEBUG_MODE` | Enable debug logging | `false` | No |
| `ENABLE_OFFLINE_MODE` | Enable offline support | `true` | No |
| `ENABLE_DARK_MODE` | Enable dark mode | `true` | No |
| `ENABLE_ANALYTICS` | Enable analytics tracking | `false` | No |
| `ENABLE_FILE_UPLOAD` | Enable file upload | `true` | No |
| `ENABLE_REAL_TIME` | Enable real-time features | `false` | No |

### UI Configuration

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `DEFAULT_THEME` | Default theme | `light` | No |
| `DEFAULT_LANGUAGE` | Default language | `en` | No |
| `DEFAULT_TIMEZONE` | Default timezone | `UTC` | No |
| `PAGE_SIZE` | Default page size | `20` | No |
| `MAX_FILE_SIZE` | Max file upload size (bytes) | `10485760` | No |

### Performance Variables

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `CACHE_ENABLED` | Enable client-side caching | `true` | No |
| `CACHE_TTL` | Cache time-to-live (ms) | `300000` | No |
| `LAZY_LOADING` | Enable lazy loading | `true` | No |
| `BUNDLE_ANALYZER` | Enable bundle analyzer | `false` | No |

### Security Variables

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `ENABLE_CSP` | Enable Content Security Policy | `false` | No |
| `ENABLE_HSTS` | Enable HTTP Strict Transport Security | `false` | No |
| `ENABLE_XSS_PROTECTION` | Enable XSS protection | `true` | No |
| `ALLOWED_HOSTS` | Comma-separated allowed hosts | - | No |

### Monitoring Variables

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `SENTRY_DSN` | Sentry error tracking DSN | - | No |
| `GOOGLE_ANALYTICS_ID` | Google Analytics tracking ID | - | No |
| `LOG_LEVEL` | Logging level | `info` | No |
| `ENABLE_PERFORMANCE_MONITORING` | Enable performance monitoring | `false` | No |

### CDN and Assets

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `CDN_URL` | CDN base URL | - | No |
| `ASSETS_URL` | Assets base URL | - | No |
| `FONT_URL` | Font files URL | - | No |

## Build Configuration

### Webpack Environment Configuration

```javascript
// webpack.config.js
const path = require('path');
const webpack = require('webpack');
const dotenv = require('dotenv');

// Load base environment variables first
const baseEnv = dotenv.config({
  path: '.env'
}).parsed || {};

// Load environment-specific variables
const envSpecific = dotenv.config({
  path: `.env.${process.env.NODE_ENV || 'development'}`
}).parsed || {};

// Merge environments (env-specific overrides base)
const env = { ...baseEnv, ...envSpecific };

// Filter to only allowed variables (whitelist approach)
const allowedVars = [
  'APP_NAME', 'APP_VERSION', 'APP_DESCRIPTION',
  'API_BASE_URL', 'API_VERSION', 'API_TIMEOUT',
  'AUTH_TOKEN_KEY', 'AUTH_REFRESH_THRESHOLD', 'AUTH_REDIRECT_URL',
  'ENABLE_DEBUG_MODE', 'ENABLE_OFFLINE_MODE', 'ENABLE_DARK_MODE',
  'ENABLE_ANALYTICS', 'ENABLE_FILE_UPLOAD', 'ENABLE_REAL_TIME',
  'DEFAULT_THEME', 'DEFAULT_LANGUAGE', 'DEFAULT_TIMEZONE',
  'PAGE_SIZE', 'MAX_FILE_SIZE', 'CACHE_ENABLED', 'CACHE_TTL',
  'LAZY_LOADING', 'ENABLE_CSP', 'ENABLE_HSTS', 'ENABLE_XSS_PROTECTION',
  'LOG_LEVEL', 'PUBLIC_URL', 'WS_URL'
];

// Create environment variables for DefinePlugin with filtering
const envKeys = allowedVars.reduce((prev, key) => {
  if (env[key] !== undefined) {
    prev[`process.env.${key}`] = JSON.stringify(env[key]);
  }
  return prev;
}, {});

module.exports = {
  // ... other config
  
  plugins: [
    new webpack.DefinePlugin(envKeys),
    
    // Environment-specific plugins
    ...(process.env.NODE_ENV === 'production' ? [
      new webpack.optimize.AggressiveMergingPlugin(),
    ] : []),
    
    ...(process.env.BUNDLE_ANALYZER === 'true' ? [
      new (require('webpack-bundle-analyzer')).BundleAnalyzerPlugin(),
    ] : []),
  ],
  
  // Development server configuration
  devServer: {
    port: process.env.DEV_PORT || 3000,
    proxy: {
      '/api': {
        target: process.env.API_BASE_URL || 'http://localhost:8080',
        changeOrigin: true,
      },
    },
  },
};
```

### Environment-Specific Builds

```json
{
  "scripts": {
    "build": "webpack --mode=production",
    "build:dev": "webpack --mode=development",
    "build:staging": "NODE_ENV=staging webpack --mode=production",
    "build:production": "NODE_ENV=production webpack --mode=production",
    "start:dev": "NODE_ENV=development webpack serve",
    "start:staging": "NODE_ENV=staging webpack serve --mode=production"
  }
}
```

## Runtime Configuration

### Configuration Service

```javascript
// src/services/ConfigService.js
class ConfigService {
  constructor() {
    this.config = this.loadConfig();
  }
  
  loadConfig() {
    // Load from environment variables (set by webpack DefinePlugin)
    const envConfig = {
      app: {
        name: process.env.APP_NAME || 'SpecSrv',
        version: process.env.APP_VERSION || '1.0.0',
        description: process.env.APP_DESCRIPTION || '',
      },
      api: {
        baseUrl: process.env.API_BASE_URL || 'http://localhost:8080',
        version: process.env.API_VERSION || 'v1',
        timeout: parseInt(process.env.API_TIMEOUT) || 30000,
      },
      auth: {
        tokenKey: process.env.AUTH_TOKEN_KEY || 'specsrv-token',
        refreshThreshold: parseInt(process.env.AUTH_REFRESH_THRESHOLD) || 300000,
        redirectUrl: process.env.AUTH_REDIRECT_URL || '/dashboard',
      },
      features: {
        debugMode: process.env.ENABLE_DEBUG_MODE === 'true',
        offlineMode: process.env.ENABLE_OFFLINE_MODE === 'true',
        darkMode: process.env.ENABLE_DARK_MODE === 'true',
        analytics: process.env.ENABLE_ANALYTICS === 'true',
        fileUpload: process.env.ENABLE_FILE_UPLOAD === 'true',
        realTime: process.env.ENABLE_REAL_TIME === 'true',
      },
      ui: {
        defaultTheme: process.env.DEFAULT_THEME || 'light',
        defaultLanguage: process.env.DEFAULT_LANGUAGE || 'en',
        defaultTimezone: process.env.DEFAULT_TIMEZONE || 'UTC',
        pageSize: parseInt(process.env.PAGE_SIZE) || 20,
        maxFileSize: parseInt(process.env.MAX_FILE_SIZE) || 10485760,
      },
      performance: {
        cacheEnabled: process.env.CACHE_ENABLED === 'true',
        cacheTTL: parseInt(process.env.CACHE_TTL) || 300000,
        lazyLoading: process.env.LAZY_LOADING === 'true',
      },
      security: {
        enableCSP: process.env.ENABLE_CSP === 'true',
        enableHSTS: process.env.ENABLE_HSTS === 'true',
        enableXSSProtection: process.env.ENABLE_XSS_PROTECTION === 'true',
        allowedHosts: process.env.ALLOWED_HOSTS ? 
          process.env.ALLOWED_HOSTS.split(',') : [],
      },
      monitoring: {
        sentryDSN: process.env.SENTRY_DSN || null,
        googleAnalyticsId: process.env.GOOGLE_ANALYTICS_ID || null,
        logLevel: process.env.LOG_LEVEL || 'info',
        performanceMonitoring: process.env.ENABLE_PERFORMANCE_MONITORING === 'true',
      },
      cdn: {
        baseUrl: process.env.CDN_URL || null,
        assetsUrl: process.env.ASSETS_URL || null,
        fontUrl: process.env.FONT_URL || null,
      },
    };
    
    // Merge with runtime overrides
    const runtimeConfig = this.loadRuntimeConfig();
    return this.mergeConfig(envConfig, runtimeConfig);
  }
  
  loadRuntimeConfig() {
    // Load configuration from API or localStorage
    try {
      const storedConfig = localStorage.getItem('specsrv-config');
      return storedConfig ? JSON.parse(storedConfig) : {};
    } catch (error) {
      console.warn('Failed to load runtime configuration:', error);
      return {};
    }
  }
  
  mergeConfig(envConfig, runtimeConfig) {
    // Deep merge configurations with runtime config taking precedence
    return this.deepMerge(envConfig, runtimeConfig);
  }
  
  deepMerge(target, source) {
    const result = { ...target };
    
    for (const key in source) {
      if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
        result[key] = this.deepMerge(result[key] || {}, source[key]);
      } else {
        result[key] = source[key];
      }
    }
    
    return result;
  }
  
  get(path, defaultValue = undefined) {
    const value = this.getNestedValue(this.config, path);
    return value !== undefined ? value : defaultValue;
  }
  
  getNestedValue(obj, path) {
    return path.split('.').reduce((current, key) => current?.[key], obj);
  }
  
  set(path, value) {
    this.setNestedValue(this.config, path, value);
    this.saveRuntimeConfig();
  }
  
  setNestedValue(obj, path, value) {
    const keys = path.split('.');
    const lastKey = keys.pop();
    const target = keys.reduce((current, key) => {
      if (!current[key] || typeof current[key] !== 'object') {
        current[key] = {};
      }
      return current[key];
    }, obj);
    
    target[lastKey] = value;
  }
  
  saveRuntimeConfig() {
    try {
      // Save only user-modifiable configuration
      const runtimeConfig = {
        ui: this.config.ui,
        features: {
          darkMode: this.config.features.darkMode,
          // Don't save security-sensitive features
        },
      };
      
      localStorage.setItem('specsrv-config', JSON.stringify(runtimeConfig));
    } catch (error) {
      console.warn('Failed to save runtime configuration:', error);
    }
  }
}

export const configService = new ConfigService();
export default configService;
```

### Usage Examples

```javascript
// Using the configuration service
import config from './services/ConfigService.js';

// Get configuration values
const apiUrl = config.get('api.baseUrl');
const isDarkMode = config.get('features.darkMode');
const pageSize = config.get('ui.pageSize', 20);

// Set configuration values
config.set('ui.theme', 'dark');
config.set('features.debugMode', true);

// Check feature flags
if (config.get('features.analytics')) {
  // Initialize analytics
}
```

## Environment-Specific Settings

### Development Environment

```bash
# .env.development
NODE_ENV=development

# API Configuration
API_BASE_URL=http://localhost:8080
API_TIMEOUT=10000

# Feature Flags
ENABLE_DEBUG_MODE=true
ENABLE_ANALYTICS=false
ENABLE_CSP=false

# Development Tools
ENABLE_HOT_RELOAD=true
ENABLE_SOURCE_MAPS=true
BUNDLE_ANALYZER=false

# UI Configuration
DEFAULT_THEME=light
LOG_LEVEL=debug

# Development Server
DEV_PORT=3000
DEV_HOST=localhost
PROXY_API=true
```

### Testing Environment

```bash
# .env.test
NODE_ENV=test

# API Configuration
API_BASE_URL=http://localhost:8080
API_TIMEOUT=5000

# Feature Flags
ENABLE_DEBUG_MODE=false
ENABLE_ANALYTICS=false
ENABLE_OFFLINE_MODE=false

# Testing Configuration
TEST_USER_EMAIL=test@example.com
TEST_USER_PASSWORD=password123
TEST_JWT_TOKEN=mock-jwt-token

# Database
DATABASE_URL=postgresql://test_user:test_pass@localhost:5433/specsrv_test

# Disable external services in tests
ENABLE_SENTRY=false
ENABLE_GOOGLE_ANALYTICS=false
```

### Staging Environment

```bash
# .env.staging
NODE_ENV=staging

# API Configuration
API_BASE_URL=https://api-staging.your-domain.com
API_TIMEOUT=30000

# Feature Flags
ENABLE_DEBUG_MODE=false
ENABLE_ANALYTICS=true
ENABLE_CSP=true
ENABLE_HSTS=false

# Security
ALLOWED_HOSTS=staging.your-domain.com

# Monitoring
SENTRY_DSN=https://your-sentry-dsn@sentry.io/project-id
LOG_LEVEL=info

# CDN Configuration
CDN_URL=https://cdn-staging.your-domain.com
ASSETS_URL=https://cdn-staging.your-domain.com/assets

# UI Configuration
DEFAULT_THEME=light
PAGE_SIZE=20
```

### Production Environment

```bash
# .env.production
NODE_ENV=production

# API Configuration
API_BASE_URL=https://api.your-domain.com
API_TIMEOUT=30000
WS_URL=wss://api.your-domain.com/ws

# Feature Flags
ENABLE_DEBUG_MODE=false
ENABLE_ANALYTICS=true
ENABLE_CSP=true
ENABLE_HSTS=true
ENABLE_XSS_PROTECTION=true

# Security
ALLOWED_HOSTS=your-domain.com,www.your-domain.com

# Monitoring
SENTRY_DSN=https://your-sentry-dsn@sentry.io/project-id
GOOGLE_ANALYTICS_ID=GA_MEASUREMENT_ID
LOG_LEVEL=warn
ENABLE_PERFORMANCE_MONITORING=true

# CDN Configuration
CDN_URL=https://cdn.your-domain.com
ASSETS_URL=https://cdn.your-domain.com/assets
FONT_URL=https://fonts.googleapis.com

# Performance
CACHE_ENABLED=true
CACHE_TTL=3600000
LAZY_LOADING=true

# UI Configuration
DEFAULT_THEME=light
PAGE_SIZE=20
MAX_FILE_SIZE=10485760
```

## Security Considerations

### Sensitive Variables

Never commit sensitive information to version control:

```bash
# Sensitive variables that should be set via CI/CD or secrets management
SENTRY_DSN=
GOOGLE_ANALYTICS_ID=
DATABASE_URL=
JWT_SECRET=
ENCRYPTION_KEY=
```

### Environment Variable Security

```javascript
// src/utils/environmentSecurity.js
export class EnvironmentSecurity {
  static validateEnvironment() {
    const requiredVars = [
      'API_BASE_URL',
      'NODE_ENV'
    ];
    
    const missingVars = requiredVars.filter(
      varName => !process.env[varName]
    );
    
    if (missingVars.length > 0) {
      throw new Error(`Missing required environment variables: ${missingVars.join(', ')}`);
    }
  }
  
  static sanitizeForClient(envVars) {
    // Only expose client-safe variables
    const clientSafeKeys = [
      'APP_NAME',
      'APP_VERSION',
      'API_BASE_URL',
      'DEFAULT_THEME',
      'ENABLE_DEBUG_MODE'
    ];
    
    return Object.keys(envVars)
      .filter(key => clientSafeKeys.includes(key))
      .reduce((obj, key) => {
        obj[key] = envVars[key];
        return obj;
      }, {});
  }
  
  static maskSensitiveValues(value) {
    if (typeof value !== 'string') return value;
    
    // Mask API keys, tokens, etc.
    if (value.match(/^(sk_|pk_|api_)/i)) {
      return value.substring(0, 8) + '...';
    }
    
    // Mask URLs with credentials
    if (value.includes('://') && value.includes('@')) {
      return value.replace(/\/\/[^@]+@/, '//***:***@');
    }
    
    return value;
  }
}
```

## Configuration Validation

### Environment Validation

```javascript
// src/utils/configValidator.js
import Joi from 'joi';

const configSchema = Joi.object({
  app: Joi.object({
    name: Joi.string().required(),
    version: Joi.string().required(),
  }),
  
  api: Joi.object({
    baseUrl: Joi.string().uri().required(),
    version: Joi.string().required(),
    timeout: Joi.number().min(1000).max(120000),
  }),
  
  features: Joi.object({
    debugMode: Joi.boolean(),
    offlineMode: Joi.boolean(),
    darkMode: Joi.boolean(),
    analytics: Joi.boolean(),
  }),
  
  ui: Joi.object({
    defaultTheme: Joi.string().valid('light', 'dark'),
    pageSize: Joi.number().min(10).max(100),
    maxFileSize: Joi.number().min(1024).max(104857600), // 1KB to 100MB
  }),
  
  security: Joi.object({
    enableCSP: Joi.boolean(),
    enableHSTS: Joi.boolean(),
    allowedHosts: Joi.array().items(Joi.string().hostname()),
  }),
});

export function validateConfig(config) {
  const { error, value } = configSchema.validate(config, {
    allowUnknown: true,
    stripUnknown: false,
  });
  
  if (error) {
    throw new Error(`Configuration validation error: ${error.message}`);
  }
  
  return value;
}

export function validateEnvironment() {
  try {
    const config = {
      app: {
        name: process.env.APP_NAME,
        version: process.env.APP_VERSION,
      },
      api: {
        baseUrl: process.env.API_BASE_URL,
        version: process.env.API_VERSION,
        timeout: parseInt(process.env.API_TIMEOUT),
      },
      // ... other config sections
    };
    
    validateConfig(config);
    console.log('✅ Environment configuration is valid');
  } catch (error) {
    console.error('❌ Environment validation failed:', error.message);
    process.exit(1);
  }
}
```

### Runtime Configuration Check

```javascript
// src/utils/configCheck.js
export class ConfigurationChecker {
  constructor(config) {
    this.config = config;
    this.checks = [];
  }
  
  addCheck(name, checkFn) {
    this.checks.push({ name, checkFn });
  }
  
  async runChecks() {
    const results = [];
    
    for (const { name, checkFn } of this.checks) {
      try {
        const result = await checkFn(this.config);
        results.push({
          name,
          status: result ? 'pass' : 'fail',
          message: result === true ? 'OK' : result || 'Failed'
        });
      } catch (error) {
        results.push({
          name,
          status: 'error',
          message: error.message
        });
      }
    }
    
    return results;
  }
}

// Initialize configuration checker
const checker = new ConfigurationChecker(config);

// Add checks
checker.addCheck('API Connectivity', async (config) => {
  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 5000);
    
    const response = await fetch(`${config.get('api.baseUrl')}/health`, {
      signal: controller.signal
    });
    
    clearTimeout(timeoutId);
    return response.ok;
  } catch (error) {
    if (error.name === 'AbortError') {
      return 'API endpoint not reachable';
    }
    return 'API endpoint not reachable';
  }
});

checker.addCheck('LocalStorage Available', (config) => {
  try {
    localStorage.setItem('test', 'test');
    localStorage.removeItem('test');
    return true;
  } catch {
    return 'LocalStorage not available';
  }
});

checker.addCheck('Required Features', (config) => {
  const requiredFeatures = ['darkMode', 'offlineMode'];
  const missingFeatures = requiredFeatures.filter(
    feature => config.get(`features.${feature}`) === undefined
  );
  
  return missingFeatures.length === 0 || 
    `Missing features: ${missingFeatures.join(', ')}`;
});

export default checker;
```

## Troubleshooting

### Common Configuration Issues

#### 1. API Connection Failures

```javascript
// Check API configuration
console.log('API Base URL:', process.env.API_BASE_URL);
console.log('API Timeout:', process.env.API_TIMEOUT);

// Test API connectivity
fetch(`${process.env.API_BASE_URL}/health`)
  .then(response => console.log('API Status:', response.status))
  .catch(error => console.error('API Error:', error));
```

#### 2. Environment Variable Not Loading

```javascript
// Debug environment variables
console.log('NODE_ENV:', process.env.NODE_ENV);
console.log('All env vars:', process.env);

// Check if variables are defined at build time
console.log('Build-time API URL:', process.env.API_BASE_URL);
```

#### 3. Configuration Override Issues

```javascript
// Check configuration precedence
console.log('Environment config:', envConfig);
console.log('Runtime config:', runtimeConfig);
console.log('Final config:', finalConfig);
```

### Debug Configuration

```javascript
// src/utils/debugConfig.js
export function debugConfiguration() {
  if (process.env.NODE_ENV !== 'development') {
    return;
  }
  
  console.group('🔧 Configuration Debug');
  
  console.log('Environment:', process.env.NODE_ENV);
  console.log('API Base URL:', process.env.API_BASE_URL);
  console.log('Features:', {
    debugMode: process.env.ENABLE_DEBUG_MODE,
    analytics: process.env.ENABLE_ANALYTICS,
    darkMode: process.env.ENABLE_DARK_MODE,
  });
  
  console.log('Security:', {
    CSP: process.env.ENABLE_CSP,
    HSTS: process.env.ENABLE_HSTS,
    XSS: process.env.ENABLE_XSS_PROTECTION,
  });
  
  console.groupEnd();
}
```

### Environment Validation Script

```bash
#!/bin/bash
# scripts/validate-env.sh

set -e

ENV=${1:-development}

echo "Validating environment: $ENV"

# Check if environment file exists
ENV_FILE=".env.$ENV"
if [ ! -f "$ENV_FILE" ]; then
    echo "❌ Environment file $ENV_FILE not found"
    exit 1
fi

# Source environment file
source "$ENV_FILE"

# Validate required variables
REQUIRED_VARS=("NODE_ENV" "API_BASE_URL")

for var in "${REQUIRED_VARS[@]}"; do
    if [ -z "${!var}" ]; then
        echo "❌ Required variable $var is not set"
        exit 1
    fi
    echo "✅ $var: ${!var}"
done

# Validate API connectivity
if command -v curl &> /dev/null; then
    echo "Testing API connectivity..."
    if curl -f -s "${API_BASE_URL}/health" > /dev/null; then
        echo "✅ API is reachable"
    else
        echo "⚠️  API is not reachable"
    fi
fi

echo "Environment validation completed"
```

This comprehensive environment configuration guide ensures that the SpecSrv frontend can be properly configured across all deployment environments with appropriate security, validation, and troubleshooting procedures.