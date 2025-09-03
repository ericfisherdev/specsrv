/**
 * Base Page class for SpecSrv Frontend
 * Provides common functionality for all pages
 */
export class BasePage {
  constructor(params = {}, state = {}) {
    this.params = params;
    this.state = state;
    this.element = null;
    this.isDestroyed = false;

    // Service references (will be injected by main app)
    this.apiService = window.app?.apiService;
    this.authService = window.app?.authService;
    this.notificationManager = window.app?.notificationManager;

    // Page lifecycle hooks
    this.hooks = {
      beforeMount: [],
      mounted: [],
      beforeDestroy: [],
      destroyed: [],
    };
  }

  /**
   * Add lifecycle hook
   * @param {string} hook - Hook name
   * @param {Function} callback - Callback function
   */
  addHook(hook, callback) {
    if (this.hooks[hook]) {
      this.hooks[hook].push(callback);
    }
  }

  /**
   * Execute lifecycle hooks
   * @param {string} hook - Hook name
   * @param {...any} args - Arguments to pass to hooks
   */
  async executeHooks(hook, ...args) {
    if (this.hooks[hook]) {
      for (const callback of this.hooks[hook]) {
        await callback.call(this, ...args);
      }
    }
  }

  /**
   * Render the page
   * @param {HTMLElement} container - Container element
   */
  async render(container) {
    try {
      await this.executeHooks('beforeMount', container);

      // Show loading state
      this.showLoading(container);

      // Load data if needed
      await this.loadData();

      // Create the page element
      this.element = this.createElement();

      // Replace loading with actual content
      container.innerHTML = '';
      container.appendChild(this.element);

      // Initialize components
      await this.initializeComponents();

      await this.executeHooks('mounted', this.element);

    } catch (error) {
      console.error(`Error rendering ${this.constructor.name}:`, error);
      this.showError(container, error);
    }
  }

  /**
   * Show loading state
   * @param {HTMLElement} container - Container element
   */
  showLoading(container) {
    container.innerHTML = `
      <div class="flex items-center justify-center min-h-96">
        <div class="flex flex-col items-center space-y-4">
          <div class="loading-spinner loading-spinner-lg text-primary-500"></div>
          <p class="text-sm text-gray-600 dark:text-gray-400">Loading...</p>
        </div>
      </div>
    `;
  }

  /**
   * Show error state
   * @param {HTMLElement} container - Container element
   * @param {Error} error - Error object
   */
  showError(container, error) {
    container.innerHTML = `
      <div class="flex items-center justify-center min-h-96">
        <div class="text-center max-w-md">
          <div class="text-red-500 mb-4">
            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Oops! Something went wrong</h2>
          <p class="text-gray-600 dark:text-gray-400 mb-6">
            ${error.message || 'An unexpected error occurred while loading this page.'}
          </p>
          <button onclick="window.location.reload()" class="btn btn-primary">
            Try Again
          </button>
        </div>
      </div>
    `;
  }

  /**
   * Create the page element (to be implemented by subclasses)
   * @returns {HTMLElement}
   */
  createElement() {
    const element = document.createElement('div');
    element.className = 'page';
    element.innerHTML = '<p>Base page - implement createElement() in subclass</p>';
    return element;
  }

  /**
   * Load page data (to be implemented by subclasses)
   */
  async loadData() {
    // Override in subclasses
  }

  /**
   * Initialize components after rendering
   */
  async initializeComponents() {
    // Initialize Alpine.js components if present
    if (window.Alpine && this.element) {
      window.Alpine.initTree(this.element);
    }

    // Initialize HTMX if present
    if (window.htmx && this.element) {
      window.htmx.process(this.element);
    }
  }

  /**
   * Get page title
   * @returns {string}
   */
  getTitle() {
    return 'SpecSrv';
  }

  /**
   * Get page breadcrumbs
   * @returns {Array<Object>}
   */
  getBreadcrumbs() {
    return [];
  }

  /**
   * Check if user can access this page
   * @returns {boolean}
   */
  canAccess() {
    return true;
  }

  /**
   * Handle page-specific keyboard shortcuts
   * @param {KeyboardEvent} event - Keyboard event
   */
  handleKeyboard(event) {
    // Override in subclasses for page-specific shortcuts
  }

  /**
   * Destroy the page and cleanup
   */
  async destroy() {
    if (this.isDestroyed) {return;}

    await this.executeHooks('beforeDestroy');

    // Cleanup event listeners
    this.cleanup();

    // Remove element
    if (this.element && this.element.parentNode) {
      this.element.parentNode.removeChild(this.element);
    }

    this.isDestroyed = true;

    await this.executeHooks('destroyed');
  }

  /**
   * Cleanup resources (to be implemented by subclasses)
   */
  cleanup() {
    // Override in subclasses for cleanup
  }

  /**
   * Utility method to create HTML elements
   * @param {string} tagName - Tag name
   * @param {Object} attributes - Element attributes
   * @param {Array|string} children - Child elements or text
   * @returns {HTMLElement}
   */
  createElement(tagName, attributes = {}, children = []) {
    const element = document.createElement(tagName);

    // Set attributes
    Object.keys(attributes).forEach(key => {
      if (key === 'className') {
        element.className = attributes[key];
      } else if (key === 'innerHTML') {
        element.innerHTML = attributes[key];
      } else if (key.startsWith('data-') || key.startsWith('aria-')) {
        element.setAttribute(key, attributes[key]);
      } else {
        element[key] = attributes[key];
      }
    });

    // Add children
    if (typeof children === 'string') {
      element.textContent = children;
    } else if (Array.isArray(children)) {
      children.forEach(child => {
        if (typeof child === 'string') {
          element.appendChild(document.createTextNode(child));
        } else if (child instanceof HTMLElement) {
          element.appendChild(child);
        }
      });
    }

    return element;
  }

  /**
   * Utility method to create page header
   * @param {string} title - Page title
   * @param {Object} options - Header options
   * @returns {HTMLElement}
   */
  createPageHeader(title, options = {}) {
    const { subtitle, actions = [], breadcrumbs = [] } = options;

    const header = this.createElement('div', {
      className: 'bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700'
    });

    const container = this.createElement('div', {
      className: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6'
    });

    // Breadcrumbs
    if (breadcrumbs.length > 0) {
      const breadcrumbNav = this.createBreadcrumbs(breadcrumbs);
      container.appendChild(breadcrumbNav);
    }

    // Title section
    const titleSection = this.createElement('div', {
      className: `flex items-center justify-between ${breadcrumbs.length > 0 ? 'mt-4' : ''}`
    });

    const titleContainer = this.createElement('div');
    const titleElement = this.createElement('h1', {
      className: 'text-2xl font-bold text-gray-900 dark:text-white'
    }, title);
    titleContainer.appendChild(titleElement);

    if (subtitle) {
      const subtitleElement = this.createElement('p', {
        className: 'mt-1 text-sm text-gray-600 dark:text-gray-400'
      }, subtitle);
      titleContainer.appendChild(subtitleElement);
    }

    titleSection.appendChild(titleContainer);

    // Actions
    if (actions.length > 0) {
      const actionsContainer = this.createElement('div', {
        className: 'flex items-center space-x-3'
      });

      actions.forEach(action => {
        actionsContainer.appendChild(action);
      });

      titleSection.appendChild(actionsContainer);
    }

    container.appendChild(titleSection);
    header.appendChild(container);

    return header;
  }

  /**
   * Create breadcrumb navigation
   * @param {Array<Object>} breadcrumbs - Breadcrumb items
   * @returns {HTMLElement}
   */
  createBreadcrumbs(breadcrumbs) {
    const nav = this.createElement('nav', {
      className: 'flex',
      'aria-label': 'Breadcrumb'
    });

    const ol = this.createElement('ol', {
      className: 'flex items-center space-x-2 text-sm'
    });

    breadcrumbs.forEach((crumb, index) => {
      const li = this.createElement('li', {
        className: 'flex items-center'
      });

      if (index > 0) {
        const separator = this.createElement('svg', {
          className: 'w-4 h-4 text-gray-400 mr-2',
          fill: 'currentColor',
          viewBox: '0 0 20 20'
        });
        separator.innerHTML = '<path fill-rule=\'evenodd\' d=\'M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z\' clip-rule=\'evenodd\'/>';
        li.appendChild(separator);
      }

      if (crumb.url && index < breadcrumbs.length - 1) {
        const link = this.createElement('a', {
          href: crumb.url,
          className: 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
        }, crumb.label);
        li.appendChild(link);
      } else {
        const span = this.createElement('span', {
          className: 'text-gray-900 dark:text-white font-medium'
        }, crumb.label);
        li.appendChild(span);
      }

      ol.appendChild(li);
    });

    nav.appendChild(ol);
    return nav;
  }

  /**
   * Show notification
   * @param {string} message - Message
   * @param {string} type - Notification type
   * @param {Object} options - Additional options
   */
  notify(message, type = 'info', options = {}) {
    if (this.notificationManager) {
      return this.notificationManager[type](message, options);
    } else {
      console.log(`[${type.toUpperCase()}] ${message}`);
    }
  }

  /**
   * Navigate to another page
   * @param {string} path - Path to navigate to
   * @param {Object} options - Navigation options
   */
  navigate(path, options = {}) {
    if (window.app?.router) {
      window.app.router.navigate(path, options);
    } else {
      window.location.href = path;
    }
  }
}