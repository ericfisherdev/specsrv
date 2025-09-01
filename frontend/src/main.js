// Main entry point for SpecSrv Frontend Application
import './styles/main.css';

// Import core libraries
import Alpine from 'alpinejs';
import { gsap } from 'gsap';
import 'htmx.org';

// Import application modules
import { ApiService } from './services/ApiService.js';
import { AuthService } from './services/AuthService.js';
import { Router } from './utils/Router.js';
import { ThemeManager } from './utils/ThemeManager.js';
import { NotificationManager } from './utils/NotificationManager.js';

// Initialize application
class App {
  constructor() {
    this.apiService = new ApiService();
    this.authService = new AuthService(this.apiService);
    this.router = new Router();
    this.themeManager = new ThemeManager();
    this.notificationManager = new NotificationManager();
    
    this.isInitialized = false;
  }
  
  async init() {
    try {
      console.log('Initializing SpecSrv Frontend...');
      
      // Initialize theme before anything else
      this.themeManager.init();
      
      // Initialize HTMX
      this.initializeHTMX();
      
      // Initialize AlpineJS
      this.initializeAlpine();
      
      // Initialize authentication
      await this.authService.init();
      
      // Initialize routing
      this.router.init();
      
      // Hide loading screen and show app
      this.hideLoadingScreen();
      
      // Mark as initialized
      this.isInitialized = true;
      
      console.log('SpecSrv Frontend initialized successfully');
      
      // Dispatch application ready event
      window.dispatchEvent(new CustomEvent('app:ready'));
      
    } catch (error) {
      console.error('Failed to initialize application:', error);
      this.showError('Failed to initialize application. Please refresh the page.');
    }
  }
  
  initializeHTMX() {
    // Configure HTMX defaults
    htmx.config.defaultSwapStyle = 'innerHTML';
    htmx.config.defaultSwapDelay = 0;
    htmx.config.defaultSettleDelay = 20;
    
    // Add global HTMX event listeners
    document.body.addEventListener('htmx:configRequest', (event) => {
      // Add authentication headers
      const token = this.authService.getToken();
      if (token) {
        event.detail.headers['Authorization'] = `Bearer ${token}`;
      }
      
      // Add CSRF protection
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
      if (csrfToken) {
        event.detail.headers['X-CSRF-Token'] = csrfToken;
      }
    });
    
    document.body.addEventListener('htmx:responseError', (event) => {
      if (event.detail.xhr.status === 401) {
        this.authService.logout();
        this.router.navigate('/login');
      } else {
        this.notificationManager.error('Request failed. Please try again.');
      }
    });
  }
  
  initializeAlpine() {
    // Global Alpine data
    Alpine.data('app', () => ({
      isLoading: false,
      user: this.authService.getCurrentUser(),
      theme: this.themeManager.getCurrentTheme(),
      
      // Methods
      toggleTheme() {
        this.theme = this.themeManager.toggle();
      },
      
      async logout() {
        await this.authService.logout();
        this.router.navigate('/login');
      }
    }));
    
    // Global Alpine stores
    Alpine.store('auth', {
      user: null,
      isAuthenticated: false,
      
      setUser(user) {
        this.user = user;
        this.isAuthenticated = !!user;
      },
      
      clear() {
        this.user = null;
        this.isAuthenticated = false;
      }
    });
    
    Alpine.store('notifications', {
      items: [],
      
      add(notification) {
        this.items.push({
          id: Date.now(),
          ...notification
        });
      },
      
      remove(id) {
        this.items = this.items.filter(item => item.id !== id);
      },
      
      clear() {
        this.items = [];
      }
    });
    
    // Start Alpine
    Alpine.start();
  }
  
  hideLoadingScreen() {
    const loadingScreen = document.getElementById('initial-loading');
    const app = document.getElementById('app');
    
    if (loadingScreen && app) {
      // Animate out loading screen
      gsap.to(loadingScreen, {
        opacity: 0,
        duration: 0.3,
        onComplete: () => {
          loadingScreen.style.display = 'none';
          app.classList.remove('hidden');
          
          // Animate in app
          gsap.fromTo(app, 
            { opacity: 0, y: 20 },
            { opacity: 1, y: 0, duration: 0.4 }
          );
        }
      });
    }
  }
  
  showError(message) {
    const loadingScreen = document.getElementById('initial-loading');
    if (loadingScreen) {
      loadingScreen.innerHTML = `
        <div class="flex flex-col items-center space-y-4 text-center p-8">
          <div class="text-red-500">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
          <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Application Error</h2>
          <p class="text-gray-600 dark:text-gray-400 max-w-sm">${message}</p>
          <button onclick="window.location.reload()" class="btn btn-primary">
            Reload Page
          </button>
        </div>
      `;
    }
  }
}

// Initialize application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  window.app = new App();
  window.app.init();
});

// Global error handler
window.addEventListener('error', (event) => {
  console.error('Global error:', event.error);
  if (window.app?.notificationManager) {
    window.app.notificationManager.error('An unexpected error occurred.');
  }
});

// Global unhandled promise rejection handler
window.addEventListener('unhandledrejection', (event) => {
  console.error('Unhandled promise rejection:', event.reason);
  if (window.app?.notificationManager) {
    window.app.notificationManager.error('An unexpected error occurred.');
  }
  event.preventDefault();
});