# SpecSrv Frontend Components Documentation

This document provides detailed documentation for all frontend components, services, and utilities in the SpecSrv application.

## Table of Contents

1. [Component Architecture](#component-architecture)
2. [Core Components](#core-components)
3. [Services](#services)
4. [Utilities](#utilities)
5. [Page Components](#page-components)
6. [Development Guidelines](#development-guidelines)

## Component Architecture

The SpecSrv frontend uses a modular component architecture with:

- **Alpine.js** for reactive functionality
- **HTMX** for server-side interactions
- **Tailwind CSS** for styling
- **Vanilla JavaScript** for complex logic

### Component Types

1. **Static Components** - Generate HTML markup
2. **Class Components** - Object-oriented components with state
3. **Service Classes** - API communication and business logic
4. **Utility Functions** - Helper functions and tools
5. **Page Components** - Page-specific logic and templates

## Core Components

### Navigation Component

Located in: `src/components/Navigation.js`

**Purpose**: Provides the main application navigation bar with user menu, theme toggle, and responsive design.

#### Functions

##### `createNavigation()`
Generates the navigation HTML structure.

```javascript
import { createNavigation } from './components/Navigation.js';

const navHtml = createNavigation();
document.getElementById('nav-container').innerHTML = navHtml;
```

##### `initializeNavigation()`
Initializes navigation functionality after DOM insertion.

```javascript
import { initializeNavigation } from './components/Navigation.js';

// Call after navigation HTML is inserted
initializeNavigation();
```

#### Features

- **Responsive Design**: Desktop and mobile layouts
- **Theme Toggle**: Light/dark mode switching
- **User Menu**: Profile access and logout
- **Search Integration**: Built-in search autocomplete
- **Active State**: Highlights current page

#### Alpine.js Data

The navigation uses several Alpine.js data components:

```javascript
// Theme toggle
x-data="themeToggle"

// Dropdown menu
x-data="dropdown()"

// Mobile menu toggle
x-data="{ showMobileMenu: false }"
```

#### Styling Classes

- Primary navigation: `bg-white dark:bg-gray-800`
- Navigation items: `text-gray-700 dark:text-gray-300`
- Active items: `text-primary-600 dark:text-primary-400`
- Transitions: `transition-colors duration-200`

### FileList Component

Located in: `src/components/FileList.js`

**Purpose**: Displays and manages file attachments with preview, download, and delete functionality.

#### Constructor

```javascript
import { FileList } from './components/FileList.js';

const fileList = new FileList(containerElement, {
  canDelete: true,
  baseUrl: '/api/v1/files'
});
```

#### Options

- `canDelete` (boolean): Enable delete functionality
- `baseUrl` (string): API base URL for file operations

#### Methods

##### `render(files)`
Renders the file list with provided file array.

```javascript
const files = [
  {
    id: 1,
    filename: 'document.pdf',
    original_name: 'Project Document.pdf',
    mime_type: 'application/pdf',
    size: 1024000,
    created_at: '2023-01-01T00:00:00Z'
  }
];

fileList.render(files);
```

##### `renderFileItem(file)`
Renders a single file item with actions.

##### `togglePreview(filename)`
Toggles file preview for supported file types.

##### `deleteFile(fileId)`
Handles file deletion with confirmation using the file's unique ID.

#### Supported File Types

- **Images**: `image/*` - Shows preview thumbnail
- **PDF**: `application/pdf` - Shows PDF icon
- **Text**: `text/*` - Shows text icon with preview
- **Other**: Generic file icon

#### Events

The component emits custom events:

```javascript
// Listen for file deletion
container.addEventListener('file-deleted', (event) => {
  console.log('File deleted:', event.detail.fileId);
});

// Listen for file actions via Alpine.js
@file-delete="handleFileDelete($event.detail)"
```

**Note**: File operations use the file's unique `id` as the canonical identifier for REST endpoints (e.g., `DELETE /api/v1/files/{id}`) to prevent collisions and race conditions that could occur with filename-based operations.

#### Styling

File list uses responsive grid layout with hover effects:

```css
.file-item {
  @apply flex items-center justify-between p-4 bg-gray-50 rounded-lg border hover:bg-gray-100 transition-colors;
}
```

### Modal Component

Located in: `src/components/Modal.js`

**Purpose**: Provides reusable modal dialogs with Alpine.js integration.

#### Usage

```javascript
import { Modal } from './components/Modal.js';

const modal = new Modal({
  id: 'confirm-dialog',
  title: 'Confirm Action',
  content: 'Are you sure you want to proceed?',
  actions: [
    { text: 'Cancel', class: 'btn-secondary', action: 'close' },
    { text: 'Confirm', class: 'btn-primary', action: 'confirm' }
  ]
});

modal.show();
```

#### Configuration Options

- `id` (string): Unique modal identifier
- `title` (string): Modal title
- `content` (string): Modal body content
- `actions` (array): Action buttons configuration
- `size` (string): Modal size (`sm`, `md`, `lg`, `xl`)
- `closeable` (boolean): Show close button

#### Methods

- `show()` - Show the modal
- `hide()` - Hide the modal
- `destroy()` - Remove modal from DOM

### SearchAutocomplete Component

Located in: `src/components/SearchAutocomplete.js`

**Purpose**: Provides search functionality with real-time suggestions.

#### Initialization

```javascript
import { SearchAutocomplete } from './components/SearchAutocomplete.js';

const search = new SearchAutocomplete(inputElement, {
  apiEndpoint: '/api/v1/search/suggestions',
  minChars: 2,
  debounceMs: 300
});
```

#### Features

- **Real-time Search**: Debounced API calls
- **Keyboard Navigation**: Arrow keys and Enter support
- **Result Categories**: Projects and tasks
- **Click/Enter Selection**: Navigate to selected item

#### Configuration

- `apiEndpoint` - Search API endpoint
- `minChars` - Minimum characters to trigger search
- `debounceMs` - Debounce delay in milliseconds
- `maxResults` - Maximum results to display

## Services

### ApiService

Located in: `src/services/ApiService.js`

**Purpose**: Centralized HTTP client for API communication with interceptors and error handling.

#### Initialization

```javascript
import { ApiService } from './services/ApiService.js';

const apiService = new ApiService();
```

#### Configuration

The service auto-configures with:

- Base URL from environment or default
- JWT token from secure httpOnly cookies (recommended) or in-memory storage with refresh token rotation
- Request/response interceptors
- Timeout handling (30 seconds)

**Security Note**: Avoid storing JWTs in localStorage as it's vulnerable to XSS attacks. Use httpOnly, Secure, SameSite cookies set by the API, or implement a short-lived access token in-memory with refresh token strategy. See AuthService implementation for cookie-based logout/renew flow examples.

#### HTTP Methods

```javascript
// GET request
const projects = await apiService.get('/projects');

// POST request with data
const newProject = await apiService.post('/projects', {
  title: 'New Project',
  description: 'Project description'
});

// PUT request (full update)
const updated = await apiService.put('/projects/1', projectData);

// PATCH request (partial update)
const patched = await apiService.patch('/tasks/1/status', { status: 'completed' });

// DELETE request
await apiService.delete('/projects/1');
```

#### File Operations

```javascript
// Upload file
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('entity_type', 'project');
formData.append('entity_id', 1);

const result = await apiService.upload('/files', formData);

// Download file
await apiService.download('/files/1/download', 'document.pdf');
```

#### Error Handling

The service automatically handles common HTTP errors:

- **401 Unauthorized**: Clears token and emits logout event
- **403 Forbidden**: Emits forbidden event
- **429 Rate Limited**: Emits rate limit event
- **5xx Server Errors**: Emits server error event

#### Request Interceptors

```javascript
// Add custom request interceptor
apiService.addRequestInterceptor((config) => {
  config.headers['X-Custom-Header'] = 'value';
  return config;
});
```

#### Response Interceptors

```javascript
// Add custom response interceptor
apiService.addResponseInterceptor(async (response, config) => {
  // Log response times
  console.log('Response time:', response.headers.get('X-Response-Time'));
  return response;
});
```

### ProjectService

Located in: `src/services/ProjectService.js`

**Purpose**: High-level API wrapper for project operations.

#### Methods

```javascript
import { ProjectService } from './services/ProjectService.js';

const projectService = new ProjectService();

// List projects with pagination
const projects = await projectService.getAll({ page: 1, per_page: 20 });

// Get single project
const project = await projectService.getById(1);

// Create project
const newProject = await projectService.create({
  title: 'New Project',
  description: 'Description',
  github_repo: 'https://github.com/user/repo'
});

// Update project
const updated = await projectService.update(1, { title: 'Updated Title' });

// Delete project
await projectService.delete(1);

// Get project tasks
const tasks = await projectService.getTasks(1, { status: 'todo' });

// Get project files
const files = await projectService.getFiles(1);

// Get project commits
const commits = await projectService.getCommits(1);
```

### TaskService

Located in: `src/services/TaskService.js`

**Purpose**: High-level API wrapper for task operations.

#### Methods

```javascript
import { TaskService } from './services/TaskService.js';

const taskService = new TaskService();

// List tasks with filters
const tasks = await taskService.getAll({
  status: 'in_progress',
  search: 'bug fix',
  page: 1
});

// Create task
const newTask = await taskService.create({
  project_id: 1,
  title: 'New Task',
  description: 'Task description',
  status: 'todo'
});

// Update task status
await taskService.updateStatus(1, 'completed');

// Add git link to task
await taskService.addGitLink(1, {
  commit_hash: 'abc123def456',
  pr_reference: '123'
});

// Get task files
const files = await taskService.getFiles(1);
```

### AuthService

Located in: `src/services/AuthService.js`

**Purpose**: Authentication and user management.

#### Methods

```javascript
import { AuthService } from './services/AuthService.js';

const authService = new AuthService();

// Login user
const result = await authService.login('user@example.com', 'password');
console.log('User token:', result.token);

// Register user
const newUser = await authService.register({
  email: 'new@example.com',
  password: 'password',
  name: 'New User'
});

// Get current user
const user = await authService.getCurrentUser();

// Refresh token
const refreshed = await authService.refreshToken();

// Logout
await authService.logout();

// Check authentication status
const isAuthenticated = authService.isAuthenticated();
```

#### Events

The AuthService emits events for state changes:

```javascript
// Listen for authentication events
window.addEventListener('auth:login', (event) => {
  console.log('User logged in:', event.detail.user);
});

window.addEventListener('auth:logout', () => {
  console.log('User logged out');
  window.location.href = '/login';
});
```

### FileService

Located in: `src/services/FileService.js`

**Purpose**: File upload and management operations.

#### Methods

```javascript
import { FileService } from './services/FileService.js';

const fileService = new FileService();

// Upload file to entity
const uploadResult = await fileService.upload(file, 'project', 1);

// Get file info
const fileInfo = await fileService.getById(1);

// Download file
await fileService.download(1, 'document.pdf');

// Delete file
await fileService.delete(1);

// Get upload limits
const limits = await fileService.getLimits();
console.log('Max file size:', limits.max_file_size);

// List files for entity
const files = await fileService.getByEntity('project', 1);
```

## Utilities

### Router

Located in: `src/utils/Router.js`

**Purpose**: Client-side routing for single-page application functionality.

#### Usage

```javascript
import { Router } from './utils/Router.js';

const router = new Router();

// Define routes
router.addRoute('/', () => import('./pages/DashboardPage.js'));
router.addRoute('/projects', () => import('./pages/ProjectsPage.js'));
router.addRoute('/projects/:id', (params) => import('./pages/ProjectDetailPage.js'));

// Start routing
router.start();

// Navigate programmatically
router.navigate('/projects/1');
```

#### Route Parameters

```javascript
// Route with parameters
router.addRoute('/projects/:id/tasks/:taskId', (params) => {
  console.log('Project ID:', params.id);
  console.log('Task ID:', params.taskId);
  return import('./pages/TaskDetailPage.js');
});
```

### ThemeManager

Located in: `src/utils/ThemeManager.js`

**Purpose**: Dark/light theme management with persistence.

#### Usage

```javascript
import { ThemeManager } from './utils/ThemeManager.js';

const themeManager = new ThemeManager();

// Get current theme
const currentTheme = themeManager.getTheme(); // 'light' | 'dark'

// Set theme
themeManager.setTheme('dark');

// Toggle theme
themeManager.toggleTheme();

// Check if dark mode
const isDark = themeManager.isDark();
```

#### Alpine.js Integration

```javascript
// Theme toggle component
Alpine.data('themeToggle', () => ({
  toggle() {
    this.$store.theme.toggle();
  }
}));

// Alpine store
Alpine.store('theme', {
  current: themeManager.getTheme(),
  isDark: themeManager.isDark(),
  
  toggle() {
    themeManager.toggleTheme();
    this.current = themeManager.getTheme();
    this.isDark = themeManager.isDark();
  }
});
```

### FlashMessages

Located in: `src/utils/flashMessages.js`

**Purpose**: Display temporary success, error, and info messages.

#### Usage

```javascript
import { flashMessages } from './utils/flashMessages.js';

// Show success message
flashMessages.success('Project created successfully!');

// Show error message
flashMessages.error('Failed to save project.');

// Show info message
flashMessages.info('Please complete your profile.');

// Show warning message
flashMessages.warning('This action cannot be undone.');

// Show message with options
flashMessages.show('Custom message', {
  type: 'success',
  duration: 5000,
  dismissible: true
});
```

#### Configuration

```javascript
// Configure default options
flashMessages.configure({
  duration: 4000,
  position: 'top-right',
  dismissible: true,
  animations: true
});
```

### NotificationManager

Located in: `src/utils/NotificationManager.js`

**Purpose**: Browser notification handling with permission management.

#### Usage

```javascript
import { NotificationManager } from './utils/NotificationManager.js';

const notifications = new NotificationManager();

// Request permission
const hasPermission = await notifications.requestPermission();

// Show notification
if (hasPermission) {
  notifications.show('Task completed!', {
    body: 'Your task "Fix bug" has been marked as completed.',
    icon: '/favicon.ico',
    tag: 'task-1',
    actions: [
      { action: 'view', title: 'View Task' },
      { action: 'close', title: 'Close' }
    ]
  });
}
```

## Page Components

### BasePage

Located in: `src/pages/BasePage.js`

**Purpose**: Base class for all page components with common functionality.

#### Extending BasePage

```javascript
import { BasePage } from './BasePage.js';

export class DashboardPage extends BasePage {
  constructor() {
    super();
    this.pageTitle = 'Dashboard';
    this.requiresAuth = true;
  }

  async init() {
    await super.init();
    
    // Page-specific initialization
    this.loadDashboardData();
  }

  async loadDashboardData() {
    try {
      const data = await this.apiService.get('/dashboard/stats');
      this.renderDashboard(data);
    } catch (error) {
      this.showError('Failed to load dashboard data');
    }
  }

  renderDashboard(data) {
    // Render dashboard content
  }
}
```

#### BasePage Methods

- `init()` - Initialize page
- `destroy()` - Clean up page
- `setTitle(title)` - Set page title
- `showLoading()` - Show loading state
- `hideLoading()` - Hide loading state
- `showError(message)` - Show error message

### LoginPage

Located in: `src/pages/LoginPage.js`

**Purpose**: User authentication page with form validation.

#### Features

- Email/password validation
- Remember me functionality
- Password reset link
- Registration redirect
- Social login buttons (if configured)

### DashboardPage

Located in: `src/pages/DashboardPage.js`

**Purpose**: Main dashboard with statistics and recent items.

#### Features

- User statistics summary
- Recent projects list
- Recent tasks list
- Quick actions
- Activity feed

## Development Guidelines

### Creating New Components

1. **Choose Component Type**:
   - Static function for simple HTML generation
   - Class component for complex state management
   - Service class for API operations

2. **Follow Naming Conventions**:
   - Components: PascalCase (`FileList`, `Modal`)
   - Files: kebab-case (`file-list.js`, `modal.js`)
   - CSS classes: BEM or utility-first

3. **Component Structure**:
```javascript
/**
 * Component description
 * @param {Object} options - Configuration options
 */
export class MyComponent {
  constructor(element, options = {}) {
    this.element = element;
    this.options = { ...defaultOptions, ...options };
    this.init();
  }

  init() {
    // Initialization logic
  }

  render() {
    // Rendering logic
  }

  destroy() {
    // Cleanup logic
  }
}
```

### Alpine.js Integration

Components should integrate cleanly with Alpine.js:

```javascript
// Register Alpine component
Alpine.data('myComponent', (options) => ({
  // Alpine data properties
  isVisible: false,
  
  // Alpine methods
  toggle() {
    this.isVisible = !this.isVisible;
  },
  
  // Lifecycle hooks
  init() {
    // Component initialization
  }
}));
```

### API Integration (fetch)

Use the ApiService for server interactions with JSON responses:

```javascript
// POST to create a new project
async function createProject(projectData) {
  try {
    const response = await apiService.post('/projects', projectData);
    if (response.ok && response.success) {
      // Update DOM with new project
      renderProject(response.data);
      document.getElementById('project-list').appendChild(renderProject(response.data));
    } else {
      throw new Error(response.message || 'Failed to create project');
    }
  } catch (error) {
    console.error('Project creation failed:', error);
    showError(error.message);
  }
}

// Helper function to render project HTML
function renderProject(project) {
  const projectElement = document.createElement('div');
  projectElement.className = 'project-item';
  projectElement.innerHTML = `
    <h3>${project.title}</h3>
    <p>${project.description}</p>
  `;
  return projectElement;
}
```

### Error Handling

Implement consistent error handling:

```javascript
try {
  const result = await this.apiService.post('/endpoint', data);
  this.showSuccess('Operation completed successfully');
} catch (error) {
  this.showError(error.message || 'Operation failed');
  console.error('Operation error:', error);
}
```

### Testing Components

Write tests for component functionality:

```javascript
// Component test example
describe('FileList Component', () => {
  let container;
  let fileList;

  beforeEach(() => {
    container = document.createElement('div');
    fileList = new FileList(container);
  });

  test('should render empty state when no files', () => {
    fileList.render([]);
    expect(container.innerHTML).toContain('No files attached');
  });

  test('should render file items', () => {
    const files = [{ filename: 'test.pdf', size: 1000 }];
    fileList.render(files);
    expect(container.innerHTML).toContain('test.pdf');
  });
});
```

### Performance Considerations

1. **Lazy Loading**: Load components only when needed
2. **Event Delegation**: Use event delegation for dynamic content
3. **Memory Management**: Clean up event listeners in destroy methods
4. **Bundle Size**: Import only what you need

### Accessibility

Ensure components are accessible:

```html
<!-- Proper ARIA labels -->
<button aria-label="Close modal" aria-expanded="false">
  <svg aria-hidden="true">...</svg>
</button>

<!-- Keyboard navigation support -->
<div role="listbox" tabindex="0" @keydown="handleKeydown">
  <div role="option" tabindex="-1">Option 1</div>
</div>
```

This documentation provides comprehensive guidance for understanding and working with the SpecSrv frontend component system. Each component follows consistent patterns and integrates seamlessly with the overall architecture.