/**
 * Simple client-side router for SpecSrv Frontend
 * Handles navigation and dynamic content loading
 */
export class Router {
  constructor() {
    this.routes = new Map();
    this.currentRoute = null;
    this.basePath = '';
    this.defaultRoute = '/dashboard';
    this.notFoundRoute = '/404';

    // Route middleware
    this.beforeEachCallbacks = [];
    this.afterEachCallbacks = [];
  }

  /**
   * Initialize the router
   */
  init() {
    this.setupDefaultRoutes();
    this.bindEvents();
    this.handleInitialRoute();
  }

  /**
   * Setup default routes
   */
  setupDefaultRoutes() {
    // Authentication routes
    this.addRoute('/login', {
      component: 'LoginPage',
      requiresAuth: false,
      title: 'Login - SpecSrv',
    });

    this.addRoute('/register', {
      component: 'RegisterPage',
      requiresAuth: false,
      title: 'Register - SpecSrv',
    });

    // Main application routes
    this.addRoute('/dashboard', {
      component: 'DashboardPage',
      requiresAuth: true,
      title: 'Dashboard - SpecSrv',
    });

    this.addRoute('/projects', {
      component: 'ProjectListPage',
      requiresAuth: true,
      title: 'Projects - SpecSrv',
    });

    this.addRoute('/projects/:id', {
      component: 'ProjectDetailPage',
      requiresAuth: true,
      title: 'Project Details - SpecSrv',
    });

    this.addRoute('/kanban', {
      component: 'KanbanPage',
      requiresAuth: true,
      title: 'Kanban Board - SpecSrv',
    });

    this.addRoute('/tasks', {
      component: 'TaskListPage',
      requiresAuth: true,
      title: 'Tasks - SpecSrv',
    });

    this.addRoute('/tasks/:id', {
      component: 'TaskDetailPage',
      requiresAuth: true,
      title: 'Task Details - SpecSrv',
    });

    this.addRoute('/profile', {
      component: 'ProfilePage',
      requiresAuth: true,
      title: 'Profile - SpecSrv',
    });

    this.addRoute('/search', {
      component: 'SearchPage',
      requiresAuth: true,
      title: 'Search - SpecSrv',
    });

    // Error routes
    this.addRoute('/404', {
      component: 'NotFoundPage',
      requiresAuth: false,
      title: 'Page Not Found - SpecSrv',
    });
  }

  /**
   * Add a route
   * @param {string} path - Route path
   * @param {Object} config - Route configuration
   */
  addRoute(path, config) {
    const routeRegex = this.pathToRegex(path);
    this.routes.set(path, {
      ...config,
      path,
      regex: routeRegex,
      params: this.extractParamNames(path),
    });
  }

  /**
   * Convert path to regex
   * @param {string} path - Route path
   * @returns {RegExp}
   */
  pathToRegex(path) {
    const escapedPath = path.replace(/\//g, '\\/');
    const paramRegex = escapedPath.replace(/:([^/]+)/g, '([^/]+)');
    return new RegExp(`^${paramRegex}$`);
  }

  /**
   * Extract parameter names from path
   * @param {string} path - Route path
   * @returns {Array<string>}
   */
  extractParamNames(path) {
    const matches = path.match(/:([^/]+)/g);
    return matches ? matches.map(match => match.slice(1)) : [];
  }

  /**
   * Add before navigation middleware
   * @param {Function} callback - Middleware function
   */
  beforeEach(callback) {
    this.beforeEachCallbacks.push(callback);
  }

  /**
   * Add after navigation middleware
   * @param {Function} callback - Middleware function
   */
  afterEach(callback) {
    this.afterEachCallbacks.push(callback);
  }

  /**
   * Navigate to a route
   * @param {string} path - Path to navigate to
   * @param {Object} options - Navigation options
   */
  async navigate(path, options = {}) {
    const { replace = false, state = {} } = options;

    try {
      // Run before navigation middleware
      for (const callback of this.beforeEachCallbacks) {
        const result = await callback(path, this.currentRoute);
        if (result === false) {
          return; // Navigation cancelled
        }
        if (typeof result === 'string') {
          path = result; // Redirect to different path
        }
      }

      const route = this.matchRoute(path);
      if (!route) {
        return this.navigate(this.notFoundRoute, { replace: true });
      }

      // Check authentication requirements
      if (route.requiresAuth && !this.isAuthenticated()) {
        return this.navigate('/login', { replace: true });
      }

      if (!route.requiresAuth && path !== '/login' && path !== '/register' && this.isAuthenticated()) {
        return this.navigate(this.defaultRoute, { replace: true });
      }

      // Update browser history
      if (replace) {
        history.replaceState(state, '', this.basePath + path);
      } else {
        history.pushState(state, '', this.basePath + path);
      }

      // Load the route
      await this.loadRoute(route, path, state);

      // Run after navigation middleware
      for (const callback of this.afterEachCallbacks) {
        await callback(route, this.currentRoute);
      }

      this.currentRoute = { ...route, path };

    } catch (error) {
      console.error('Navigation error:', error);
      if (path !== this.notFoundRoute) {
        this.navigate(this.notFoundRoute, { replace: true });
      }
    }
  }

  /**
   * Match a path to a route
   * @param {string} path - Path to match
   * @returns {Object|null}
   */
  matchRoute(path) {
    for (const [routePath, route] of this.routes) {
      const match = path.match(route.regex);
      if (match) {
        const params = {};
        route.params.forEach((param, index) => {
          params[param] = match[index + 1];
        });
        return { ...route, params };
      }
    }
    return null;
  }

  /**
   * Load a route
   * @param {Object} route - Route configuration
   * @param {string} path - Current path
   * @param {Object} state - Navigation state
   */
  async loadRoute(route, path, state) {
    // Update document title
    if (route.title) {
      document.title = route.title;
    }

    // Dispatch route change event
    window.dispatchEvent(new CustomEvent('route:change', {
      detail: { route, path, state }
    }));

    // If we have a component specified, try to load it
    if (route.component) {
      await this.loadComponent(route.component, route.params, state);
    }

    // Update active navigation states
    this.updateNavigationState(path);
  }

  /**
   * Load a component for the route
   * @param {string} componentName - Component name
   * @param {Object} params - Route parameters
   * @param {Object} state - Navigation state
   */
  async loadComponent(componentName, params, state) {
    const mainContent = document.getElementById('main-content');
    if (!mainContent) {return;}

    try {
      // Show loading state
      mainContent.innerHTML = '<div class=\'flex items-center justify-center py-12\'><div class=\'loading-spinner loading-spinner-lg\'></div></div>';

      // Dynamic import of the component
      const componentModule = await import(`../pages/${componentName}.js`);
      const Component = componentModule.default || componentModule[componentName];

      if (Component) {
        const componentInstance = new Component(params, state);
        await componentInstance.render(mainContent);
      } else {
        throw new Error(`Component ${componentName} not found`);
      }
    } catch (error) {
      console.error(`Failed to load component ${componentName}:`, error);
      mainContent.innerHTML = `
        <div class="text-center py-12">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Failed to load page</h2>
          <p class="text-gray-600 dark:text-gray-400 mb-4">There was an error loading this page.</p>
          <button onclick="window.location.reload()" class="btn btn-primary">Reload Page</button>
        </div>
      `;
    }
  }

  /**
   * Update navigation active states
   * @param {string} currentPath - Current path
   */
  updateNavigationState(currentPath) {
    // Remove active class from all nav links
    document.querySelectorAll('.nav-link').forEach(link => {
      link.classList.remove('active');
    });

    // Add active class to current nav link
    const currentLink = document.querySelector(`[href="${currentPath}"]`);
    if (currentLink) {
      currentLink.classList.add('active');
    }
  }

  /**
   * Check if user is authenticated
   * @returns {boolean}
   */
  isAuthenticated() {
    return !!localStorage.getItem('specsrv-token');
  }

  /**
   * Bind browser events
   */
  bindEvents() {
    // Handle browser back/forward buttons
    window.addEventListener('popstate', (event) => {
      const path = window.location.pathname.replace(this.basePath, '') || '/';
      this.navigate(path, { replace: true, state: event.state || {} });
    });

    // Handle navigation link clicks
    document.addEventListener('click', (event) => {
      const link = event.target.closest('a[href]');
      if (link && this.shouldHandleLink(link)) {
        event.preventDefault();
        const href = link.getAttribute('href');
        this.navigate(href);
      }
    });
  }

  /**
   * Check if link should be handled by router
   * @param {HTMLElement} link - Link element
   * @returns {boolean}
   */
  shouldHandleLink(link) {
    const href = link.getAttribute('href');

    // Don"t handle external links
    if (href.startsWith('http') || href.startsWith('//')) {
      return false;
    }

    // Don"t handle links with target="_blank"
    if (link.getAttribute('target') === '_blank') {
      return false;
    }

    // Don"t handle links with download attribute
    if (link.hasAttribute('download')) {
      return false;
    }

    // Don"t handle mailto: or tel: links
    if (href.startsWith('mailto:') || href.startsWith('tel:')) {
      return false;
    }

    return true;
  }

  /**
   * Handle initial route on page load
   */
  handleInitialRoute() {
    const path = window.location.pathname.replace(this.basePath, '') || '/';

    // If we"re at root, redirect to default route
    if (path === '/') {
      return this.navigate(this.defaultRoute, { replace: true });
    }

    this.navigate(path, { replace: true });
  }

  /**
   * Get current route
   * @returns {Object|null}
   */
  getCurrentRoute() {
    return this.currentRoute;
  }

  /**
   * Get route parameters
   * @returns {Object}
   */
  getParams() {
    return this.currentRoute?.params || {};
  }

  /**
   * Go back in history
   */
  back() {
    history.back();
  }

  /**
   * Go forward in history
   */
  forward() {
    history.forward();
  }

  /**
   * Replace current route
   * @param {string} path - New path
   * @param {Object} state - Navigation state
   */
  replace(path, state = {}) {
    this.navigate(path, { replace: true, state });
  }
}