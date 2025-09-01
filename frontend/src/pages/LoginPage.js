import { BasePage } from './BasePage.js';

/**
 * Login Page for SpecSrv Frontend
 */
export class LoginPage extends BasePage {
  constructor(params, state) {
    super(params, state);
    this.isLoading = false;
    this.errors = {};
  }
  
  /**
   * Check if user can access this page
   * @returns {boolean}
   */
  canAccess() {
    // Allow access if not authenticated
    return !this.authService?.isUserAuthenticated();
  }
  
  /**
   * Get page title
   * @returns {string}
   */
  getTitle() {
    return 'Login - SpecSrv';
  }
  
  /**
   * Create the login page element
   * @returns {HTMLElement}
   */
  createElement() {
    const container = document.createElement('div');
    container.className = 'min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8';
    
    container.innerHTML = `
      <div class="max-w-md w-full space-y-8">
        <div>
          <div class="mx-auto h-12 w-12 flex items-center justify-center">
            <span class="text-4xl">📋</span>
          </div>
          <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
            Sign in to SpecSrv
          </h2>
          <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
            Or
            <a href="/register" class="font-medium text-primary-600 hover:text-primary-500">
              create a new account
            </a>
          </p>
        </div>
        
        <form x-data="loginForm()" @submit.prevent="handleSubmit" class="mt-8 space-y-6">
          <div class="rounded-md shadow-sm -space-y-px">
            <div>
              <label for="email-address" class="sr-only">Email address</label>
              <input 
                id="email-address" 
                name="email" 
                type="email" 
                autocomplete="email" 
                required 
                x-model="form.email"
                class="form-input relative block w-full rounded-t-md border-0 py-1.5 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:z-10 sm:text-sm sm:leading-6" 
                :class="{ 'form-input-error': errors.email }"
                placeholder="Email address"
              />
              <div x-show="errors.email" class="form-error" x-text="errors.email"></div>
            </div>
            <div>
              <label for="password" class="sr-only">Password</label>
              <input 
                id="password" 
                name="password" 
                type="password" 
                autocomplete="current-password" 
                required 
                x-model="form.password"
                class="form-input relative block w-full rounded-b-md border-0 py-1.5 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:z-10 sm:text-sm sm:leading-6"
                :class="{ 'form-input-error': errors.password }"
                placeholder="Password"
              />
              <div x-show="errors.password" class="form-error" x-text="errors.password"></div>
            </div>
          </div>

          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input 
                id="remember-me" 
                name="remember-me" 
                type="checkbox" 
                x-model="form.rememberMe"
                class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-600"
              />
              <label for="remember-me" class="ml-2 block text-sm text-gray-900 dark:text-white">
                Remember me
              </label>
            </div>

            <div class="text-sm">
              <a href="/forgot-password" class="font-medium text-primary-600 hover:text-primary-500">
                Forgot your password?
              </a>
            </div>
          </div>

          <div x-show="generalError" class="rounded-md bg-red-50 p-4">
            <div class="text-sm text-red-700" x-text="generalError"></div>
          </div>

          <div>
            <button 
              type="submit" 
              :disabled="isLoading"
              class="btn btn-primary w-full flex justify-center items-center"
              :class="{ 'opacity-50 cursor-not-allowed': isLoading }"
            >
              <div x-show="isLoading" class="loading-spinner mr-2"></div>
              <span x-text="isLoading ? 'Signing in...' : 'Sign in'"></span>
            </button>
          </div>
        </form>
      </div>
    `;
    
    return container;
  }
  
  /**
   * Initialize components after rendering
   */
  async initializeComponents() {
    await super.initializeComponents();
    
    // Add Alpine.js data for the login form
    if (window.Alpine) {
      window.Alpine.data('loginForm', () => ({
        form: {
          email: '',
          password: '',
          rememberMe: false,
        },
        isLoading: false,
        errors: {},
        generalError: '',
        
        async handleSubmit() {
          this.clearErrors();
          
          if (!this.validateForm()) {
            return;
          }
          
          this.isLoading = true;
          
          try {
            await window.app.authService.login(
              this.form.email,
              this.form.password,
              this.form.rememberMe
            );
            
            // Redirect to dashboard on success
            window.app.router.navigate('/dashboard', { replace: true });
            
          } catch (error) {
            console.error('Login error:', error);
            
            if (error.status === 401) {
              this.generalError = 'Invalid email or password';
            } else if (error.status === 429) {
              this.generalError = 'Too many login attempts. Please try again later.';
            } else {
              this.generalError = error.message || 'Login failed. Please try again.';
            }
          } finally {
            this.isLoading = false;
          }
        },
        
        validateForm() {
          let isValid = true;
          
          if (!this.form.email) {
            this.errors.email = 'Email is required';
            isValid = false;
          } else if (!this.isValidEmail(this.form.email)) {
            this.errors.email = 'Please enter a valid email address';
            isValid = false;
          }
          
          if (!this.form.password) {
            this.errors.password = 'Password is required';
            isValid = false;
          } else if (this.form.password.length < 6) {
            this.errors.password = 'Password must be at least 6 characters';
            isValid = false;
          }
          
          return isValid;
        },
        
        isValidEmail(email) {
          return /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/.test(email);
        },
        
        clearErrors() {
          this.errors = {};
          this.generalError = '';
        }
      }));
      
      // Re-initialize Alpine for this component
      window.Alpine.initTree(this.element);
    }
  }
  
  /**
   * Handle page-specific keyboard shortcuts
   * @param {KeyboardEvent} event - Keyboard event
   */
  handleKeyboard(event) {
    // Focus email field on 'e' key
    if (event.key === 'e' && !event.ctrlKey && !event.metaKey && event.target.tagName !== 'INPUT') {
      event.preventDefault();
      const emailInput = this.element.querySelector('#email-address');
      if (emailInput) {
        emailInput.focus();
      }
    }
  }
  
  /**
   * Load page data (not needed for login page)
   */
  async loadData() {
    // No data to load for login page
  }
  
  /**
   * Cleanup resources
   */
  cleanup() {
    // Remove any specific event listeners if added
  }
}

export default LoginPage;