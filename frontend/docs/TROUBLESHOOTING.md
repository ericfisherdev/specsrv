# SpecSrv Frontend Troubleshooting Guide

This comprehensive guide covers common issues, diagnostic procedures, and solutions for the SpecSrv frontend application across development, testing, and production environments.

## Table of Contents

1. [Quick Diagnostics](#quick-diagnostics)
2. [Development Issues](#development-issues)
3. [Build and Deployment Issues](#build-and-deployment-issues)
4. [Runtime Issues](#runtime-issues)
5. [API Communication Problems](#api-communication-problems)
6. [Authentication Issues](#authentication-issues)
7. [Performance Problems](#performance-problems)
8. [Browser Compatibility Issues](#browser-compatibility-issues)
9. [Docker and Container Issues](#docker-and-container-issues)
10. [Logging and Debugging](#logging-and-debugging)
11. [Diagnostic Tools](#diagnostic-tools)
12. [Emergency Procedures](#emergency-procedures)

## Quick Diagnostics

### Health Check Checklist

Run through this checklist for quick problem identification:

```bash
# 1. Check application status
curl -f http://localhost:3000/health || echo "❌ Frontend not responding"

# 2. Check API connectivity
curl -f http://localhost:8080/api/v1/health || echo "❌ Backend API not responding"

# 3. Check Docker containers
docker ps | grep specsrv || echo "❌ Containers not running"

# 4. Check logs
docker logs specsrv-frontend --tail 50

# 5. Check disk space
df -h | grep -E "(/$|/var|/tmp)"

# 6. Check memory usage
free -m

# 7. Check network connectivity
ping -c 3 api.your-domain.com || echo "❌ Network connectivity issues"
```

### System Information Script

```bash
#!/bin/bash
# scripts/system-info.sh

echo "=== SpecSrv Frontend System Information ==="
echo "Date: $(date)"
echo "Hostname: $(hostname)"
echo "User: $(whoami)"
echo

echo "=== Environment ==="
echo "NODE_ENV: $NODE_ENV"
echo "API_BASE_URL: $API_BASE_URL"
echo

echo "=== System Resources ==="
echo "CPU Usage:"
top -bn1 | grep load
echo
echo "Memory Usage:"
free -m
echo
echo "Disk Usage:"
df -h
echo

echo "=== Network ==="
echo "Network interfaces:"
ip addr show | grep -E "^[0-9]|inet "
echo

echo "=== Docker Status ==="
if command -v docker &> /dev/null; then
    echo "Docker version: $(docker --version)"
    echo "Running containers:"
    docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
else
    echo "Docker not installed"
fi
echo

echo "=== Application Status ==="
if curl -s -f http://localhost:3000/health > /dev/null 2>&1; then
    echo "✅ Frontend: Healthy"
else
    echo "❌ Frontend: Not responding"
fi

if curl -s -f http://localhost:8080/api/v1/health > /dev/null 2>&1; then
    echo "✅ Backend API: Healthy"
else
    echo "❌ Backend API: Not responding"
fi
```

## Development Issues

### Issue: Development Server Won't Start

**Symptoms:**
- `npm start` or `npm run dev` fails
- Port already in use errors
- Module not found errors

**Diagnostic Steps:**
```bash
# Check if port is already in use
lsof -i :3000

# Check Node.js version
node --version
npm --version

# Clear npm cache
npm cache clean --force

# Remove node_modules and reinstall
rm -rf node_modules package-lock.json
npm install
```

**Solutions:**

1. **Port Conflict:**
   ```bash
   # Kill process using port 3000
   sudo kill -9 $(lsof -t -i:3000)
   
   # Or use different port
   PORT=3001 npm start
   ```

2. **Node Version Mismatch:**
   ```bash
   # Use Node Version Manager
   nvm install 18
   nvm use 18
   ```

3. **Dependency Issues:**
   ```bash
   # Clear everything and reinstall
   rm -rf node_modules package-lock.json
   npm cache clean --force
   npm install
   ```

### Issue: Hot Reload Not Working

**Symptoms:**
- Changes don't reflect in browser
- Manual refresh required
- Webpack watch not detecting changes

**Diagnostic Steps:**
```bash
# Check webpack config
npm run dev -- --debug

# Check file system events
tail -f node_modules/.cache/webpack/
```

**Solutions:**

1. **File Watching Limits (Linux):**
   ```bash
   # Increase inotify limits
   echo fs.inotify.max_user_watches=524288 | sudo tee -a /etc/sysctl.conf
   sudo sysctl -p
   ```

2. **Webpack Configuration:**
   ```javascript
   // webpack.config.js
   module.exports = {
     devServer: {
       watchFiles: ['src/**/*'],
       hot: true,
       liveReload: true,
     },
   };
   ```

3. **Docker Volume Issues:**
   ```yaml
   # docker-compose.yml
   volumes:
     - ./frontend/src:/app/src:delegated
     - /app/node_modules  # Prevent overriding node_modules
   ```

### Issue: Module Resolution Errors

**Symptoms:**
- "Cannot resolve module" errors
- Import path issues
- TypeScript path mapping not working

**Diagnostic Steps:**
```bash
# Check module resolution
npm ls <package-name>

# Check webpack resolve configuration
npx webpack --display-modules
```

**Solutions:**

1. **Update Webpack Resolve:**
   ```javascript
   // webpack.config.js
   module.exports = {
     resolve: {
       alias: {
         '@': path.resolve(__dirname, 'src'),
         '@components': path.resolve(__dirname, 'src/components'),
         '@services': path.resolve(__dirname, 'src/services'),
       },
     },
   };
   ```

2. **Check Import Paths:**
   ```javascript
   // Use relative paths or configured aliases
   import Component from './Component.js';  // Relative
   import Component from '@/components/Component.js';  // Alias
   ```

## Build and Deployment Issues

### Issue: Build Fails with Memory Errors

**Symptoms:**
- "JavaScript heap out of memory"
- Build process crashes
- Webpack compilation errors

**Diagnostic Steps:**
```bash
# Check available memory
free -m

# Monitor memory usage during build
top -p $(pgrep node)
```

**Solutions:**

1. **Increase Node.js Memory:**
   ```bash
   # Temporary fix
   NODE_OPTIONS="--max-old-space-size=4096" npm run build
   
   # Permanent fix in package.json
   "scripts": {
     "build": "NODE_OPTIONS='--max-old-space-size=4096' webpack --mode=production"
   }
   ```

2. **Optimize Build Configuration:**
   ```javascript
   // webpack.config.js
   module.exports = {
     optimization: {
       splitChunks: {
         chunks: 'all',
         maxSize: 200000,
       },
     },
   };
   ```

### Issue: Docker Build Failures

**Symptoms:**
- Docker build context too large
- npm install fails in container
- Permission denied errors

**Diagnostic Steps:**
```bash
# Check Docker build context
docker build . --no-cache --progress=plain

# Check .dockerignore
cat .dockerignore
```

**Solutions:**

1. **Optimize .dockerignore:**
   ```dockerignore
   node_modules
   npm-debug.log
   .git
   .env.local
   coverage/
   dist/
   *.log
   ```

2. **Multi-stage Build Issues:**
   ```dockerfile
   # Ensure proper file copying
   FROM node:18-alpine AS builder
   WORKDIR /app
   COPY package*.json ./
   RUN npm ci --only=production
   COPY . .
   RUN npm run build
   
   FROM nginx:alpine
   COPY --from=builder /app/dist /usr/share/nginx/html
   ```

3. **Permission Issues:**
   ```dockerfile
   # Use non-root user
   RUN addgroup -g 1001 -S nodejs
   RUN adduser -S nextjs -u 1001
   USER nextjs
   ```

### Issue: Static Assets Not Loading

**Symptoms:**
- 404 errors for CSS/JS files
- Images not displaying
- Font files not loading

**Diagnostic Steps:**
```bash
# Check build output
ls -la dist/
ls -la dist/assets/

# Check nginx configuration
docker exec specsrv-frontend cat /etc/nginx/conf.d/default.conf
```

**Solutions:**

1. **Update Public Path:**
   ```javascript
   // webpack.config.js
   module.exports = {
     output: {
       publicPath: process.env.PUBLIC_URL || '/',
     },
   };
   ```

2. **Fix Nginx Configuration:**
   ```nginx
   # nginx.conf
   location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
     root /usr/share/nginx/html;
     expires 1y;
     add_header Cache-Control "public, immutable";
   }
   ```

## Runtime Issues

### Issue: Application Won't Load

**Symptoms:**
- Blank page
- JavaScript errors in console
- Loading spinner stuck

**Diagnostic Steps:**
```javascript
// Check browser console
console.log('Window object:', window);
console.log('Document ready state:', document.readyState);

// Check for JavaScript errors
window.addEventListener('error', (e) => {
  console.error('Global error:', e.error);
});
```

**Solutions:**

1. **Check Bundle Loading:**
   ```javascript
   // Add error boundaries
   class ErrorBoundary extends React.Component {
     componentDidCatch(error, errorInfo) {
       console.error('Error boundary caught:', error, errorInfo);
     }
     
     render() {
       if (this.state.hasError) {
         return <div>Something went wrong.</div>;
       }
       return this.props.children;
     }
   }
   ```

2. **Service Worker Issues:**
   ```javascript
   // Clear service worker
   if ('serviceWorker' in navigator) {
     navigator.serviceWorker.getRegistrations().then(registrations => {
       registrations.forEach(registration => registration.unregister());
     });
   }
   ```

### Issue: CSS Styles Not Applied

**Symptoms:**
- Unstyled content
- TailwindCSS classes not working
- Custom styles missing

**Diagnostic Steps:**
```bash
# Check CSS build output
ls -la dist/assets/css/

# Check TailwindCSS configuration
npx tailwindcss --help
```

**Solutions:**

1. **TailwindCSS Configuration:**
   ```javascript
   // tailwind.config.js
   module.exports = {
     content: [
       './src/**/*.{html,js,jsx,ts,tsx}',
       './public/index.html',
     ],
     theme: {
       extend: {},
     },
     plugins: [],
   };
   ```

2. **CSS Import Issues:**
   ```javascript
   // main.js
   import './styles/main.css';
   import 'tailwindcss/tailwind.css';
   ```

### Issue: JavaScript Errors

**Symptoms:**
- TypeError: Cannot read property
- ReferenceError: Variable not defined
- Async/await errors

**Diagnostic Steps:**
```javascript
// Enable debug mode
localStorage.setItem('debug', 'true');

// Check error details
window.addEventListener('unhandledrejection', (event) => {
  console.error('Unhandled promise rejection:', event.reason);
});
```

**Solutions:**

1. **Add Error Handling:**
   ```javascript
   // Wrap async operations
   async function safeApiCall() {
     try {
       const response = await fetch('/api/data');
       if (!response.ok) throw new Error(`HTTP ${response.status}`);
       return await response.json();
     } catch (error) {
       console.error('API call failed:', error);
       return null;
     }
   }
   ```

2. **Check Variable Definitions:**
   ```javascript
   // Use optional chaining and nullish coalescing
   const user = response?.data?.user ?? null;
   const name = user?.name || 'Unknown';
   ```

## API Communication Problems

### Issue: CORS Errors

**Symptoms:**
- "Access-Control-Allow-Origin" errors
- Preflight request failures
- Credentials not included

**Diagnostic Steps:**
```bash
# Check CORS headers
curl -H "Origin: http://localhost:3000" \
     -H "Access-Control-Request-Method: POST" \
     -H "Access-Control-Request-Headers: X-Requested-With" \
     -X OPTIONS \
     http://localhost:8080/api/v1/projects
```

**Solutions:**

1. **Backend CORS Configuration:**
   ```php
   // backend/config/packages/cors.yaml
   nelmio_cors:
     defaults:
       origin_regex: true
       allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
       allow_methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
       allow_headers: ['Content-Type', 'Authorization', 'X-Requested-With']
   ```

2. **Development Proxy:**
   ```javascript
   // webpack.config.js
   module.exports = {
     devServer: {
       proxy: {
         '/api': {
           target: 'http://localhost:8080',
           changeOrigin: true,
         },
       },
     },
   };
   ```

### Issue: API Timeouts

**Symptoms:**
- Request timeout errors
- Slow API responses
- Network connectivity issues

**Diagnostic Steps:**
```bash
# Test API response time
time curl http://localhost:8080/api/v1/health

# Check network latency
ping api.your-domain.com
```

**Solutions:**

1. **Increase Timeout:**
   ```javascript
   // ApiService.js
   class ApiService {
     constructor() {
       this.timeout = process.env.API_TIMEOUT || 30000;
     }
     
     async request(url, options = {}) {
       const controller = new AbortController();
       const timeoutId = setTimeout(() => controller.abort(), this.timeout);
       
       try {
         const response = await fetch(url, {
           ...options,
           signal: controller.signal,
         });
         clearTimeout(timeoutId);
         return response;
       } catch (error) {
         clearTimeout(timeoutId);
         if (error.name === 'AbortError') {
           throw new Error('Request timeout');
         }
         throw error;
       }
     }
   }
   ```

2. **Implement Retry Logic:**
   ```javascript
   async function retryRequest(fn, maxRetries = 3, delay = 1000) {
     for (let i = 0; i < maxRetries; i++) {
       try {
         return await fn();
       } catch (error) {
         if (i === maxRetries - 1) throw error;
         await new Promise(resolve => setTimeout(resolve, delay * (i + 1)));
       }
     }
   }
   ```

### Issue: Authentication Token Issues

**Symptoms:**
- 401 Unauthorized errors
- Token refresh failures
- Session expiration

**Diagnostic Steps:**
```javascript
// Check stored token
const token = localStorage.getItem('specsrv-token');
console.log('Stored token:', token ? 'Present' : 'Missing');

// Decode JWT token (if using JWT)
function decodeJWT(token) {
  try {
    const payload = JSON.parse(atob(token.split('.')[1]));
    console.log('Token payload:', payload);
    console.log('Token expires:', new Date(payload.exp * 1000));
    return payload;
  } catch (error) {
    console.error('Invalid token format');
    return null;
  }
}
```

**Solutions:**

1. **Token Refresh Logic:**
   ```javascript
   class AuthService {
     async refreshTokenIfNeeded() {
       const token = this.getToken();
       if (!token) return false;
       
       const payload = this.decodeToken(token);
       const expiryTime = payload.exp * 1000;
       const currentTime = Date.now();
       const refreshThreshold = 5 * 60 * 1000; // 5 minutes
       
       if (expiryTime - currentTime < refreshThreshold) {
         return await this.refreshToken();
       }
       
       return true;
     }
   }
   ```

2. **Automatic Token Cleanup:**
   ```javascript
   // Clear invalid tokens
   fetch('/api/v1/auth/verify')
     .then(response => {
       if (response.status === 401) {
         localStorage.removeItem('specsrv-token');
         window.location.href = '/login';
       }
     });
   ```

## Authentication Issues

### Issue: Login Fails

**Symptoms:**
- Invalid credentials error
- Login form not submitting
- Redirect not working after login

**Diagnostic Steps:**
```javascript
// Check form data
const formData = new FormData(loginForm);
console.log('Form data:', Object.fromEntries(formData));

// Check API response
fetch('/api/v1/auth/login', {
  method: 'POST',
  body: JSON.stringify({ email, password }),
  headers: { 'Content-Type': 'application/json' }
}).then(response => {
  console.log('Login response:', response.status, response.statusText);
  return response.json();
}).then(data => {
  console.log('Login data:', data);
});
```

**Solutions:**

1. **Form Validation:**
   ```javascript
   function validateLoginForm(email, password) {
     const errors = [];
     
     if (!email || !email.includes('@')) {
       errors.push('Valid email required');
     }
     
     if (!password || password.length < 6) {
       errors.push('Password must be at least 6 characters');
     }
     
     return errors;
   }
   ```

2. **Handle Login Errors:**
   ```javascript
   async function handleLogin(email, password) {
     try {
       const response = await authService.login(email, password);
       
       if (response.token) {
         localStorage.setItem('specsrv-token', response.token);
         window.location.href = '/dashboard';
       }
     } catch (error) {
       if (error.status === 401) {
         showError('Invalid email or password');
       } else if (error.status >= 500) {
         showError('Server error. Please try again later.');
       } else {
         showError('Login failed. Please try again.');
       }
     }
   }
   ```

### Issue: Session Management Problems

**Symptoms:**
- User logged out unexpectedly
- Session not persisting across tabs
- Multiple login prompts

**Solutions:**

1. **Implement Session Storage:**
   ```javascript
   class SessionManager {
     constructor() {
       this.storageKey = 'specsrv-session';
       this.tabId = this.generateTabId();
       this.setupStorageListener();
     }
     
     setupStorageListener() {
       window.addEventListener('storage', (e) => {
         if (e.key === this.storageKey + '-logout') {
           // Another tab logged out
           window.location.href = '/login';
         }
       });
     }
     
     logout() {
       localStorage.removeItem('specsrv-token');
       localStorage.setItem(this.storageKey + '-logout', Date.now());
       window.location.href = '/login';
     }
   }
   ```

## Performance Problems

### Issue: Slow Page Load Times

**Symptoms:**
- High Time to First Byte (TTFB)
- Large bundle sizes
- Blocking resources

**Diagnostic Steps:**
```javascript
// Measure performance
window.addEventListener('load', () => {
  setTimeout(() => {
    const perfData = performance.getEntriesByType('navigation')[0];
    console.log('Page load metrics:', {
      DNS: perfData.domainLookupEnd - perfData.domainLookupStart,
      TCP: perfData.connectEnd - perfData.connectStart,
      Request: perfData.responseStart - perfData.requestStart,
      Response: perfData.responseEnd - perfData.responseStart,
      DOM: perfData.domContentLoadedEventEnd - perfData.responseEnd,
      Total: perfData.loadEventEnd - perfData.navigationStart
    });
  }, 0);
});
```

**Solutions:**

1. **Code Splitting:**
   ```javascript
   // Dynamic imports for large components
   const LazyComponent = lazy(() => import('./HeavyComponent'));
   
   // Route-based splitting
   const Dashboard = lazy(() => import('./pages/Dashboard'));
   ```

2. **Bundle Analysis:**
   ```bash
   # Analyze bundle size
   npm install --save-dev webpack-bundle-analyzer
   npx webpack-bundle-analyzer dist/main.js
   ```

3. **Resource Optimization:**
   ```javascript
   // Preload critical resources
   const link = document.createElement('link');
   link.rel = 'preload';
   link.href = '/critical.css';
   link.as = 'style';
   document.head.appendChild(link);
   ```

### Issue: Memory Leaks

**Symptoms:**
- Increasing memory usage over time
- Browser becomes unresponsive
- Tab crashes

**Diagnostic Steps:**
```javascript
// Monitor memory usage
setInterval(() => {
  if (performance.memory) {
    console.log('Memory usage:', {
      used: Math.round(performance.memory.usedJSHeapSize / 1048576) + ' MB',
      total: Math.round(performance.memory.totalJSHeapSize / 1048576) + ' MB',
      limit: Math.round(performance.memory.jsHeapSizeLimit / 1048576) + ' MB'
    });
  }
}, 10000);
```

**Solutions:**

1. **Cleanup Event Listeners:**
   ```javascript
   class Component {
     constructor() {
       this.handleResize = this.handleResize.bind(this);
     }
     
     mount() {
       window.addEventListener('resize', this.handleResize);
     }
     
     unmount() {
       window.removeEventListener('resize', this.handleResize);
     }
   }
   ```

2. **Clear Intervals and Timeouts:**
   ```javascript
   class Timer {
     start() {
       this.intervalId = setInterval(() => {
         // Timer logic
       }, 1000);
     }
     
     stop() {
       if (this.intervalId) {
         clearInterval(this.intervalId);
         this.intervalId = null;
       }
     }
   }
   ```

## Browser Compatibility Issues

### Issue: Modern JavaScript Features Not Working

**Symptoms:**
- Syntax errors in older browsers
- Promise/async not supported
- ES6+ features not working

**Solutions:**

1. **Babel Configuration:**
   ```javascript
   // babel.config.js
   module.exports = {
     presets: [
       ['@babel/preset-env', {
         targets: {
           browsers: ['> 1%', 'last 2 versions', 'not ie <= 11']
         },
         useBuiltIns: 'entry',
         corejs: 3
       }]
     ]
   };
   ```

2. **Polyfills:**
   ```javascript
   // Add polyfills for older browsers
   import 'core-js/stable';
   import 'regenerator-runtime/runtime';
   ```

### Issue: CSS Features Not Supported

**Symptoms:**
- CSS Grid not working
- Flexbox issues
- Custom properties not supported

**Solutions:**

1. **CSS Autoprefixer:**
   ```javascript
   // postcss.config.js
   module.exports = {
     plugins: [
       require('autoprefixer'),
       require('tailwindcss'),
     ]
   };
   ```

2. **Feature Detection:**
   ```javascript
   // Check for CSS Grid support
   if (!CSS.supports('display', 'grid')) {
     // Use fallback layout
     document.body.classList.add('no-grid');
   }
   ```

## Docker and Container Issues

### Issue: Container Won't Start

**Symptoms:**
- Container exits immediately
- Port binding failures
- Volume mount issues

**Diagnostic Steps:**
```bash
# Check container logs
docker logs specsrv-frontend

# Check container status
docker ps -a

# Inspect container configuration
docker inspect specsrv-frontend

# Check resource usage
docker stats specsrv-frontend
```

**Solutions:**

1. **Port Conflicts:**
   ```bash
   # Check what's using the port
   sudo netstat -tulpn | grep :80
   
   # Use different port
   docker run -p 8080:80 specsrv-frontend
   ```

2. **Volume Mount Issues:**
   ```yaml
   # docker-compose.yml
   volumes:
     - ./frontend/dist:/usr/share/nginx/html:ro
     - ./nginx.conf:/etc/nginx/conf.d/default.conf:ro
   ```

3. **Permission Issues:**
   ```dockerfile
   # Dockerfile
   RUN chown -R nginx:nginx /usr/share/nginx/html
   USER nginx
   ```

## Logging and Debugging

### Enable Debug Logging

```javascript
// src/utils/logger.js
class Logger {
  constructor() {
    this.level = process.env.LOG_LEVEL || 'info';
    this.enabledLevels = this.getEnabledLevels(this.level);
  }
  
  getEnabledLevels(level) {
    const levels = {
      debug: ['debug', 'info', 'warn', 'error'],
      info: ['info', 'warn', 'error'],
      warn: ['warn', 'error'],
      error: ['error']
    };
    return levels[level] || levels.info;
  }
  
  log(level, message, ...args) {
    if (this.enabledLevels.includes(level)) {
      console[level](`[${new Date().toISOString()}] [${level.toUpperCase()}]`, message, ...args);
    }
  }
  
  debug(message, ...args) { this.log('debug', message, ...args); }
  info(message, ...args) { this.log('info', message, ...args); }
  warn(message, ...args) { this.log('warn', message, ...args); }
  error(message, ...args) { this.log('error', message, ...args); }
}

export const logger = new Logger();
```

### Error Tracking

```javascript
// src/utils/errorTracker.js
class ErrorTracker {
  constructor() {
    this.setupGlobalHandlers();
  }
  
  setupGlobalHandlers() {
    window.addEventListener('error', (event) => {
      this.trackError({
        type: 'javascript',
        message: event.error.message,
        stack: event.error.stack,
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno
      });
    });
    
    window.addEventListener('unhandledrejection', (event) => {
      this.trackError({
        type: 'promise',
        message: event.reason.message,
        stack: event.reason.stack
      });
    });
  }
  
  trackError(error) {
    console.error('Tracked error:', error);
    
    // Send to error tracking service
    if (process.env.SENTRY_DSN) {
      // Send to Sentry
    }
    
    // Store locally for debugging
    this.storeError(error);
  }
  
  storeError(error) {
    const errors = JSON.parse(localStorage.getItem('specsrv-errors') || '[]');
    errors.push({
      ...error,
      timestamp: new Date().toISOString(),
      url: window.location.href,
      userAgent: navigator.userAgent
    });
    
    // Keep only last 50 errors
    if (errors.length > 50) {
      errors.splice(0, errors.length - 50);
    }
    
    localStorage.setItem('specsrv-errors', JSON.stringify(errors));
  }
  
  getStoredErrors() {
    return JSON.parse(localStorage.getItem('specsrv-errors') || '[]');
  }
}

export const errorTracker = new ErrorTracker();
```

## Diagnostic Tools

### Health Check Tool

```javascript
// src/utils/diagnostics.js
export class DiagnosticTool {
  async runDiagnostics() {
    const results = {
      timestamp: new Date().toISOString(),
      browser: this.getBrowserInfo(),
      performance: await this.getPerformanceMetrics(),
      api: await this.testApiConnectivity(),
      storage: this.testStorageCapabilities(),
      network: await this.testNetworkConnectivity(),
      errors: errorTracker.getStoredErrors().slice(-10)
    };
    
    console.log('Diagnostic Results:', results);
    return results;
  }
  
  getBrowserInfo() {
    return {
      userAgent: navigator.userAgent,
      language: navigator.language,
      platform: navigator.platform,
      cookieEnabled: navigator.cookieEnabled,
      onLine: navigator.onLine
    };
  }
  
  async getPerformanceMetrics() {
    if (!performance.getEntriesByType) return null;
    
    const navigation = performance.getEntriesByType('navigation')[0];
    return {
      loadTime: navigation.loadEventEnd - navigation.navigationStart,
      domContentLoaded: navigation.domContentLoadedEventEnd - navigation.navigationStart,
      firstPaint: performance.getEntriesByName('first-paint')[0]?.startTime || null,
      firstContentfulPaint: performance.getEntriesByName('first-contentful-paint')[0]?.startTime || null
    };
  }
  
  async testApiConnectivity() {
    const tests = [
      { name: 'health', url: '/api/v1/health' },
      { name: 'auth', url: '/api/v1/auth/me' }
    ];
    
    const results = {};
    
    for (const test of tests) {
      try {
        const start = performance.now();
        const response = await fetch(test.url, { timeout: 5000 });
        const end = performance.now();
        
        results[test.name] = {
          status: response.status,
          ok: response.ok,
          responseTime: Math.round(end - start)
        };
      } catch (error) {
        results[test.name] = {
          error: error.message,
          ok: false
        };
      }
    }
    
    return results;
  }
  
  testStorageCapabilities() {
    const tests = {
      localStorage: this.testLocalStorage(),
      sessionStorage: this.testSessionStorage(),
      indexedDB: this.testIndexedDB()
    };
    
    return tests;
  }
  
  testLocalStorage() {
    try {
      const testKey = '__diagnostic_test__';
      localStorage.setItem(testKey, 'test');
      const value = localStorage.getItem(testKey);
      localStorage.removeItem(testKey);
      return { available: value === 'test' };
    } catch (error) {
      return { available: false, error: error.message };
    }
  }
  
  testSessionStorage() {
    try {
      const testKey = '__diagnostic_test__';
      sessionStorage.setItem(testKey, 'test');
      const value = sessionStorage.getItem(testKey);
      sessionStorage.removeItem(testKey);
      return { available: value === 'test' };
    } catch (error) {
      return { available: false, error: error.message };
    }
  }
  
  async testIndexedDB() {
    if (!window.indexedDB) {
      return { available: false, error: 'IndexedDB not supported' };
    }
    
    try {
      const db = await new Promise((resolve, reject) => {
        const request = indexedDB.open('__diagnostic_test__');
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
      });
      
      db.close();
      indexedDB.deleteDatabase('__diagnostic_test__');
      
      return { available: true };
    } catch (error) {
      return { available: false, error: error.message };
    }
  }
  
  async testNetworkConnectivity() {
    const tests = [
      'https://api.your-domain.com',
      'https://cdn.your-domain.com',
      'https://fonts.googleapis.com'
    ];
    
    const results = {};
    
    for (const url of tests) {
      try {
        const start = performance.now();
        const response = await fetch(url, { 
          method: 'HEAD', 
          mode: 'no-cors',
          timeout: 5000 
        });
        const end = performance.now();
        
        results[url] = {
          reachable: true,
          responseTime: Math.round(end - start)
        };
      } catch (error) {
        results[url] = {
          reachable: false,
          error: error.message
        };
      }
    }
    
    return results;
  }
}
```

## Emergency Procedures

### Rollback Procedure

```bash
#!/bin/bash
# scripts/emergency-rollback.sh

set -e

echo "🚨 EMERGENCY ROLLBACK PROCEDURE 🚨"
echo "This will rollback to the previous working version"

# Confirm rollback
read -p "Are you sure you want to rollback? (yes/no): " CONFIRM
if [ "$CONFIRM" != "yes" ]; then
    echo "Rollback cancelled"
    exit 1
fi

# Find backup image
BACKUP_IMAGE=$(docker images --format "table {{.Repository}}:{{.Tag}}" | grep backup | head -n1)

if [ -z "$BACKUP_IMAGE" ]; then
    echo "❌ No backup image found"
    exit 1
fi

echo "Rolling back to: $BACKUP_IMAGE"

# Stop current container
echo "Stopping current container..."
docker stop specsrv-frontend || true
docker rm specsrv-frontend || true

# Start backup container
echo "Starting backup container..."
docker run -d \
    --name specsrv-frontend \
    --restart unless-stopped \
    -p 80:80 \
    -p 443:443 \
    -v /opt/specsrv/ssl:/etc/nginx/ssl:ro \
    -v /opt/specsrv/logs:/var/log/nginx \
    --network specsrv-network \
    $BACKUP_IMAGE

# Wait for service to start
echo "Waiting for service to start..."
sleep 10

# Verify rollback
if curl -f http://localhost/health > /dev/null 2>&1; then
    echo "✅ Rollback successful"
    echo "Service is now running on backup version: $BACKUP_IMAGE"
else
    echo "❌ Rollback failed - service not responding"
    exit 1
fi
```

### System Recovery

```bash
#!/bin/bash
# scripts/system-recovery.sh

echo "🔧 SYSTEM RECOVERY PROCEDURE 🔧"

# Check system resources
echo "Checking system resources..."
FREE_SPACE=$(df / | awk 'NR==2{print $4}')
FREE_MEMORY=$(free -m | awk 'NR==2{print $7}')

if [ "$FREE_SPACE" -lt 1000000 ]; then
    echo "⚠️  Low disk space detected"
    # Clean up Docker images
    docker system prune -f
    docker image prune -a -f
fi

if [ "$FREE_MEMORY" -lt 500 ]; then
    echo "⚠️  Low memory detected"
    # Restart services to free memory
    docker restart specsrv-frontend
fi

# Check service health
echo "Checking service health..."
for i in {1..5}; do
    if curl -f http://localhost/health > /dev/null 2>&1; then
        echo "✅ Service is healthy"
        break
    else
        echo "Attempt $i: Service not responding, waiting..."
        sleep 10
    fi
    
    if [ "$i" -eq 5 ]; then
        echo "❌ Service failed to recover"
        echo "Attempting full restart..."
        docker-compose down
        docker-compose up -d
    fi
done

echo "Recovery procedure completed"
```

This comprehensive troubleshooting guide provides systematic approaches to diagnosing and resolving common issues across all aspects of the SpecSrv frontend application, from development through production deployment.