/*jslint browser: true, devel: true */
/*
 * Welcome to your app"s main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// Import CSS styles
import './styles/app.css';

// Import HTMX for dynamic HTML interactions
import htmx from 'htmx.org';
window.htmx = htmx;

// Import Alpine.js for reactive components
import Alpine from 'alpinejs';
window.Alpine = Alpine;

// Import GSAP for animations
import { gsap } from 'gsap';
import { Draggable } from 'gsap/Draggable';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

// Import theme management
// import ThemeManager from './js/theme-manager';

// Import router and utilities
import { Router } from './utils/Router';
// import keyboardNav from './js/keyboard-navigation';

// Local debounce helper function
function debounce(fn, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      fn.apply(this, args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}
// import offlineDetection from './js/offline-detection';

// Import services
import { ApiService } from './services/ApiService';
import { AuthService } from './services/AuthService';
import { FlashMessageManager } from './utils/flashMessages';

// Register GSAP plugins
gsap.registerPlugin(Draggable, ScrollTrigger);
window.gsap = gsap;

// Enhanced Alpine.js components
Alpine.data('themeToggle', () => ({
  init() {
    this.updateTheme();
    document.addEventListener('themechange', () => this.updateTheme());
  },

  updateTheme() {
    if (window.themeManager) {
      this.$el.setAttribute('aria-pressed', window.themeManager.isDark);
    }
  },

  toggle() {
    if (window.themeManager) {
      window.themeManager.toggleTheme();
    }
  }
}));

Alpine.data('modal', (initialOpen = false) => ({
  open: initialOpen,

  show() {
    this.open = true;
    document.body.style.overflow = 'hidden';
    this.$nextTick(() => {
      this.$refs.firstInput?.focus();
    });
  },

  hide() {
    this.open = false;
    document.body.style.overflow = '';
  },

  toggle() {
    this.open ? this.hide() : this.show();
  },

  handleEscape(event) {
    if (event.key === 'Escape' && this.open) {
      this.hide();
    }
  }
}));

Alpine.data('dropdown', (initialOpen = false) => ({
  open: initialOpen,

  toggle() {
    this.open = !this.open;
  },

  close() {
    this.open = false;
  },

  handleClickAway() {
    this.open = false;
  }
}));

Alpine.data('toast', () => ({
  toasts: [],

  add(message, type = 'info', duration = 5000) {
    const id = Date.now();
    const toast = { id, message, type, duration };

    this.toasts.push(toast);

    if (duration > 0) {
      setTimeout(() => this.remove(id), duration);
    }

    return id;
  },

  remove(id) {
    this.toasts = this.toasts.filter(toast => toast.id !== id);
  },

  success(message, duration) {
    return this.add(message, 'success', duration);
  },

  error(message, duration) {
    return this.add(message, 'error', duration);
  },

  warning(message, duration) {
    return this.add(message, 'warning', duration);
  },

  info(message, duration) {
    return this.add(message, 'info', duration);
  }
}));

Alpine.data('search', () => ({
  query: '',
  results: [],
  loading: false,
  focused: false,

  init() {
    this.$watch('query', (value) => {
      this.debounceSearch(value);
    });
  },

  debounceSearch: debounce(function(query) {
    if (query.length >= 2) {
      this.performSearch.call(this, query);
    } else {
      this.results = [];
    }
  }, 300),

  async performSearch(query) {
    this.loading = true;
    try {
      // Get JWT token from auth store or localStorage
      const token = this.getAuthToken();
      const headers = {
        'Content-Type': 'application/json'
      };
      if (token) {
        headers.Authorization = `Bearer ${token}`;
      }

      const response = await fetch(`/api/v1/search/suggestions?q=${encodeURIComponent(query)}`, {
        headers
      });
      
      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          this.results = data.data?.results || data.data?.projects || data.data?.tasks || [];
        } else {
          // eslint-disable-next-line no-console
          console.error('Search failed:', data.message);
          this.results = [];
        }
      } else {
        // eslint-disable-next-line no-console
        console.error('Search request failed:', response.status);
        this.results = [];
      }
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error('Search failed:', error);
      this.results = [];
    } finally {
      this.loading = false;
    }
  },

  getAuthToken() {
    // Try to get token from auth store first, fallback to localStorage
    return this.authToken || localStorage.getItem('jwt_token') || null;
  },

  selectResult(result) {
    // Use SPA routing instead of direct navigation
    if (result.type) {
      let routePath;
      switch (result.type) {
        case 'project':
          routePath = `/projects/${result.id}`;
          break;
        case 'task':
          routePath = `/tasks/${result.id}`;
          break;
        case 'user':
          routePath = `/users/${result.id}`;
          break;
        case 'document':
          routePath = `/docs/${result.id}`;
          break;
        default:
          routePath = `/search/${encodeURIComponent(result.title)}`;
      }
      
      // Use router for SPA navigation
      if (window.router && typeof window.router.push === 'function') {
        window.router.push(routePath);
      } else {
        // Fallback to window.location for external URLs or if router not available
        if (result.url && (result.url.startsWith('http') || result.url.startsWith('//'))) {
          window.open(result.url, '_blank');
        } else {
          window.location.href = result.url || routePath;
        }
      }
    } else {
      // Handle legacy results without type
      window.location.href = result.url;
    }
  },

  clear() {
    this.query = '';
    this.results = [];
  }
}));

// Enhanced GSAP animations
const animations = {
  fadeIn: (element, duration = 0.3) => {
    return gsap.from(element, {
      opacity: 0,
      y: 20,
      duration,
      ease: 'power2.out'
    });
  },

  slideIn: (element, direction = 'left', duration = 0.4) => {
    const fromProps = { opacity: 0 };
    fromProps[direction === 'left' ? 'x' : 'y'] = direction === 'left' || direction === 'up' ? -100 : 100;

    return gsap.from(element, {
      ...fromProps,
      duration,
      ease: 'power3.out'
    });
  },

  staggerFadeIn: (elements, duration = 0.3, stagger = 0.1) => {
    return gsap.from(elements, {
      opacity: 0,
      y: 30,
      duration,
      stagger,
      ease: 'power2.out'
    });
  },

  scaleIn: (element, duration = 0.3) => {
    return gsap.from(element, {
      scale: 0.9,
      opacity: 0,
      duration,
      ease: 'back.out(1.7)'
    });
  }
};

window.animations = animations;

// Alpine.js stores
Alpine.store('theme', {
  get isDark() {
    return window.themeManager && window.themeManager.getCurrentTheme() === 'dark';
  },
  
  toggle() {
    if (window.themeManager) {
      window.themeManager.toggleTheme();
    }
  }
});

// Initialize Alpine.js
Alpine.start();

// Initialize Router
const router = new Router();
window.router = router;

// Add authentication middleware
router.beforeEach((to) => {
  const isAuthenticated = !!localStorage.getItem('specsrv-token');
  const publicRoutes = ['/login', '/register', '/404'];

  if (!isAuthenticated && !publicRoutes.includes(to)) {
    return '/login';
  }

  if (isAuthenticated && (to === '/login' || to === '/register')) {
    return '/dashboard';
  }
});

// Add page title updates
router.afterEach((to) => {
  // Update page title based on route
  const routeTitles = {
    '/dashboard': 'Dashboard',
    '/projects': 'Projects',
    '/kanban': 'Kanban Board',
    '/tasks': 'Tasks',
    '/profile': 'Profile',
    '/search': 'Search',
    '/login': 'Login',
    '/register': 'Register'
  };

  const title = routeTitles[to.path] || 'SpecSrv';
  document.title = `${title} - SpecSrv`;
});

// Enhanced initialization
document.addEventListener('DOMContentLoaded', function() {
  // eslint-disable-next-line no-console
  console.log('Enhanced SPA initialized with HTMX, Alpine.js, GSAP, Theme Management, and Router');
  
  // Initialize services
  const apiService = new ApiService();
  const authService = new AuthService(apiService);
  const notificationManager = new FlashMessageManager();
  
  // Create global app object
  window.app = {
    router: router,
    apiService: apiService,
    authService: authService,
    notificationManager: notificationManager
  };

  // Hide loading screen and show the app
  const loadingScreen = document.querySelector('#initial-loading');
  const appContainer = document.querySelector('#app');
  
  if (loadingScreen) {
    loadingScreen.style.display = 'none';
  }
  
  if (appContainer) {
    appContainer.classList.remove('hidden');
  }

  // Initialize animations for existing elements
  gsap.from('.card', {
    opacity: 0,
    y: 20,
    duration: 0.6,
    stagger: 0.1,
    ease: 'power2.out'
  });

  // Enhanced keyboard navigation
  document.addEventListener('keydown', (e) => {
    // Global keyboard shortcuts
    if (e.ctrlKey || e.metaKey) {
      switch (e.key) {
      case 'k':
        e.preventDefault();
        document.querySelector('[data-search-input]')?.focus();
        break;
      case '/':
        e.preventDefault();
        document.querySelector('[data-search-input]')?.focus();
        break;
      }
    }

    // Escape key handling
    if (e.key === 'Escape') {
      // Close any open dropdowns
      document.querySelectorAll('[data-dropdown-open="true"]').forEach(dropdown => {
        Alpine.$data(dropdown).open = false;
      });
    }
  });

  // Enhanced HTMX integration
  htmx.on('htmx:afterSwap', (evt) => {
    // Re-animate new content
    const newElements = evt.detail.target.querySelectorAll('.card, .kanban-card');
    if (newElements.length > 0) {
      animations.staggerFadeIn(newElements);
    }
  });

  // Initialize router after DOM is ready
  router.init();

  // Add router event handlers
  document.addEventListener('route:change', (event) => {
    const { route, path, state } = event.detail;
    // eslint-disable-next-line no-console
    console.log('Route changed:', { route: route.component, path, state });

    // Update navigation active states
    updateNavigationState(path);

    // Trigger page animations
    setTimeout(() => {
      const pageElements = document.querySelectorAll('#main-content .card, #main-content .kanban-card');
      if (pageElements.length > 0) {
        animations.staggerFadeIn(pageElements, 0.3, 0.1);
      }
    }, 100);
  });

  // Performance monitoring
  if (window.performance && window.performance.mark) {
    performance.mark('app-initialized');
    performance.measure('app-load-time', 'navigationStart', 'app-initialized');
  }
});

// Helper function to update navigation active states
function updateNavigationState(currentPath) {
  // Remove active class from all nav links
  document.querySelectorAll('.nav-link, [data-nav-link]').forEach(link => {
    link.classList.remove('active', 'bg-primary-700', 'text-white');
    link.classList.add('text-gray-300', 'hover:bg-gray-700', 'hover:text-white');
  });

  // Add active class to current nav link
  const currentLink = document.querySelector(`[href="${currentPath}"], [data-nav-path="${currentPath}"]`);
  if (currentLink) {
    currentLink.classList.add('active', 'bg-primary-700', 'text-white');
    currentLink.classList.remove('text-gray-300', 'hover:bg-gray-700', 'hover:text-white');
  }
}

// Add global navigation helper functions
window.navigateTo = (path) => {
  if (window.router) {
    window.router.navigate(path);
  }
};

window.goBack = () => {
  if (window.router) {
    window.router.back();
  }
};
