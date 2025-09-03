# SpecSrv Frontend Testing Documentation

This document provides comprehensive guidelines for testing the SpecSrv frontend application, including unit tests, integration tests, and end-to-end tests.

## Testing Framework Overview

The SpecSrv frontend uses a multi-layered testing approach:

- **Jest** - Unit and integration testing
- **Playwright** - End-to-end testing
- **JSDOM** - DOM simulation for unit tests
- **Custom Test Utilities** - Helper functions for common test scenarios

## Table of Contents

1. [Test Structure](#test-structure)
2. [Running Tests](#running-tests)
3. [Unit Testing](#unit-testing)
4. [Integration Testing](#integration-testing)
5. [End-to-End Testing](#end-to-end-testing)
6. [Test Utilities](#test-utilities)
7. [Mocking and Fixtures](#mocking-and-fixtures)
8. [Best Practices](#best-practices)
9. [Continuous Integration](#continuous-integration)

## Test Structure

```
frontend/tests/
├── unit/                    # Unit tests
│   ├── components/         # Component unit tests
│   ├── services/          # Service unit tests
│   └── utils/             # Utility unit tests
├── integration/            # Integration tests
│   ├── api/               # API integration tests
│   └── components/        # Component integration tests
├── e2e/                   # End-to-end tests
│   ├── auth.spec.js       # Authentication flows
│   ├── projects.spec.js   # Project management
│   ├── tasks.spec.js      # Task management
│   ├── files.spec.js      # File operations
│   ├── kanban.spec.js     # Kanban board
│   └── performance.spec.js # Performance tests
├── fixtures/              # Test data and fixtures
├── mocks/                 # Mock implementations
├── support/               # Test support utilities
└── setup.js               # Jest configuration
```

## Running Tests

### Available Test Scripts

```bash
# Run all tests
npm test

# Run tests in watch mode
npm run test:watch

# Run tests with coverage
npm run test:coverage

# Run only unit tests
npm run test:unit

# Run only integration tests
npm run test:integration

# Run end-to-end tests
npm run test:e2e

# Run specific test file
npm test -- --testPathPattern=services/ApiService

# Run tests matching pattern
npm test -- --testNamePattern="should handle authentication"
```

### Environment Setup

Create a `.env.test` file for test configuration:

```env
# API Configuration
API_BASE_URL=http://localhost:8080/api/v1
NODE_ENV=test

# Test User Credentials
TEST_USER_EMAIL=test@example.com
TEST_USER_PASSWORD=password123
TEST_JWT_TOKEN=eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...

# Database Configuration (for backend tests)
DATABASE_URL=postgresql://test_user:test_pass@localhost:5433/specsrv_test

# Feature Flags for Testing
ENABLE_TEST_MODE=true
SKIP_AUTH_IN_TESTS=false
```

## Unit Testing

Unit tests focus on testing individual components and functions in isolation.

### Component Testing

#### Testing Static Components

```javascript
// tests/unit/components/Navigation.test.js
import { createNavigation, initializeNavigation } from '../../../src/components/Navigation.js';

describe('Navigation Component', () => {
  let container;

  beforeEach(() => {
    container = document.createElement('div');
    document.body.appendChild(container);
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  test('should create navigation HTML', () => {
    const navHtml = createNavigation();
    
    expect(navHtml).toContain('nav');
    expect(navHtml).toContain('SpecSrv');
    expect(navHtml).toContain('Dashboard');
    expect(navHtml).toContain('Projects');
  });

  test('should highlight active navigation item', () => {
    // Mock current path
    Object.defineProperty(window, 'location', {
      value: { pathname: '/dashboard' },
      writable: true
    });

    container.innerHTML = createNavigation();
    initializeNavigation();

    const dashboardLink = container.querySelector('[data-nav-item="dashboard"]');
    expect(dashboardLink).toHaveClass('text-primary-600');
  });
});
```

#### Testing Class Components

```javascript
// tests/unit/components/FileList.test.js
import { FileList } from '../../../src/components/FileList.js';

describe('FileList Component', () => {
  let container;
  let fileList;

  beforeEach(() => {
    container = document.createElement('div');
    fileList = new FileList(container, {
      canDelete: true,
      baseUrl: '/api/v1/files'
    });
  });

  test('should initialize with correct options', () => {
    expect(fileList.container).toBe(container);
    expect(fileList.options.canDelete).toBe(true);
    expect(fileList.options.baseUrl).toBe('/api/v1/files');
  });

  test('should render empty state when no files', () => {
    fileList.render([]);
    
    expect(container.innerHTML).toContain('No files attached');
  });

  test('should render file items', () => {
    const files = [
      {
        id: 1,
        filename: 'test.pdf',
        mime_type: 'application/pdf',
        size: 1024000,
        created_at: '2023-01-01T00:00:00Z'
      }
    ];

    fileList.render(files);

    expect(container.innerHTML).toContain('test.pdf');
    expect(container.innerHTML).toContain('1000.0 KB');
  });

  test('should show delete button when canDelete is true', () => {
    const files = [{ id: 1, filename: 'test.pdf', mime_type: 'application/pdf' }];
    
    fileList.render(files);

    const deleteButton = container.querySelector('button[title="Delete file"]');
    expect(deleteButton).toBeInTheDocument();
  });

  test('should format file sizes correctly', () => {
    expect(fileList.formatFileSize(1024)).toBe('1.0 KB');
    expect(fileList.formatFileSize(1048576)).toBe('1024.0 KB');
    expect(fileList.formatFileSize(0)).toBe('Unknown size');
  });
});
```

### Service Testing

```javascript
// tests/unit/services/ProjectService.test.js
import { ProjectService } from '../../../src/services/ProjectService.js';
import { ApiService } from '../../../src/services/ApiService.js';

// Mock ApiService
jest.mock('../../../src/services/ApiService.js');

describe('ProjectService', () => {
  let projectService;
  let mockApiService;

  beforeEach(() => {
    mockApiService = {
      get: jest.fn(),
      post: jest.fn(),
      put: jest.fn(),
      delete: jest.fn()
    };
    
    ApiService.mockImplementation(() => mockApiService);
    projectService = new ProjectService();
  });

  test('should get all projects', async () => {
    const mockProjects = [
      { id: 1, title: 'Project 1' },
      { id: 2, title: 'Project 2' }
    ];

    mockApiService.get.mockResolvedValue({
      success: true,
      data: mockProjects
    });

    const result = await projectService.getAll();

    expect(mockApiService.get).toHaveBeenCalledWith('/projects');
    expect(result).toEqual(mockProjects);
  });

  test('should create new project', async () => {
    const projectData = {
      title: 'New Project',
      description: 'Project description'
    };

    const mockResponse = {
      success: true,
      data: { id: 1, ...projectData }
    };

    mockApiService.post.mockResolvedValue(mockResponse);

    const result = await projectService.create(projectData);

    expect(mockApiService.post).toHaveBeenCalledWith('/projects', projectData);
    expect(result).toEqual(mockResponse.data);
  });

  test('should handle API errors', async () => {
    const error = new Error('API Error');
    mockApiService.get.mockRejectedValue(error);

    await expect(projectService.getAll()).rejects.toThrow('API Error');
  });
});
```

### Utility Testing

```javascript
// tests/unit/utils/ThemeManager.test.js
import { ThemeManager } from '../../../src/utils/ThemeManager.js';

describe('ThemeManager', () => {
  let themeManager;

  beforeEach(() => {
    themeManager = new ThemeManager();
    localStorage.clear();
  });

  test('should initialize with default theme', () => {
    expect(themeManager.getTheme()).toBe('light');
    expect(themeManager.isDark()).toBe(false);
  });

  test('should set theme and persist to localStorage', () => {
    themeManager.setTheme('dark');

    expect(themeManager.getTheme()).toBe('dark');
    expect(localStorage.setItem).toHaveBeenCalledWith('specsrv-theme', 'dark');
  });

  test('should toggle between themes', () => {
    expect(themeManager.getTheme()).toBe('light');

    themeManager.toggleTheme();
    expect(themeManager.getTheme()).toBe('dark');

    themeManager.toggleTheme();
    expect(themeManager.getTheme()).toBe('light');
  });

  test('should load theme from localStorage', () => {
    localStorage.getItem.mockReturnValue('dark');
    
    const newManager = new ThemeManager();
    expect(newManager.getTheme()).toBe('dark');
  });
});
```

## Integration Testing

Integration tests verify that different parts of the application work together correctly.

### API Integration Tests

```javascript
// tests/integration/api/auth.test.js
import { AuthService } from '../../../src/services/AuthService.js';

describe('Authentication Integration', () => {
  let authService;

  beforeEach(() => {
    authService = new AuthService();
  });

  test('should complete login flow', async () => {
    // Mock successful login response
    fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        success: true,
        data: {
          token: 'mock-jwt-token',
          user: { id: 1, email: 'test@example.com' }
        }
      })
    });

    const result = await authService.login('test@example.com', 'password');

    expect(result.token).toBe('mock-jwt-token');
    expect(localStorage.setItem).toHaveBeenCalledWith('specsrv-token', 'mock-jwt-token');
    expect(window.dispatchEvent).toHaveBeenCalledWith(
      expect.objectContaining({ type: 'auth:login' })
    );
  });

  test('should handle authentication failure', async () => {
    fetch.mockResolvedValueOnce({
      ok: false,
      status: 401,
      clone: () => ({
        json: async () => ({ message: 'Invalid credentials' })
      })
    });

    await expect(
      authService.login('test@example.com', 'wrongpassword')
    ).rejects.toThrow('Invalid credentials');

    expect(localStorage.getItem('specsrv-token')).toBeNull();
  });
});
```

### Component Integration Tests

```javascript
// tests/integration/components/ProjectForm.test.js
import { ProjectService } from '../../../src/services/ProjectService.js';

describe('Project Form Integration', () => {
  let container;
  let projectService;

  beforeEach(() => {
    container = document.createElement('div');
    projectService = new ProjectService();
    
    container.innerHTML = `
      <form id="project-form">
        <input name="title" required>
        <textarea name="description"></textarea>
        <button type="submit">Create Project</button>
      </form>
    `;
    
    document.body.appendChild(container);
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  test('should submit project form and create project', async () => {
    const form = container.querySelector('#project-form');
    const titleInput = container.querySelector('input[name="title"]');
    const descriptionInput = container.querySelector('textarea[name="description"]');

    // Mock successful API response
    jest.spyOn(projectService, 'create').mockResolvedValue({
      id: 1,
      title: 'Test Project',
      description: 'Test Description'
    });

    // Fill form
    titleInput.value = 'Test Project';
    descriptionInput.value = 'Test Description';

    // Submit form
    form.dispatchEvent(new Event('submit', { bubbles: true }));

    await global.testUtils.waitFor(() => {
      expect(projectService.create).toHaveBeenCalledWith({
        title: 'Test Project',
        description: 'Test Description'
      });
    });
  });
});
```

## End-to-End Testing

End-to-end tests use Playwright to test complete user workflows in a real browser environment.

### Authentication E2E Tests

```javascript
// tests/e2e/auth.spec.js (already shown above)
// This tests the complete authentication flow from login to dashboard
```

### Project Management E2E Tests

```javascript
// tests/e2e/projects.spec.js
import { test, expect } from '@playwright/test';

test.describe('Project Management', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto('/login');
    await page.fill('input[name="email"]', process.env.TEST_USER_EMAIL);
    await page.fill('input[name="password"]', process.env.TEST_USER_PASSWORD);
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/\/dashboard/);
  });

  test('should create a new project', async ({ page }) => {
    await page.goto('/projects');
    
    await page.click('button:text("New Project"), a:text("New Project")');
    await page.fill('input[name="title"]', 'E2E Test Project');
    await page.fill('textarea[name="description"]', 'Created by E2E test');
    await page.click('button[type="submit"]');

    await expect(page.locator('.success-message')).toBeVisible();
    await expect(page.locator('text=E2E Test Project')).toBeVisible();
  });

  test('should edit existing project', async ({ page }) => {
    await page.goto('/projects');
    
    // Click on first project
    await page.click('.project-card:first-child, .project-item:first-child');
    
    await page.click('button:text("Edit"), .edit-button');
    await page.fill('input[name="title"]', 'Updated Project Title');
    await page.click('button[type="submit"]');

    await expect(page.locator('text=Updated Project Title')).toBeVisible();
  });

  test('should delete project with confirmation', async ({ page }) => {
    await page.goto('/projects');
    
    // Get initial project count
    const initialCount = await page.locator('.project-card, .project-item').count();
    
    // Delete first project
    await page.click('.project-card:first-child .delete-button, .project-item:first-child .delete-button');
    
    // Handle confirmation dialog
    page.on('dialog', async dialog => {
      expect(dialog.message()).toContain('delete');
      await dialog.accept();
    });

    await page.click('button:text("Delete")');

    // Verify project is removed
    await expect(page.locator('.project-card, .project-item')).toHaveCount(initialCount - 1);
  });
});
```

### Performance E2E Tests

```javascript
// tests/e2e/performance.spec.js
import { test, expect } from '@playwright/test';

test.describe('Performance Tests', () => {
  test('should load dashboard within performance budget', async ({ page }) => {
    const startTime = Date.now();
    
    await page.goto('/dashboard');
    await page.waitForSelector('h1, .page-title');
    
    const loadTime = Date.now() - startTime;
    expect(loadTime).toBeLessThan(3000); // 3 second budget
  });

  test('should handle large project lists efficiently', async ({ page }) => {
    await page.goto('/projects');
    
    // Wait for projects to load
    await page.waitForSelector('.project-card, .project-item');
    
    // Measure scroll performance
    const startTime = Date.now();
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    const scrollTime = Date.now() - startTime;
    
    expect(scrollTime).toBeLessThan(1000); // Should scroll smoothly
  });

  test('should load API responses within budget', async ({ page }) => {
    await page.route('**/api/v1/**', async route => {
      const startTime = Date.now();
      const response = await route.fetch();
      const responseTime = Date.now() - startTime;
      
      expect(responseTime).toBeLessThan(500); // 500ms budget for API calls
      await route.fulfill({ response });
    });

    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');
  });
});
```

## Test Utilities

### Custom Test Utilities

```javascript
// tests/support/testUtils.js
export const testUtils = {
  /**
   * Create DOM element with properties
   */
  createElement(tagName, properties = {}) {
    const element = document.createElement(tagName);
    
    Object.entries(properties).forEach(([key, value]) => {
      if (key === 'innerHTML') {
        element.innerHTML = value;
      } else if (key === 'className') {
        element.className = value;
      } else {
        element[key] = value;
      }
    });
    
    return element;
  },

  /**
   * Wait for condition to be true
   */
  async waitFor(condition, timeout = 5000) {
    const start = Date.now();
    
    while (Date.now() - start < timeout) {
      try {
        if (await condition()) {
          return;
        }
      } catch (error) {
        // Continue waiting
      }
      
      await new Promise(resolve => setTimeout(resolve, 50));
    }
    
    throw new Error('Timeout waiting for condition');
  },

  /**
   * Wait for element to be present
   */
  async waitForElement(selector, container = document) {
    return this.waitFor(() => container.querySelector(selector));
  },

  /**
   * Simulate user events
   */
  simulate: {
    click(element) {
      element.dispatchEvent(new MouseEvent('click', { bubbles: true }));
    },
    
    change(input, value) {
      input.value = value;
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
    },
    
    submit(form) {
      form.dispatchEvent(new Event('submit', { bubbles: true }));
    }
  },

  /**
   * Eventually assertion helper
   */
  async eventually(assertion, timeout = 5000) {
    await this.waitFor(assertion, timeout);
  }
};

// Make available globally
global.testUtils = testUtils;
```

### Fixtures and Mock Data

```javascript
// tests/fixtures/projectData.js
export const mockProjects = [
  {
    id: 1,
    title: 'Test Project 1',
    description: 'First test project',
    github_repo: 'https://github.com/user/project1',
    created_at: '2023-01-01T00:00:00Z',
    updated_at: '2023-01-01T00:00:00Z'
  },
  {
    id: 2,
    title: 'Test Project 2',
    description: 'Second test project',
    github_repo: 'https://github.com/user/project2',
    created_at: '2023-01-02T00:00:00Z',
    updated_at: '2023-01-02T00:00:00Z'
  }
];

export const mockTasks = [
  {
    id: 1,
    title: 'Test Task 1',
    description: 'First test task',
    status: 'todo',
    priority: 'medium',
    project_id: 1,
    created_at: '2023-01-01T00:00:00Z',
    updated_at: '2023-01-01T00:00:00Z'
  }
];
```

## Mocking and Fixtures

### Global Mocks

```javascript
// tests/setup.js
import { testUtils } from './support/testUtils.js';

// Mock localStorage
const localStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn(),
};
global.localStorage = localStorageMock;

// Mock fetch
global.fetch = jest.fn();

// Mock window.location
delete window.location;
window.location = {
  href: '',
  pathname: '/',
  search: '',
  hash: ''
};

// Mock HTMX
global.htmx = {
  ajax: jest.fn(),
  trigger: jest.fn(),
  process: jest.fn(),
  remove: jest.fn()
};

// Mock Alpine.js
global.Alpine = {
  start: jest.fn(),
  data: jest.fn(),
  store: jest.fn(),
  version: '3.14.9'
};

// Mock GSAP
global.gsap = {
  to: jest.fn(),
  from: jest.fn(),
  set: jest.fn(),
  timeline: jest.fn(() => ({
    to: jest.fn(),
    from: jest.fn(),
    set: jest.fn()
  }))
};

// Setup DOM cleanup
afterEach(() => {
  document.body.innerHTML = '';
  document.head.innerHTML = '';
  jest.clearAllMocks();
});
```

### API Mocks

```javascript
// tests/mocks/apiMocks.js
import { mockProjects } from '../fixtures/mockProjects';
import { mockTasks } from '../fixtures/mockTasks';

export const createApiMocks = () => {
  const mockResponses = {
    '/auth/login': {
      success: true,
      data: {
        token: 'mock-jwt-token',
        user: { id: 1, email: 'test@example.com' }
      }
    },
    '/projects': {
      success: true,
      data: mockProjects
    },
    '/tasks': {
      success: true,
      data: mockTasks
    }
  };

  fetch.mockImplementation((url) => {
    const endpoint = url.replace(/^.*\/api\/v1/, '');
    const response = mockResponses[endpoint];
    
    if (response) {
      return Promise.resolve({
        ok: true,
        status: 200,
        json: async () => response,
        headers: new Map([['content-type', 'application/json']])
      });
    }
    
    return Promise.resolve({
      ok: false,
      status: 404,
      json: async () => ({ message: 'Not found' })
    });
  });
};
```

## Best Practices

### Test Organization

1. **Group Related Tests**: Use `describe` blocks to group related tests
2. **Descriptive Test Names**: Use clear, descriptive test names that explain what is being tested
3. **Arrange-Act-Assert**: Structure tests with clear setup, action, and verification phases
4. **One Assertion Per Test**: Keep tests focused with minimal assertions

### Test Data Management

1. **Use Fixtures**: Create reusable test data in fixture files
2. **Reset State**: Clean up state between tests
3. **Avoid Hard-coded Values**: Use variables or constants for test data
4. **Mock External Dependencies**: Mock API calls, external services, and browser APIs

### Performance Considerations

1. **Parallel Execution**: Run tests in parallel when possible
2. **Selective Testing**: Use test patterns to run specific test suites
3. **Mock Heavy Operations**: Mock file operations, network calls, and complex computations
4. **Clean Up Resources**: Properly dispose of resources after tests

### Debugging Tests

```bash
# Run tests in debug mode
npm test -- --detectOpenHandles --forceExit

# Run specific test with debugging
npm test -- --testNamePattern="should handle authentication" --verbose

# Generate coverage report
npm run test:coverage

# Run tests with watch mode for development
npm run test:watch
```

## Continuous Integration

### GitHub Actions Configuration

```yaml
# .github/workflows/frontend-tests.yml
name: Frontend Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  unit-tests:
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
    
    - name: Run unit tests
      working-directory: frontend
      run: npm run test:unit -- --coverage
    
    - name: Upload coverage reports
      uses: codecov/codecov-action@v3
      with:
        directory: frontend/coverage

  e2e-tests:
    runs-on: ubuntu-latest
    
    services:
      postgres:
        image: postgres:14
        env:
          POSTGRES_PASSWORD: specsrv1234
        ports:
          - 5433:5432
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup Node.js
      uses: actions/setup-node@v3
      with:
        node-version: '18'
        cache: 'npm'
    
    - name: Install dependencies
      run: |
        cd backend && composer install
        cd ../frontend && npm ci
    
    - name: Setup backend
      run: |
        cd backend
        php bin/console doctrine:database:create --env=test
        php bin/console doctrine:migrations:migrate --no-interaction --env=test
    
    - name: Start services
      run: |
        docker-compose -f docker-compose.dev.yml up -d
        sleep 10  # Wait for services to be ready
    
    - name: Install Playwright
      working-directory: frontend
      run: npx playwright install chromium
    
    - name: Run E2E tests
      working-directory: frontend
      run: npm run test:e2e
      env:
        TEST_USER_EMAIL: test@example.com
        TEST_USER_PASSWORD: password123
    
    - name: Upload E2E results
      uses: actions/upload-artifact@v3
      if: failure()
      with:
        name: playwright-report
        path: frontend/test-results/
```

### Test Coverage Requirements

Set up coverage thresholds in Jest configuration:

```javascript
// jest.config.js
module.exports = {
  coverageThreshold: {
    global: {
      branches: 80,
      functions: 80,
      lines: 80,
      statements: 80
    },
    './src/services/': {
      branches: 90,
      functions: 90,
      lines: 90,
      statements: 90
    }
  }
};
```

This comprehensive testing documentation ensures that the SpecSrv frontend is thoroughly tested at all levels, from individual unit tests to complete end-to-end user workflows. The testing framework is designed to catch bugs early, maintain code quality, and provide confidence in deployments.