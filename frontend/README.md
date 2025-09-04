# SpecSrv Frontend

The SpecSrv frontend is a modern web application built with HTMX, Alpine.js, and Tailwind CSS. It communicates with the backend API to provide a responsive and interactive user experience.

## Quick Start

### Prerequisites

- Node.js 18+ and npm
- Docker and Docker Compose (for containerized development)

### Development Setup

1. **Install Dependencies**
   ```bash
   cd frontend
   npm install
   ```

2. **Start Development Server**
   ```bash
   npm run dev
   ```
   The frontend will be available at `http://localhost:3000`

3. **Start Backend API**
   ```bash
   # In the backend directory
   docker-compose -f docker-compose.dev.yml up specsrv-backend -d
   ```
   The API will be available at `http://localhost:8080`

### Docker Development

For a fully containerized development environment:

```bash
# Start both frontend and backend
docker-compose -f docker-compose.dev.yml up

# Frontend: http://localhost:3000
# Backend API: http://localhost:8080
```

## Architecture Overview

### Technology Stack

- **HTMX** - Server-driven interactivity and AJAX handling
- **Alpine.js** - Reactive JavaScript framework for UI components
- **TailwindCSS** - Utility-first CSS framework
- **Webpack** - Module bundler and development server
- **Jest** - Testing framework
- **Playwright** - End-to-end testing

### Directory Structure

```
frontend/
├── src/                    # Source code
│   ├── components/         # Reusable UI components
│   ├── pages/             # Page-specific components and HTML
│   ├── services/          # API communication services
│   ├── styles/            # CSS and styling
│   ├── utils/             # Utility functions and classes
│   ├── js/                # Legacy JavaScript modules
│   └── templates/         # Base HTML templates
├── public/                # Static assets
├── dist/                  # Build output
├── tests/                 # Test files
├── webpack.config.js      # Webpack configuration
├── tailwind.config.js     # Tailwind CSS configuration
├── package.json           # Dependencies and scripts
└── Dockerfile             # Production Docker image
```

## Development Workflow

### Available Scripts

```bash
# Development
npm run dev                # Start development server with hot reload
npm run watch             # Watch for changes and rebuild

# Building
npm run build             # Production build
npm run build:dev         # Development build

# Testing
npm test                  # Run unit tests
npm run test:watch        # Run tests in watch mode
npm run test:e2e          # Run end-to-end tests
npm run test:coverage     # Run tests with coverage

# Linting and Formatting
npm run lint              # ESLint
npm run format            # Prettier
```

### Hot Reload Development

The development server supports hot reloading for:
- CSS changes (instant reload)
- JavaScript changes (page refresh)
- HTML template changes (page refresh)

### Environment Variables

Create a `.env` file in the frontend directory:

```env
# API Configuration
API_BASE_URL=http://localhost:8080/api/v1
API_TIMEOUT=5000

# Development Settings
NODE_ENV=development
HOT_RELOAD=true

# Feature Flags
ENABLE_OFFLINE_MODE=true
ENABLE_DARK_MODE=true
```

## Component System

### Component Types

1. **Static Components** (`/components/`)
   - Reusable UI elements
   - Example: `FileList.js`, `Modal.js`, `Navigation.js`

2. **Page Components** (`/pages/`)
   - Page-specific logic and templates
   - Example: `DashboardPage.js`, `LoginPage.js`

3. **Service Classes** (`/services/`)
   - API communication layers
   - Example: `ProjectService.js`, `AuthService.js`

### Creating a New Component

1. **Create the component file:**
   ```javascript
   // src/components/MyComponent.js
   class MyComponent {
     constructor(element) {
       this.element = element;
       this.init();
     }

     init() {
       // Component initialization
     }

     // Component methods
   }

   export default MyComponent;
   ```

2. **Register with Alpine.js (if needed):**
   ```javascript
   // In main.js or appropriate page file
   import MyComponent from './components/MyComponent.js';
   
   Alpine.data('myComponent', () => new MyComponent());
   ```

3. **Use in HTML:**
   ```html
   <div x-data="myComponent">
     <!-- Component markup -->
   </div>
   ```

## API Integration

### Service Layer

All API communication goes through service classes:

```javascript
// Example: Using ProjectService
import ProjectService from '../services/ProjectService.js';

const projectService = new ProjectService();

// Get all projects
const projects = await projectService.getAll();

// Create a project
const newProject = await projectService.create({
  name: 'New Project',
  description: 'Project description'
});
```

### Authentication

Authentication is handled automatically by the `AuthService`:

```javascript
import AuthService from '../services/AuthService.js';

const authService = new AuthService();

// Login
await authService.login(email, password);

// Check authentication status
const isAuthenticated = authService.isAuthenticated();

// Logout
await authService.logout();
```

### Error Handling

API errors are handled consistently across all services:

```javascript
try {
  const data = await projectService.getAll();
} catch (error) {
  if (error.status === 401) {
    // Redirect to login
    window.location.href = '/login';
  } else {
    // Show error message
    console.error('API Error:', error.message);
  }
}
```

## Styling Guidelines

### Tailwind CSS Usage

- Use utility classes for most styling
- Create component classes for reusable patterns
- Follow responsive-first design principles

```html
<!-- Good: Utility classes -->
<button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
  Click me
</button>

<!-- Better: Component class for reusable buttons -->
<button class="btn btn-primary">
  Click me
</button>
```

### Custom CSS

Add custom styles in `/src/styles/`:

```css
/* src/styles/components.css */
.btn {
  @apply px-4 py-2 rounded font-medium transition-colors;
}

.btn-primary {
  @apply bg-blue-500 hover:bg-blue-600 text-white;
}
```

### Dark Mode

Dark mode is supported through Tailwind's dark mode classes:

```html
<div class="bg-white dark:bg-gray-800 text-black dark:text-white">
  Content adapts to theme
</div>
```

## Testing

### Unit Tests

Test individual components and services:

```javascript
// tests/services/ProjectService.test.js
import ProjectService from '../../src/services/ProjectService.js';

describe('ProjectService', () => {
  let service;

  beforeEach(() => {
    service = new ProjectService();
  });

  test('should get all projects', async () => {
    const projects = await service.getAll();
    expect(Array.isArray(projects)).toBe(true);
  });
});
```

### Integration Tests

Test API communication:

```javascript
// tests/integration/api.test.js
describe('API Integration', () => {
  test('should authenticate user', async () => {
    const response = await fetch('/api/v1/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: 'test@example.com', password: 'password' })
    });
    
    expect(response.ok).toBe(true);
  });
});
```

### E2E Tests

Test complete user workflows:

```javascript
// tests/e2e/dashboard.spec.js
import { test, expect } from '@playwright/test';

test('user can access dashboard', async ({ page }) => {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'test@example.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  
  await expect(page).toHaveURL('/dashboard');
  await expect(page.locator('h1')).toContainText('Dashboard');
});
```

## Build and Deployment

### Development Build

```bash
npm run build:dev
```
- Source maps enabled
- Unminified code
- Development optimizations

### Production Build

```bash
npm run build
```
- Minified and optimized
- Tree-shaking applied
- Assets hashed for caching

### Docker Deployment

1. **Build Production Image:**
   ```bash
   docker build -t specsrv-frontend .
   ```

2. **Run Container:**
   ```bash
   docker run -p 3000:80 specsrv-frontend
   ```

3. **With Docker Compose:**
   ```bash
   docker-compose up specsrv-frontend
   ```

## Performance Optimization

### Bundle Size

Monitor bundle size with webpack-bundle-analyzer:

```bash
npm install --save-dev webpack-bundle-analyzer
npx webpack-bundle-analyzer dist/main.js
```

### Code Splitting

Large components can be lazy-loaded:

```javascript
// Dynamic import for large components
const loadKanbanBoard = () => import('./components/KanbanBoard.js');

// Use when needed
const { default: KanbanBoard } = await loadKanbanBoard();
```

### Asset Optimization

- Images are automatically optimized during build
- CSS is purged of unused classes
- JavaScript is minified and compressed

## Troubleshooting

### Common Issues

1. **CORS Errors**
   - Check backend CORS configuration
   - Verify API_BASE_URL in environment

2. **Authentication Failures**
   - Clear localStorage/sessionStorage
   - Check JWT token expiration

3. **Build Errors**
   - Clear node_modules and reinstall
   - Check for conflicting dependencies

4. **Hot Reload Not Working**
   - Check webpack dev server configuration
   - Verify file watching permissions

### Debug Mode

Enable debug logging:

```javascript
// In main.js
if (process.env.NODE_ENV === 'development') {
  window.DEBUG = true;
  console.log('Debug mode enabled');
}
```

## Contributing

### Code Style

- Use ESLint and Prettier for consistent formatting
- Follow JavaScript Standard Style
- Use meaningful variable and function names
- Add JSDoc comments for public APIs

### Pull Request Process

1. Create feature branch from `develop`
2. Make changes with appropriate tests
3. Run linting and tests: `npm run lint && npm test`
4. Submit pull request with clear description

### Testing Requirements

- Unit tests for new services and utilities
- Integration tests for API endpoints
- E2E tests for new user workflows
- All tests must pass before merge

## Resources

- [HTMX Documentation](https://htmx.org/docs/)
- [Alpine.js Guide](https://alpinejs.dev/start-here)
- [Tailwind CSS Docs](https://tailwindcss.com/docs)
- [Webpack Configuration](https://webpack.js.org/configuration/)
- [Jest Testing Framework](https://jestjs.io/docs/getting-started)
- [Playwright E2E Testing](https://playwright.dev/docs/intro)