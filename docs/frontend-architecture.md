# Frontend Architecture Documentation

## Overview

SpecSrv uses a modern, lightweight frontend architecture built on HTMX, Alpine.js, GSAP, and Tailwind CSS. This document outlines the component architecture, styling conventions, and development patterns.

## Technology Stack

### Core Technologies
- **HTMX** - Server-driven HTML over-the-wire interactions
- **Alpine.js** - Minimal JavaScript framework for reactive UI components
- **GSAP** - High-performance animations and micro-interactions
- **Tailwind CSS** - Utility-first CSS framework with custom design system
- **Symfony Webpack Encore** - Asset bundling and optimization

### Build Tools
- **Webpack 5** - Module bundling with optimization
- **PostCSS** - CSS processing with plugins
- **Babel** - JavaScript transpilation
- **ESBuild** - Fast JavaScript bundling (via Encore)

## Architecture Principles

### 1. Server-First Approach
- **HTMX drives interactions** - Most UI updates come from server-rendered HTML fragments
- **Minimal client-side state** - Server maintains the source of truth
- **Progressive enhancement** - Base functionality works without JavaScript

### 2. Component-Based Structure
- **Alpine.js components** - Self-contained reactive UI components
- **Twig templates** - Reusable server-side components
- **Utility classes** - Tailwind CSS for consistent styling

### 3. Performance-Focused
- **Asset optimization** - Minification, compression, and caching
- **Code splitting** - Vendor and common chunks
- **Critical CSS** - Above-the-fold optimization
- **Image optimization** - WebP, lazy loading, and responsive images

## Component Architecture

### Alpine.js Components

#### 1. Kanban Board (`kanbanBoard()`)
**Location**: `/templates/kanban/index.html.twig`

**Purpose**: Manages the drag-and-drop kanban board functionality.

**Key Features**:
- Drag-and-drop task movement between columns
- Real-time status updates via HTMX
- Task filtering and search
- Visual feedback and animations

**State Management**:
```javascript
{
  draggedTask: null,
  draggedFromStatus: null,
  backlogTasks: [],
  todoTasks: [],
  workingTasks: [],
  reviewTasks: [],
  doneTasks: []
}
```

**Key Methods**:
- `handleDragStart()` - Initiates drag operation
- `handleDrop()` - Processes task status change
- `updateTaskStatus()` - API call to persist changes

#### 2. Task Modal (`taskModal()`)
**Location**: `/templates/kanban/index.html.twig`

**Purpose**: Handles task creation and editing modals.

**Key Features**:
- Form validation and submission
- File upload with drag-and-drop
- Project selection
- Real-time preview

**State Management**:
```javascript
{
  isOpen: false,
  isSubmitting: false,
  form: {
    title: '',
    description: '',
    project_id: '',
    priority: 'medium'
  },
  files: []
}
```

#### 3. Search Autocomplete (`searchAutocomplete()`)
**Location**: `/templates/components/_search_autocomplete.html.twig`

**Purpose**: Global search with autocomplete suggestions.

**Key Features**:
- Debounced search input
- Keyboard navigation
- Recent searches persistence
- Context-aware suggestions

#### 4. File Upload (`fileUpload()`)
**Location**: Multiple templates

**Purpose**: Handles file upload interactions.

**Key Features**:
- Drag-and-drop support
- Progress indicators
- File validation
- Error handling

### Twig Components

#### 1. Base Layout
**File**: `/templates/base.html.twig`
**Purpose**: Main application layout with navigation

#### 2. Search Autocomplete
**File**: `/templates/components/_search_autocomplete.html.twig`
**Purpose**: Reusable search component

#### 3. File Upload
**File**: `/templates/components/_file_upload.html.twig`
**Purpose**: Reusable file upload interface

## Styling System

### Tailwind CSS Configuration

#### Custom Design System
```javascript
// Custom colors for task management
colors: {
  status: {
    backlog: '#6b7280',    // gray-500
    todo: '#3b82f6',       // blue-500
    progress: '#f59e0b',   // amber-500
    review: '#8b5cf6',     // violet-500
    completed: '#10b981',  // emerald-500
  },
  priority: {
    low: '#10b981',        // emerald-500
    medium: '#f59e0b',     // amber-500  
    high: '#f97316',       // orange-500
    critical: '#ef4444',   // red-500
  }
}
```

#### Component Utilities
```css
.kanban-card {
  @apply bg-white rounded-lg shadow-sm border border-gray-200 p-4 
         hover:shadow-md cursor-pointer transition-shadow duration-200;
}

.btn-primary {
  @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500;
}
```

### Animation System

#### GSAP Integration
- **Task card animations** - Smooth transitions during drag operations
- **Modal animations** - Fade and scale effects
- **Loading states** - Spinner and progress animations
- **Micro-interactions** - Hover effects and feedback

#### CSS Animations
```css
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { transform: translateY(10px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
```

## HTMX Patterns

### Common Interactions

#### 1. Form Submissions
```html
<form hx-post="/tasks/create" 
      hx-target="#task-list" 
      hx-swap="beforeend">
  <!-- form fields -->
</form>
```

#### 2. Dynamic Content Loading
```html
<div hx-get="/tasks/{{ task.id }}" 
     hx-trigger="load" 
     hx-target="this" 
     hx-swap="outerHTML">
  Loading...
</div>
```

#### 3. Real-time Updates
```html
<div hx-get="/api/tasks/status" 
     hx-trigger="every 30s" 
     hx-target="#status-indicator">
</div>
```

### Response Patterns

#### Success Responses
- Return HTML fragments for direct DOM replacement
- Include success indicators or animations
- Update multiple targets when needed

#### Error Handling
- Return error messages in appropriate containers
- Preserve form state where possible
- Provide clear user feedback

## File Organization

### Directory Structure
```
backend/
├── assets/
│   ├── app.js              # Main JavaScript entry point
│   ├── styles/
│   │   └── app.css         # Main CSS entry point
│   └── components/
│       ├── kanban.js       # Kanban-specific JavaScript
│       └── search.js       # Search functionality
├── templates/
│   ├── base.html.twig      # Main layout
│   ├── components/         # Reusable components
│   ├── kanban/            # Kanban templates
│   ├── projects/          # Project templates
│   ├── tasks/             # Task templates
│   └── search/            # Search templates
└── public/build/          # Compiled assets
```

### Asset Pipeline

#### Development
1. **Watch mode**: `npm run watch`
2. **Hot reload**: Webpack dev server with live reloading
3. **Source maps**: Full source maps for debugging

#### Production
1. **Optimization**: Minification and compression
2. **Code splitting**: Vendor chunks and commons
3. **Cache busting**: Hashed filenames
4. **Asset optimization**: Image compression and optimization

## Performance Guidelines

### JavaScript Performance
- Use Alpine.js for minimal reactive components
- Avoid large JavaScript libraries
- Leverage HTMX for server-side processing
- Implement efficient event handling

### CSS Performance
- Use Tailwind's JIT mode for minimal CSS
- Implement critical CSS extraction
- Avoid complex selectors
- Use CSS custom properties for themes

### Image Optimization
- Use WebP format when supported
- Implement lazy loading for non-critical images
- Serve responsive images based on viewport
- Compress images during build process

## Accessibility Guidelines

### Semantic HTML
- Use proper heading hierarchy
- Include ARIA labels where needed
- Ensure keyboard navigation support
- Provide alt text for images

### Focus Management
- Manage focus in modals and dialogs
- Provide visible focus indicators
- Support tab navigation
- Implement skip links

### Screen Reader Support
- Use semantic elements
- Provide descriptive text
- Announce dynamic content changes
- Support assistive technologies

## Development Workflow

### Setup
1. Install dependencies: `npm install`
2. Start development server: `npm run dev-server`
3. Watch for changes: `npm run watch`

### Building
1. Development build: `npm run dev`
2. Production build: `npm run build`
3. Watch mode: `npm run watch`

### Testing
- Test in multiple browsers
- Verify responsive design
- Check accessibility compliance
- Test with JavaScript disabled

## Browser Support

### Target Browsers
- **Chrome**: Latest 2 versions
- **Firefox**: Latest 2 versions  
- **Safari**: Latest 2 versions
- **Edge**: Latest 2 versions

### Progressive Enhancement
- Core functionality works without JavaScript
- Enhanced features require modern browser features
- Graceful degradation for older browsers

## Troubleshooting

### Common Issues

#### HTMX Not Working
- Check for JavaScript errors in console
- Verify HTMX library is loaded
- Ensure proper CSRF token handling
- Check HTTP response status codes

#### Alpine.js Component Issues
- Verify proper `x-data` initialization
- Check for JavaScript syntax errors
- Ensure proper event binding
- Debug with Alpine DevTools

#### Styling Issues
- Check Tailwind CSS compilation
- Verify proper class names
- Check for CSS specificity conflicts
- Use browser dev tools for debugging

#### Performance Issues
- Check for memory leaks in Alpine components
- Monitor network requests
- Optimize large datasets
- Profile JavaScript execution

## Future Enhancements

### Planned Features
- Server-sent events for real-time updates
- Progressive Web App capabilities
- Advanced animation sequences
- Enhanced accessibility features

### Technical Debt
- Refactor large Alpine components into smaller ones
- Implement more comprehensive error handling
- Add automated accessibility testing
- Optimize bundle size further