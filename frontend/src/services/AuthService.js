/**
 * Authentication Service for SpecSrv Frontend
 * Handles user authentication, token management, and user state
 */
export class AuthService {
  constructor(apiService) {
    this.apiService = apiService;
    this.currentUser = null;
    this.isAuthenticated = false;
    this.tokenKey = 'specsrv-token';
    this.userKey = 'specsrv-user';
    this.refreshTokenKey = 'specsrv-refresh-token';

    // Event listeners
    this.listeners = {
      login: [],
      logout: [],
      userUpdate: [],
    };
  }

  /**
   * Initialize auth service
   */
  async init() {
    try {
      // Check if user is already logged in
      const token = this.getToken();
      const userData = this.getStoredUser();

      if (token && userData) {
        this.currentUser = userData;
        this.isAuthenticated = true;

        // Verify token is still valid
        try {
          const user = await this.getCurrentUserFromAPI();
          this.setUser(user);
        } catch (error) {
          // Token is invalid, clear auth state
          this.clearAuthData();
        }
      }
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error('Auth initialization failed:', error);
      this.clearAuthData();
    }
  }

  /**
   * Add event listener
   * @param {string} event
   * @param {Function} callback
   */
  addEventListener(event, callback) {
    if (this.listeners[event]) {
      this.listeners[event].push(callback);
    }
  }

  /**
   * Remove event listener
   * @param {string} event
   * @param {Function} callback
   */
  removeEventListener(event, callback) {
    if (this.listeners[event]) {
      this.listeners[event] = this.listeners[event].filter(cb => cb !== callback);
    }
  }

  /**
   * Emit event
   * @param {string} event
   * @param {*} data
   */
  emit(event, data) {
    if (this.listeners[event]) {
      this.listeners[event].forEach(callback => callback(data));
    }
  }

  /**
   * Login user
   * @param {string} email
   * @param {string} password
   * @param {boolean} rememberMe
   * @returns {Promise<Object>}
   */
  async login(email, password, rememberMe = false) {
    try {
      const response = await this.apiService.post('/auth/login', {
        email,
        password,
        remember_me: rememberMe,
      });

      if (response.token && response.user) {
        this.setToken(response.token);
        this.setUser(response.user);

        if (response.refresh_token) {
          this.setRefreshToken(response.refresh_token);
        }

        this.isAuthenticated = true;
        this.emit('login', response.user);

        // Update global auth store if using Alpine
        if (window.Alpine?.store?.('auth')?.setUser) {
          window.Alpine.store('auth').setUser(response.user);
        }

        return response;
      }

      throw new Error('Invalid login response');
    } catch (error) {
      this.clearAuthData();
      throw error;
    }
  }

  /**
   * Register user
   * @param {Object} userData
   * @returns {Promise<Object>}
   */
  async register(userData) {
    try {
      const response = await this.apiService.post('/auth/register', userData);

      if (response.token && response.user) {
        this.setToken(response.token);
        this.setUser(response.user);

        if (response.refresh_token) {
          this.setRefreshToken(response.refresh_token);
        }

        this.isAuthenticated = true;
        this.emit('login', response.user);

        // Update global auth store if using Alpine
        if (window.Alpine?.store?.('auth')?.setUser) {
          window.Alpine.store('auth').setUser(response.user);
        }

        return response;
      }

      throw new Error('Invalid registration response');
    } catch (error) {
      this.clearAuthData();
      throw error;
    }
  }

  /**
   * Logout user
   * @returns {Promise<void>}
   */
  async logout() {
    try {
      // Call logout endpoint if we have a token
      if (this.getToken()) {
        try {
          await this.apiService.post('/auth/logout');
        } catch (error) {
          // Ignore errors on logout endpoint
          // eslint-disable-next-line no-console
          console.warn('Logout endpoint failed:', error);
        }
      }
    } finally {
      // Always clear local auth data
      this.clearAuthData();
      this.emit('logout');

      // Update global auth store if using Alpine
      if (window.Alpine?.store?.('auth')?.clear) {
        window.Alpine.store('auth').clear();
      }

      // Redirect to login page
      if (window.location.pathname !== '/login') {
        window.location.href = '/login';
      }
    }
  }

  /**
   * Refresh authentication token
   * @returns {Promise<Object>}
   */
  async refreshToken() {
    const refreshToken = this.getRefreshToken();
    if (!refreshToken) {
      throw new Error('No refresh token available');
    }

    try {
      const response = await this.apiService.post('/auth/refresh', {
        refresh_token: refreshToken,
      });

      if (response.token) {
        this.setToken(response.token);

        if (response.refresh_token) {
          this.setRefreshToken(response.refresh_token);
        }

        if (response.user) {
          this.setUser(response.user);
        }

        return response;
      }

      throw new Error('Invalid refresh response');
    } catch (error) {
      // Refresh failed, clear auth data and redirect to login
      this.clearAuthData();
      throw error;
    }
  }

  /**
   * Get current user from API
   * @returns {Promise<Object>}
   */
  async getCurrentUserFromAPI() {
    return this.apiService.get('/auth/me');
  }

  /**
   * Update user profile
   * @param {Object} userData
   * @returns {Promise<Object>}
   */
  async updateProfile(userData) {
    const response = await this.apiService.put('/auth/profile', userData);

    if (response.user) {
      this.setUser(response.user);
      this.emit('userUpdate', response.user);

      // Update global auth store if using Alpine
      if (window.Alpine?.store?.('auth')?.setUser) {
        window.Alpine.store('auth').setUser(response.user);
      }
    }

    return response;
  }

  /**
   * Change password
   * @param {Object} passwordData
   * @returns {Promise<Object>}
   */
  async changePassword(passwordData) {
    // Handle both object and individual params
    const data = typeof passwordData === 'object' && passwordData.currentPassword
      ? {
        current_password: passwordData.currentPassword,
        new_password: passwordData.newPassword,
      }
      : passwordData;

    return this.apiService.post('/auth/change-password', data);
  }

  /**
   * Request password reset
   * @param {string} email
   * @returns {Promise<Object>}
   */
  async requestPasswordReset(email) {
    return this.apiService.post('/auth/forgot-password', { email });
  }

  /**
   * Reset password with token
   * @param {string} token
   * @param {string} password
   * @returns {Promise<Object>}
   */
  async resetPassword(token, password) {
    return this.apiService.post('/auth/reset-password', {
      token,
      password,
    });
  }

  /**
   * Get current user
   * @returns {Object|null}
   */
  getCurrentUser() {
    return this.currentUser;
  }

  /**
   * Check if user is authenticated
   * @returns {boolean}
   */
  isUserAuthenticated() {
    return this.isAuthenticated && !!this.getToken();
  }

  /**
   * Alias for isUserAuthenticated
   * @returns {boolean}
   */
  isLoggedIn() {
    return this.isUserAuthenticated();
  }

  /**
   * Trigger event (alias for emit)
   * @param {string} event
   * @param {*} data
   */
  triggerEvent(event, data) {
    this.emit(event, data);
  }

  /**
   * Get authentication token
   * @returns {string|null}
   */
  getToken() {
    return localStorage.getItem(this.tokenKey);
  }

  /**
   * Set authentication token
   * @param {string} token
   */
  setToken(token) {
    localStorage.setItem(this.tokenKey, token);
  }

  /**
   * Remove authentication token
   */
  removeToken() {
    localStorage.removeItem(this.tokenKey);
  }

  /**
   * Get refresh token
   * @returns {string|null}
   */
  getRefreshToken() {
    return localStorage.getItem(this.refreshTokenKey);
  }

  /**
   * Set refresh token
   * @param {string} token
   */
  setRefreshToken(token) {
    localStorage.setItem(this.refreshTokenKey, token);
  }

  /**
   * Get stored user data
   * @returns {Object|null}
   */
  getStoredUser() {
    const userData = localStorage.getItem(this.userKey);
    if (!userData) {return null;}

    try {
      return JSON.parse(userData);
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error('Failed to parse stored user data:', error);
      return null;
    }
  }

  /**
   * Set user data
   * @param {Object} user
   */
  setUser(user) {
    this.currentUser = user;
    localStorage.setItem(this.userKey, JSON.stringify(user));
  }

  /**
   * Clear all authentication data
   */
  clearAuthData() {
    this.currentUser = null;
    this.isAuthenticated = false;
    localStorage.removeItem(this.tokenKey);
    localStorage.removeItem(this.userKey);
    localStorage.removeItem(this.refreshTokenKey);
  }

  /**
   * Check if user has permission
   * @param {string} permission
   * @returns {boolean}
   */
  hasPermission(permission) {
    if (!this.currentUser || !this.currentUser.roles) {
      return false;
    }

    return this.currentUser.roles.some(role =>
      role.permissions?.includes(permission)
    );
  }

  /**
   * Check if user has role
   * @param {string} role
   * @returns {boolean}
   */
  hasRole(role) {
    if (!this.currentUser || !this.currentUser.roles) {
      return false;
    }

    return this.currentUser.roles.some(r => r.name === role);
  }

  /**
   * Get user avatar URL
   * @param {Object} user
   * @returns {string}
   */
  getAvatarUrl(user = null) {
    const targetUser = user || this.currentUser;

    if (targetUser?.avatar) {
      return targetUser.avatar;
    }

    // Generate avatar based on initials
    const name = targetUser?.name || targetUser?.email || 'User';
    const initials = name.split(' ')
      .map(word => word[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);

    // Generate a simple SVG avatar
    const svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><rect width="40" height="40" fill="#3B82F6"/><text x="50%" y="50%" dy="0.1em" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" fill="white">' + initials + '</text></svg>';
    return 'data:image/svg+xml,' + encodeURIComponent(svgContent);
  }
}