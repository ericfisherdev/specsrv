# Styling Guide

## Overview

SpecSrv uses a comprehensive design system built on Tailwind CSS with custom extensions. This guide covers styling conventions, component patterns, and best practices for maintaining visual consistency.

## Design System

### Color Palette

#### Primary Colors
```css
/* Blue spectrum for primary actions and navigation */
primary-50:  #f0f9ff  /* Light backgrounds */
primary-100: #e0f2fe  /* Subtle highlights */
primary-200: #bae6fd  /* Borders and dividers */
primary-300: #7dd3fc  /* Disabled states */
primary-400: #38bdf8  /* Hover states */
primary-500: #0ea5e9  /* Primary brand color */
primary-600: #0284c7  /* Active states */
primary-700: #0369a1  /* Text and icons */
primary-800: #075985  /* Headings */
primary-900: #0c4a6e  /* Dark text */
```

#### Semantic Colors
```css
/* Task Status Colors */
status-backlog:   #6b7280  /* Gray-500 */
status-todo:      #3b82f6  /* Blue-500 */
status-progress:  #f59e0b  /* Amber-500 */
status-review:    #8b5cf6  /* Violet-500 */
status-completed: #10b981  /* Emerald-500 */

/* Priority Colors */
priority-low:      #10b981  /* Emerald-500 */
priority-medium:   #f59e0b  /* Amber-500 */
priority-high:     #f97316  /* Orange-500 */
priority-critical: #ef4444  /* Red-500 */
```

#### Neutral Colors
```css
/* Gray spectrum for text and backgrounds */
gray-50:  #f9fafb  /* Page backgrounds */
gray-100: #f3f4f6  /* Card backgrounds */
gray-200: #e5e7eb  /* Borders */
gray-300: #d1d5db  /* Input borders */
gray-400: #9ca3af  /* Disabled text */
gray-500: #6b7280  /* Secondary text */
gray-600: #4b5563  /* Primary text */
gray-700: #374151  /* Headings */
gray-800: #1f2937  /* Dark headings */
gray-900: #111827  /* High contrast text */
```

### Typography

#### Font Scale
```css
text-xs:   0.75rem   /* 12px - Small labels */
text-sm:   0.875rem  /* 14px - Body text */
text-base: 1rem      /* 16px - Default */
text-lg:   1.125rem  /* 18px - Large body */
text-xl:   1.25rem   /* 20px - Subheadings */
text-2xl:  1.5rem    /* 24px - Headings */
text-3xl:  1.875rem  /* 30px - Page titles */
text-4xl:  2.25rem   /* 36px - Hero text */
```

#### Font Weights
```css
font-light:    300  /* Light text */
font-normal:   400  /* Body text */
font-medium:   500  /* Emphasized text */
font-semibold: 600  /* Subheadings */
font-bold:     700  /* Headings */
font-extrabold: 800 /* Hero text */
```

#### Line Heights
```css
leading-tight:  1.25   /* Headings */
leading-snug:   1.375  /* Subheadings */
leading-normal: 1.5    /* Body text */
leading-relaxed: 1.625 /* Large text blocks */
leading-loose:  2      /* Emphasized spacing */
```

### Spacing

#### Standard Scale
```css
/* Tailwind spacing scale */
0:   0px      /* No spacing */
1:   0.25rem  /* 4px  - Minimal spacing */
2:   0.5rem   /* 8px  - Small spacing */
3:   0.75rem  /* 12px - Medium-small */
4:   1rem     /* 16px - Standard */
5:   1.25rem  /* 20px - Medium */
6:   1.5rem   /* 24px - Medium-large */
8:   2rem     /* 32px - Large */
10:  2.5rem   /* 40px - Extra large */
12:  3rem     /* 48px - Section spacing */
16:  4rem     /* 64px - Page spacing */
20:  5rem     /* 80px - Hero spacing */
```

#### Custom Spacing
```css
/* Additional spacing values */
18: 4.5rem   /* 72px - Custom medium-large */
88: 22rem    /* 352px - Large containers */
```

## Component Styles

### Buttons

#### Base Button Class
```css
.btn {
  @apply px-4 py-2 text-sm font-medium rounded-md transition-colors duration-200 
         focus:outline-none focus:ring-2 focus:ring-offset-2 
         disabled:opacity-50 disabled:cursor-not-allowed;
}
```

#### Button Variants
```html
<!-- Primary Button -->
<button class="btn btn-primary">
  Primary Action
</button>

<!-- Secondary Button -->
<button class="btn btn-secondary">
  Secondary Action
</button>

<!-- Danger Button -->
<button class="btn btn-danger">
  Delete
</button>

<!-- Ghost Button -->
<button class="btn text-gray-600 hover:text-gray-800">
  Ghost Action
</button>
```

#### Button Sizes
```html
<!-- Small Button -->
<button class="btn btn-primary text-xs px-3 py-1.5">
  Small
</button>

<!-- Default Button -->
<button class="btn btn-primary">
  Default
</button>

<!-- Large Button -->
<button class="btn btn-primary text-base px-6 py-3">
  Large
</button>
```

### Cards

#### Base Card Class
```css
.card {
  @apply bg-white rounded-lg shadow-sm border border-gray-200;
}

.card-hover {
  @apply card hover:shadow-md transition-shadow duration-200;
}
```

#### Card Variants
```html
<!-- Basic Card -->
<div class="card p-6">
  <h3 class="text-lg font-semibold mb-2">Card Title</h3>
  <p class="text-gray-600">Card content...</p>
</div>

<!-- Interactive Card -->
<div class="card card-hover p-6 cursor-pointer">
  <h3 class="text-lg font-semibold mb-2">Interactive Card</h3>
  <p class="text-gray-600">Hover to see effect...</p>
</div>

<!-- Kanban Card -->
<div class="kanban-card">
  <h3 class="font-medium text-gray-900">Task Title</h3>
  <p class="text-sm text-gray-600 mt-1">Task description...</p>
</div>
```

### Forms

#### Form Layout
```html
<form class="space-y-6">
  <div class="form-group">
    <label class="form-label" for="title">
      Task Title
    </label>
    <input type="text" 
           id="title" 
           class="form-input" 
           placeholder="Enter task title">
  </div>
  
  <div class="form-group">
    <label class="form-label" for="description">
      Description
    </label>
    <textarea id="description" 
              class="form-textarea" 
              rows="4" 
              placeholder="Enter description"></textarea>
  </div>
</form>
```

#### Form Element Classes
```css
.form-label {
  @apply block text-sm font-medium text-gray-700 mb-1;
}

.form-input {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md 
         focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
         disabled:bg-gray-50 disabled:text-gray-500;
}

.form-textarea {
  @apply form-input resize-vertical;
}

.form-select {
  @apply form-input bg-white;
}

.form-error {
  @apply border-red-500 focus:ring-red-500 focus:border-red-500;
}
```

#### Form Validation States
```html
<!-- Error State -->
<div class="form-group">
  <label class="form-label text-red-700" for="email">
    Email Address
  </label>
  <input type="email" 
         id="email" 
         class="form-input form-error">
  <p class="mt-1 text-sm text-red-600">
    Please enter a valid email address.
  </p>
</div>

<!-- Success State -->
<div class="form-group">
  <label class="form-label text-green-700" for="username">
    Username
  </label>
  <input type="text" 
         id="username" 
         class="form-input border-green-500 focus:ring-green-500">
  <p class="mt-1 text-sm text-green-600">
    Username is available!
  </p>
</div>
```

### Badges

#### Status Badges
```css
.status-badge {
  @apply inline-flex items-center px-2 py-1 text-xs font-medium rounded-full;
}
```

```html
<!-- Status Badge Variants -->
<span class="status-badge bg-gray-100 text-gray-800">Backlog</span>
<span class="status-badge bg-blue-100 text-blue-800">Todo</span>
<span class="status-badge bg-amber-100 text-amber-800">In Progress</span>
<span class="status-badge bg-violet-100 text-violet-800">Review</span>
<span class="status-badge bg-emerald-100 text-emerald-800">Completed</span>
```

#### Priority Badges
```css
.priority-badge {
  @apply inline-flex items-center px-2 py-1 text-xs font-medium rounded-full;
}
```

```html
<!-- Priority Badge Variants -->
<span class="priority-badge bg-emerald-100 text-emerald-800">Low</span>
<span class="priority-badge bg-amber-100 text-amber-800">Medium</span>
<span class="priority-badge bg-orange-100 text-orange-800">High</span>
<span class="priority-badge bg-red-100 text-red-800">Critical</span>
```

### Navigation

#### Header Navigation
```html
<nav class="bg-white shadow-sm border-b border-gray-200">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center py-4">
      <!-- Logo -->
      <div class="flex items-center">
        <img class="h-8 w-auto" src="/logo.png" alt="SpecSrv">
        <span class="ml-2 text-xl font-bold text-gray-900">SpecSrv</span>
      </div>
      
      <!-- Navigation Links -->
      <div class="hidden md:flex items-center space-x-8">
        <a href="/dashboard" class="nav-link nav-link-active">Dashboard</a>
        <a href="/projects" class="nav-link">Projects</a>
        <a href="/tasks" class="nav-link">Tasks</a>
      </div>
    </div>
  </div>
</nav>
```

#### Navigation Link Classes
```css
.nav-link {
  @apply text-gray-700 hover:text-blue-600 px-3 py-2 text-sm font-medium 
         transition-colors duration-150 rounded-md;
}

.nav-link-active {
  @apply text-blue-600 bg-blue-50;
}
```

### Modals

#### Modal Structure
```html
<div class="fixed inset-0 z-50 overflow-y-auto" x-show="isOpen">
  <!-- Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
       @click="closeModal()"></div>
  
  <!-- Modal Panel -->
  <div class="flex min-h-full items-center justify-center p-4">
    <div class="modal-panel">
      <!-- Modal Header -->
      <div class="modal-header">
        <h2 class="text-lg font-semibold text-gray-900">Modal Title</h2>
        <button @click="closeModal()" class="modal-close-btn">
          <svg class="h-6 w-6"><!-- Close icon --></svg>
        </button>
      </div>
      
      <!-- Modal Body -->
      <div class="modal-body">
        <!-- Modal content -->
      </div>
      
      <!-- Modal Footer -->
      <div class="modal-footer">
        <button class="btn btn-secondary" @click="closeModal()">
          Cancel
        </button>
        <button class="btn btn-primary" @click="submitForm()">
          Save
        </button>
      </div>
    </div>
  </div>
</div>
```

#### Modal Component Classes
```css
.modal-panel {
  @apply bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] 
         overflow-hidden relative;
}

.modal-header {
  @apply px-6 py-4 border-b border-gray-200 flex items-center justify-between;
}

.modal-body {
  @apply px-6 py-4 overflow-y-auto;
}

.modal-footer {
  @apply px-6 py-4 border-t border-gray-200 flex justify-end space-x-3;
}

.modal-close-btn {
  @apply text-gray-400 hover:text-gray-600 focus:outline-none 
         focus:ring-2 focus:ring-blue-500 rounded-md p-1;
}
```

### Tables

#### Table Structure
```html
<div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
  <table class="table">
    <thead class="table-header">
      <tr>
        <th class="table-header-cell">Name</th>
        <th class="table-header-cell">Status</th>
        <th class="table-header-cell">Priority</th>
        <th class="table-header-cell">Actions</th>
      </tr>
    </thead>
    <tbody class="table-body">
      <tr class="table-row">
        <td class="table-cell">Task Title</td>
        <td class="table-cell">
          <span class="status-badge bg-blue-100 text-blue-800">Todo</span>
        </td>
        <td class="table-cell">
          <span class="priority-badge bg-orange-100 text-orange-800">High</span>
        </td>
        <td class="table-cell">
          <button class="btn btn-primary text-xs">Edit</button>
        </td>
      </tr>
    </tbody>
  </table>
</div>
```

#### Table Component Classes
```css
.table {
  @apply min-w-full divide-y divide-gray-300;
}

.table-header {
  @apply bg-gray-50;
}

.table-header-cell {
  @apply px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider;
}

.table-body {
  @apply bg-white divide-y divide-gray-200;
}

.table-row {
  @apply hover:bg-gray-50;
}

.table-cell {
  @apply px-6 py-4 whitespace-nowrap text-sm text-gray-900;
}
```

## Layout Patterns

### Page Layout
```html
<div class="min-h-screen bg-gray-50">
  <!-- Header -->
  <header class="bg-white shadow-sm">
    <!-- Navigation content -->
  </header>
  
  <!-- Main Content -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title">Page Title</h1>
      <p class="page-subtitle">Page description or breadcrumb</p>
    </div>
    
    <!-- Page Content -->
    <div class="page-content">
      <!-- Main content -->
    </div>
  </main>
</div>
```

#### Page Component Classes
```css
.page-header {
  @apply mb-8;
}

.page-title {
  @apply text-3xl font-bold text-gray-900;
}

.page-subtitle {
  @apply mt-2 text-gray-600;
}

.page-content {
  @apply space-y-8;
}
```

### Grid Layouts
```html
<!-- Responsive Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
  <div class="card p-6">Item 1</div>
  <div class="card p-6">Item 2</div>
  <div class="card p-6">Item 3</div>
  <div class="card p-6">Item 4</div>
</div>

<!-- Dashboard Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
  <!-- Main Content (2/3 width on large screens) -->
  <div class="lg:col-span-2 space-y-8">
    <!-- Main content -->
  </div>
  
  <!-- Sidebar (1/3 width on large screens) -->
  <div class="space-y-8">
    <!-- Sidebar content -->
  </div>
</div>
```

### Flexbox Layouts
```html
<!-- Header with Navigation -->
<header class="flex items-center justify-between p-4">
  <div class="flex items-center space-x-4">
    <img src="/logo.png" alt="Logo" class="h-8">
    <h1 class="text-xl font-bold">SpecSrv</h1>
  </div>
  
  <nav class="flex items-center space-x-6">
    <a href="/dashboard" class="nav-link">Dashboard</a>
    <a href="/projects" class="nav-link">Projects</a>
  </nav>
</header>

<!-- Card with Actions -->
<div class="card p-6">
  <div class="flex items-start justify-between">
    <div class="flex-1 min-w-0">
      <h3 class="font-medium text-gray-900 truncate">Card Title</h3>
      <p class="text-sm text-gray-600 mt-1">Card description...</p>
    </div>
    
    <div class="flex items-center space-x-2 ml-4">
      <button class="btn btn-primary text-xs">Edit</button>
      <button class="btn btn-danger text-xs">Delete</button>
    </div>
  </div>
</div>
```

## Animation and Transitions

### CSS Transitions
```css
/* Hover Transitions */
.transition-hover {
  @apply transition-all duration-200 hover:shadow-lg hover:-translate-y-1;
}

/* Color Transitions */
.transition-colors {
  @apply transition-colors duration-150;
}

/* Transform Transitions */
.transition-transform {
  @apply transition-transform duration-200;
}
```

### Custom Animations
```css
/* Fade Animations */
.animate-fade-in {
  @apply animate-fade-in;
}

.animate-fade-out {
  @apply animate-fade-out;
}

/* Slide Animations */
.animate-slide-up {
  @apply animate-slide-up;
}

.animate-slide-down {
  @apply animate-slide-down;
}

/* Loading States */
.animate-pulse-soft {
  @apply animate-pulse-soft;
}
```

### Alpine.js Transitions
```html
<!-- Fade Transition -->
<div x-show="isVisible"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
  Content with fade transition
</div>

<!-- Scale Transition -->
<div x-show="isVisible"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform scale-95"
     x-transition:enter-end="opacity-100 transform scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform scale-100"
     x-transition:leave-end="opacity-0 transform scale-95">
  Content with scale transition
</div>
```

## Responsive Design

### Breakpoints
```css
/* Tailwind CSS Breakpoints */
sm:  640px   /* Small tablets */
md:  768px   /* Large tablets */
lg:  1024px  /* Small desktops */
xl:  1280px  /* Large desktops */
2xl: 1536px  /* Extra large screens */
```

### Mobile-First Approach
```html
<!-- Mobile-first responsive classes -->
<div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/6">
  Responsive width
</div>

<!-- Hide/show at different breakpoints -->
<div class="block md:hidden">
  Mobile only content
</div>

<div class="hidden md:block lg:hidden">
  Tablet only content
</div>

<div class="hidden lg:block">
  Desktop only content
</div>
```

### Container Sizes
```html
<!-- Responsive containers -->
<div class="container mx-auto px-4 sm:px-6 lg:px-8">
  <!-- Full width container with responsive padding -->
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
  <!-- Constrained width container -->
</div>

<div class="max-w-md mx-auto">
  <!-- Small centered container -->
</div>
```

## Accessibility Considerations

### Color Contrast
- Ensure minimum 4.5:1 contrast ratio for normal text
- Ensure minimum 3:1 contrast ratio for large text
- Use tools like WebAIM Color Contrast Checker

### Focus States
```css
/* Custom focus styles */
.focus-visible {
  @apply focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
}

/* Skip link */
.skip-link {
  @apply absolute left-[-10000px] top-auto w-1 h-1 overflow-hidden
         focus:left-6 focus:top-7 focus:w-auto focus:h-auto focus:overflow-visible
         bg-blue-600 text-white px-4 py-2 rounded-md z-50;
}
```

### Screen Reader Support
```html
<!-- Descriptive text for screen readers -->
<button aria-label="Close modal">
  <svg aria-hidden="true"><!-- Icon --></svg>
</button>

<!-- Status announcements -->
<div role="status" aria-live="polite" class="sr-only">
  Task created successfully
</div>

<!-- Loading states -->
<div role="status" aria-label="Loading content">
  <span class="sr-only">Loading...</span>
  <!-- Spinner -->
</div>
```

## Best Practices

### Performance
1. **Minimize CSS size** - Use Tailwind's JIT mode and purging
2. **Optimize images** - Use appropriate formats and sizes
3. **Reduce reflows** - Avoid layout-triggering properties in animations
4. **Use GPU acceleration** - Prefer `transform` over position changes

### Maintainability
1. **Use semantic class names** - Combine utility classes into components
2. **Document custom styles** - Comment complex CSS rules
3. **Follow naming conventions** - Use consistent naming patterns
4. **Group related styles** - Organize CSS logically

### Consistency
1. **Use design tokens** - Stick to the defined color palette and spacing
2. **Follow component patterns** - Reuse established component styles
3. **Maintain visual hierarchy** - Use consistent typography scale
4. **Test across devices** - Ensure responsive behavior works correctly

This styling guide provides the foundation for maintaining visual consistency and implementing effective UI patterns throughout the SpecSrv application.