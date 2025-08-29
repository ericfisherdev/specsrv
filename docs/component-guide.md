# Component Usage Guide

## Overview

This guide provides detailed examples and usage patterns for all frontend components in SpecSrv. Each component is designed to be reusable, accessible, and performant.

## Alpine.js Components

### Kanban Board Component

#### Basic Usage
```html
<div x-data="kanbanBoard()" class="kanban-board">
  <!-- Kanban columns -->
</div>
```

#### Configuration Options
```javascript
// Custom initialization
<div x-data="kanbanBoard({ 
  autoSave: true,
  animation: 'smooth',
  columns: ['backlog', 'todo', 'in_progress', 'review', 'completed']
})">
```

#### Methods
- `handleDragStart(task, event)` - Initialize drag operation
- `handleDrop(status, event)` - Handle task drop
- `moveTask(taskId, fromStatus, toStatus)` - Programmatically move task
- `refreshTasks()` - Reload tasks from server

#### Events
- `task-moved` - Fired when task changes status
- `task-updated` - Fired when task data changes
- `board-refreshed` - Fired when board data reloads

#### Example Implementation
```html
<div class="kanban-board" 
     x-data="kanbanBoard()"
     @task-moved="handleTaskMoved($event)"
     @task-updated="handleTaskUpdated($event)">
  
  <!-- Backlog Column -->
  <div class="kanban-column" 
       @drop.prevent="handleDrop('backlog', $event)"
       @dragover.prevent>
    <template x-for="task in backlogTasks" :key="task.id">
      <div class="kanban-card" 
           :data-task-id="task.id"
           draggable="true"
           @dragstart="handleDragStart(task, $event)">
        <h3 x-text="task.title"></h3>
        <p x-text="task.description"></p>
      </div>
    </template>
  </div>
</div>

<script>
function handleTaskMoved(event) {
  console.log('Task moved:', event.detail);
}
</script>
```

### Task Modal Component

#### Basic Usage
```html
<div x-data="taskModal()" 
     x-show="isOpen" 
     @open-task-modal.window="openModal($event.detail)">
  <!-- Modal content -->
</div>
```

#### Opening the Modal
```javascript
// Dispatch event to open modal
$dispatch('open-task-modal', { 
  status: 'todo',
  projectId: 123 
});

// Or call method directly
Alpine.store('taskModal').open({ status: 'todo' });
```

#### Form Validation
```html
<form x-data="{ errors: {} }" @submit.prevent="validateAndSubmit()">
  <input type="text" 
         x-model="form.title"
         :class="{ 'border-red-500': errors.title }"
         @blur="validateField('title')">
  <span x-show="errors.title" 
        x-text="errors.title" 
        class="text-red-500 text-sm"></span>
</form>
```

### Search Component

#### Basic Implementation
```html
{% include 'components/_search_autocomplete.html.twig' with {
  'search_placeholder': 'Search tasks...',
  'search_endpoint': path('app_search_autocomplete')
} %}
```

#### Custom Configuration
```html
<div x-data="searchAutocomplete({
  minLength: 2,
  debounceMs: 300,
  maxResults: 10,
  showRecentSearches: true
})" class="search-container">
  <!-- Search input and results -->
</div>
```

#### Handling Results
```javascript
// Custom result handler
function customSearchHandler() {
  return {
    ...searchAutocomplete(),
    
    selectSuggestion(item, type) {
      // Custom selection logic
      if (type === 'task') {
        this.handleTaskSelection(item);
      } else {
        this.handleProjectSelection(item);
      }
    },
    
    handleTaskSelection(task) {
      // Navigate to task or open modal
      window.location.href = `/tasks/${task.id}`;
    }
  };
}
```

### File Upload Component

#### Basic Usage
```html
{% include 'components/_file_upload.html.twig' with {
  'upload_endpoint': path('app_file_upload'),
  'max_files': 5,
  'allowed_types': ['pdf', 'doc', 'jpg', 'png']
} %}
```

#### Advanced Configuration
```html
<div x-data="fileUpload({
  maxSize: 10 * 1024 * 1024, // 10MB
  allowedTypes: ['image/*', '.pdf', '.doc'],
  uploadUrl: '/api/files/upload',
  onSuccess: (response) => console.log('Upload success:', response),
  onError: (error) => console.error('Upload error:', error)
})" class="file-upload-area">
  
  <!-- Drag and drop area -->
  <div class="drop-zone"
       @drop.prevent="handleDrop($event)"
       @dragover.prevent="isDragging = true"
       @dragleave.prevent="isDragging = false">
    
    <!-- File input -->
    <input type="file" 
           multiple 
           @change="handleFileSelect($event)"
           class="hidden">
    
    <!-- Upload progress -->
    <template x-for="file in files" :key="file.name">
      <div class="file-item">
        <span x-text="file.name"></span>
        <div class="progress-bar">
          <div class="progress-fill" 
               :style="`width: ${file.progress}%`"></div>
        </div>
      </div>
    </template>
  </div>
</div>
```

## Twig Components

### Search Autocomplete Component

#### Parameters
- `search_placeholder` (string) - Placeholder text
- `search_endpoint` (string) - API endpoint for search
- `min_length` (int) - Minimum query length
- `max_results` (int) - Maximum results to show

#### Usage Example
```twig
{% include 'components/_search_autocomplete.html.twig' with {
  'search_placeholder': 'Search projects and tasks...',
  'search_endpoint': path('app_search_api'),
  'min_length': 3,
  'max_results': 8
} %}
```

### File Upload Component

#### Parameters
- `upload_endpoint` (string) - Upload API endpoint
- `entity_type` (string) - Entity type (task, project)
- `entity_id` (int) - Entity ID
- `max_files` (int) - Maximum number of files
- `allowed_types` (array) - Allowed file types

#### Usage Example
```twig
{% include 'components/_file_upload.html.twig' with {
  'upload_endpoint': path('app_file_upload'),
  'entity_type': 'task',
  'entity_id': task.id,
  'max_files': 5,
  'allowed_types': ['pdf', 'doc', 'docx', 'jpg', 'png']
} %}
```

## HTMX Patterns

### Form Submissions

#### Basic Form
```html
<form hx-post="{{ path('app_task_create') }}"
      hx-target="#task-container"
      hx-swap="beforeend"
      hx-indicator="#loading-spinner">
  
  <input type="text" name="title" required>
  <textarea name="description"></textarea>
  
  <button type="submit">
    Create Task
    <div id="loading-spinner" class="htmx-indicator">
      Loading...
    </div>
  </button>
</form>
```

#### File Upload Form
```html
<form hx-post="{{ path('app_file_upload') }}"
      hx-encoding="multipart/form-data"
      hx-target="#upload-results">
  
  <input type="file" name="files[]" multiple>
  <input type="hidden" name="task_id" value="{{ task.id }}">
  
  <button type="submit">Upload Files</button>
</form>
```

### Dynamic Content Loading

#### Lazy Loading
```html
<div hx-get="{{ path('app_task_detail', {id: task.id}) }}"
     hx-trigger="intersect once"
     hx-target="this"
     hx-swap="outerHTML">
  <div class="loading-placeholder">Loading task...</div>
</div>
```

#### Infinite Scroll
```html
<div class="task-list">
  {% for task in tasks %}
    <div class="task-item">{{ task.title }}</div>
  {% endfor %}
  
  <div hx-get="{{ path('app_tasks_list', {page: currentPage + 1}) }}"
       hx-trigger="intersect once"
       hx-target=".task-list"
       hx-swap="beforeend">
    <div class="loading-more">Loading more tasks...</div>
  </div>
</div>
```

### Real-time Updates

#### Polling for Updates
```html
<div id="task-status"
     hx-get="{{ path('app_task_status', {id: task.id}) }}"
     hx-trigger="every 5s"
     hx-target="this"
     hx-swap="innerHTML">
  {{ task.status }}
</div>
```

#### Event-driven Updates
```html
<div hx-get="{{ path('app_notifications') }}"
     hx-trigger="notification from:body"
     hx-target="#notification-container">
</div>

<script>
// Trigger update from JavaScript
htmx.trigger(document.body, 'notification');
</script>
```

## Styling Patterns

### Component Classes

#### Button Variations
```html
<!-- Primary button -->
<button class="btn btn-primary">Primary Action</button>

<!-- Secondary button -->
<button class="btn btn-secondary">Secondary Action</button>

<!-- Danger button -->
<button class="btn btn-danger">Delete</button>

<!-- Custom button -->
<button class="btn bg-green-600 hover:bg-green-700 text-white">
  Custom Button
</button>
```

#### Status Badges
```html
<!-- Using custom status classes -->
<span class="status-badge bg-status-todo text-white">Todo</span>
<span class="status-badge bg-status-progress text-white">In Progress</span>
<span class="status-badge bg-status-completed text-white">Completed</span>

<!-- Using priority classes -->
<span class="priority-badge bg-priority-low text-white">Low</span>
<span class="priority-badge bg-priority-high text-white">High</span>
```

#### Card Components
```html
<!-- Kanban card -->
<div class="kanban-card">
  <h3 class="font-medium text-gray-900">Task Title</h3>
  <p class="text-sm text-gray-600 mt-1">Task description...</p>
  
  <div class="flex items-center justify-between mt-3">
    <span class="status-badge bg-blue-100 text-blue-800">Todo</span>
    <span class="priority-badge bg-orange-100 text-orange-800">High</span>
  </div>
</div>
```

### Responsive Design

#### Mobile-First Approach
```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
  <!-- Responsive grid -->
</div>

<div class="hidden md:block">
  <!-- Hidden on mobile, visible on desktop -->
</div>

<div class="block md:hidden">
  <!-- Visible on mobile, hidden on desktop -->
</div>
```

#### Responsive Navigation
```html
<nav class="bg-white shadow-sm border-b border-gray-200">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center py-4">
      <!-- Logo -->
      <div class="flex-shrink-0">
        <img class="h-8 w-auto" src="/logo.png" alt="Logo">
      </div>
      
      <!-- Desktop Navigation -->
      <div class="hidden md:flex items-center space-x-8">
        <a href="/dashboard" class="text-gray-700 hover:text-blue-600">
          Dashboard
        </a>
      </div>
      
      <!-- Mobile Menu Button -->
      <div class="md:hidden">
        <button x-data @click="$dispatch('toggle-mobile-menu')">
          Menu
        </button>
      </div>
    </div>
  </div>
</nav>
```

## Animation Patterns

### GSAP Animations

#### Page Transitions
```javascript
// Fade in animation
gsap.from('.page-content', {
  opacity: 0,
  y: 20,
  duration: 0.5,
  ease: 'power2.out'
});

// Staggered list animation
gsap.from('.list-item', {
  opacity: 0,
  x: -20,
  duration: 0.3,
  stagger: 0.1,
  ease: 'power2.out'
});
```

#### Interactive Elements
```javascript
// Button hover effect
$('.btn').hover(
  function() {
    gsap.to(this, { scale: 1.05, duration: 0.2 });
  },
  function() {
    gsap.to(this, { scale: 1, duration: 0.2 });
  }
);

// Card drag animation
function animateCardDrag(element) {
  gsap.to(element, {
    rotation: 5,
    scale: 1.05,
    duration: 0.2,
    ease: 'power2.out'
  });
}
```

### CSS Transitions

#### Hover States
```css
.card-hover {
  @apply transition-all duration-200 hover:shadow-lg hover:-translate-y-1;
}

.button-hover {
  @apply transition-colors duration-150 hover:bg-blue-700;
}
```

#### Loading States
```css
.loading-spinner {
  @apply animate-spin h-5 w-5 text-blue-600;
}

.loading-pulse {
  @apply animate-pulse bg-gray-200 rounded;
}
```

## Accessibility Patterns

### Keyboard Navigation

#### Focus Management
```html
<div x-data="modalComponent()" 
     x-show="isOpen"
     @keydown.escape="closeModal()"
     x-trap="isOpen">
  
  <button x-ref="firstFocusable" @click="closeModal()">
    Close
  </button>
  
  <!-- Modal content -->
  
  <button x-ref="lastFocusable" @click="submitForm()">
    Submit
  </button>
</div>
```

#### Skip Links
```html
<a href="#main-content" class="skip-link">
  Skip to main content
</a>

<main id="main-content">
  <!-- Main content -->
</main>
```

### Screen Reader Support

#### ARIA Labels
```html
<button aria-label="Close modal" @click="closeModal()">
  <svg class="h-6 w-6" aria-hidden="true">
    <!-- Icon -->
  </svg>
</button>

<div role="status" aria-live="polite" x-show="message">
  <span x-text="message"></span>
</div>
```

#### Semantic HTML
```html
<nav aria-label="Main navigation">
  <ul>
    <li><a href="/dashboard">Dashboard</a></li>
    <li><a href="/projects">Projects</a></li>
  </ul>
</nav>

<main>
  <h1>Page Title</h1>
  <section aria-labelledby="section-heading">
    <h2 id="section-heading">Section Title</h2>
    <!-- Section content -->
  </section>
</main>
```

## Error Handling

### Form Validation

#### Client-side Validation
```html
<form x-data="formValidator()" @submit.prevent="submitForm()">
  <div class="form-group">
    <input type="email" 
           x-model="form.email"
           @blur="validateField('email')"
           :class="{ 'border-red-500': errors.email }">
    <span x-show="errors.email" 
          x-text="errors.email" 
          class="text-red-500 text-sm"></span>
  </div>
</form>
```

#### Server-side Validation
```php
// Controller response
return new JsonResponse([
  'success' => false,
  'errors' => [
    'title' => 'Title is required',
    'description' => 'Description must be at least 10 characters'
  ]
], 422);
```

```html
<!-- HTMX error handling -->
<form hx-post="/tasks/create" 
      hx-target="#form-errors">
  
  <div id="form-errors">
    <!-- Error messages will be inserted here -->
  </div>
  
  <!-- Form fields -->
</form>
```

### Error States

#### Network Errors
```html
<div x-data="{ networkError: false }" 
     @htmx:response-error.window="networkError = true"
     @htmx:after-request.window="networkError = false">
  
  <div x-show="networkError" class="error-banner">
    Network error occurred. Please try again.
  </div>
</div>
```

#### Loading States
```html
<button hx-post="/api/save" 
        hx-indicator="#save-indicator">
  Save
  <span id="save-indicator" class="htmx-indicator">
    Saving...
  </span>
</button>
```

This comprehensive component guide provides practical examples for implementing and customizing all the frontend components in SpecSrv. Each pattern is designed to be maintainable, accessible, and performant.