import { AuthService } from '../../src/services/AuthService.js';

describe('AuthService', () => {
  let authService;
  let mockApiService;

  beforeEach(() => {
    mockApiService = {
      get: jest.fn(),
      post: jest.fn(),
    };
    
    authService = new AuthService(mockApiService);
    
    // Clear localStorage
    localStorage.clear();
  });

  describe('constructor', () => {
    it('should initialize with default values', () => {
      expect(authService.apiService).toBe(mockApiService);
      expect(authService.currentUser).toBeNull();
      expect(authService.isAuthenticated).toBe(false);
      expect(authService.tokenKey).toBe('specsrv-token');
      expect(authService.userKey).toBe('specsrv-user');
      expect(authService.refreshTokenKey).toBe('specsrv-refresh-token');
    });

    it('should initialize event listeners', () => {
      expect(authService.listeners).toEqual({
        login: [],
        logout: [],
        userUpdate: [],
      });
    });
  });

  describe('addEventListener', () => {
    it('should add callback to valid event type', () => {
      const callback = jest.fn();
      authService.addEventListener('login', callback);
      
      expect(authService.listeners.login).toContain(callback);
    });

    it('should not add callback to invalid event type', () => {
      const callback = jest.fn();
      authService.addEventListener('invalid', callback);
      
      expect(authService.listeners.invalid).toBeUndefined();
    });
  });

  describe('removeEventListener', () => {
    it('should remove callback from event type', () => {
      const callback = jest.fn();
      authService.addEventListener('login', callback);
      authService.removeEventListener('login', callback);
      
      expect(authService.listeners.login).not.toContain(callback);
    });
  });

  describe('token management', () => {
    it('should get token from localStorage', () => {
      localStorage.setItem('specsrv-token', 'test-token');
      
      const token = authService.getToken();
      
      expect(token).toBe('test-token');
      expect(localStorage.getItem).toHaveBeenCalledWith('specsrv-token');
    });

    it('should set token in localStorage', () => {
      authService.setToken('new-token');
      
      expect(localStorage.setItem).toHaveBeenCalledWith('specsrv-token', 'new-token');
    });

    it('should remove token from localStorage', () => {
      authService.removeToken();
      
      expect(localStorage.removeItem).toHaveBeenCalledWith('specsrv-token');
    });
  });

  describe('user management', () => {
    it('should get stored user from localStorage', () => {
      const userData = { id: 1, email: 'test@example.com' };
      localStorage.getItem.mockReturnValue(JSON.stringify(userData));
      
      const user = authService.getStoredUser();
      
      expect(user).toEqual(userData);
      expect(localStorage.getItem).toHaveBeenCalledWith('specsrv-user');
    });

    it('should return null for invalid user data', () => {
      localStorage.getItem.mockReturnValue('invalid-json');
      
      const user = authService.getStoredUser();
      
      expect(user).toBeNull();
    });

    it('should set user and update authentication state', () => {
      const userData = { id: 1, email: 'test@example.com' };
      const mockTriggerEvent = jest.spyOn(authService, 'triggerEvent');
      
      authService.setUser(userData);
      
      expect(authService.currentUser).toEqual(userData);
      expect(authService.isAuthenticated).toBe(true);
      expect(localStorage.setItem).toHaveBeenCalledWith('specsrv-user', JSON.stringify(userData));
      expect(mockTriggerEvent).toHaveBeenCalledWith('userUpdate', userData);
    });
  });

  describe('login', () => {
    it('should login successfully', async () => {
      const credentials = {
        email: 'test@example.com',
        password: 'password123'
      };
      
      const loginResponse = {
        token: 'auth-token',
        user: { id: 1, email: 'test@example.com' }
      };
      
      mockApiService.post.mockResolvedValue({ data: loginResponse });
      const mockTriggerEvent = jest.spyOn(authService, 'triggerEvent');
      
      const result = await authService.login(credentials);
      
      expect(mockApiService.post).toHaveBeenCalledWith('/auth/login', credentials);
      expect(authService.currentUser).toEqual(loginResponse.user);
      expect(authService.isAuthenticated).toBe(true);
      expect(localStorage.setItem).toHaveBeenCalledWith('specsrv-token', 'auth-token');
      expect(localStorage.setItem).toHaveBeenCalledWith('specsrv-user', JSON.stringify(loginResponse.user));
      expect(mockTriggerEvent).toHaveBeenCalledWith('login', loginResponse.user);
      expect(result).toEqual(loginResponse);
    });

    it('should handle login failure', async () => {
      const credentials = {
        email: 'test@example.com',
        password: 'wrong-password'
      };
      
      const error = new Error('Invalid credentials');
      mockApiService.post.mockRejectedValue(error);
      
      await expect(authService.login(credentials)).rejects.toThrow('Invalid credentials');
      expect(authService.isAuthenticated).toBe(false);
      expect(authService.currentUser).toBeNull();
    });
  });

  describe('register', () => {
    it('should register successfully', async () => {
      const userData = {
        email: 'test@example.com',
        password: 'password123',
        name: 'Test User'
      };
      
      const registerResponse = {
        token: 'auth-token',
        user: { id: 1, email: 'test@example.com', name: 'Test User' }
      };
      
      mockApiService.post.mockResolvedValue({ data: registerResponse });
      const mockTriggerEvent = jest.spyOn(authService, 'triggerEvent');
      
      const result = await authService.register(userData);
      
      expect(mockApiService.post).toHaveBeenCalledWith('/auth/register', userData);
      expect(authService.currentUser).toEqual(registerResponse.user);
      expect(authService.isAuthenticated).toBe(true);
      expect(mockTriggerEvent).toHaveBeenCalledWith('login', registerResponse.user);
      expect(result).toEqual(registerResponse);
    });

    it('should handle registration failure', async () => {
      const userData = {
        email: 'existing@example.com',
        password: 'password123'
      };
      
      const error = new Error('User already exists');
      mockApiService.post.mockRejectedValue(error);
      
      await expect(authService.register(userData)).rejects.toThrow('User already exists');
      expect(authService.isAuthenticated).toBe(false);
    });
  });

  describe('logout', () => {
    it('should logout and clear auth data', async () => {
      // Set up authenticated state
      authService.currentUser = { id: 1, email: 'test@example.com' };
      authService.isAuthenticated = true;
      
      mockApiService.post.mockResolvedValue({});
      const mockTriggerEvent = jest.spyOn(authService, 'triggerEvent');
      
      await authService.logout();
      
      expect(mockApiService.post).toHaveBeenCalledWith('/auth/logout');
      expect(authService.currentUser).toBeNull();
      expect(authService.isAuthenticated).toBe(false);
      expect(localStorage.removeItem).toHaveBeenCalledWith('specsrv-token');
      expect(localStorage.removeItem).toHaveBeenCalledWith('specsrv-user');
      expect(localStorage.removeItem).toHaveBeenCalledWith('specsrv-refresh-token');
      expect(mockTriggerEvent).toHaveBeenCalledWith('logout');
    });

    it('should clear auth data even if API call fails', async () => {
      authService.currentUser = { id: 1, email: 'test@example.com' };
      authService.isAuthenticated = true;
      
      mockApiService.post.mockRejectedValue(new Error('Network error'));
      
      await authService.logout();
      
      expect(authService.currentUser).toBeNull();
      expect(authService.isAuthenticated).toBe(false);
    });
  });

  describe('refreshToken', () => {
    it('should refresh token successfully', async () => {
      const newTokenResponse = {
        token: 'new-token',
        user: { id: 1, email: 'test@example.com' }
      };
      
      mockApiService.post.mockResolvedValue({ data: newTokenResponse });
      
      const result = await authService.refreshToken();
      
      expect(mockApiService.post).toHaveBeenCalledWith('/auth/refresh');
      expect(localStorage.setItem).toHaveBeenCalledWith('specsrv-token', 'new-token');
      expect(result).toEqual(newTokenResponse);
    });

    it('should clear auth data if refresh fails', async () => {
      mockApiService.post.mockRejectedValue(new Error('Token expired'));
      
      await expect(authService.refreshToken()).rejects.toThrow('Token expired');
      expect(authService.currentUser).toBeNull();
      expect(authService.isAuthenticated).toBe(false);
    });
  });

  describe('getCurrentUser', () => {
    it('should return current user from API', async () => {
      const userData = { id: 1, email: 'test@example.com' };
      mockApiService.get.mockResolvedValue({ data: { user: userData } });
      
      const result = await authService.getCurrentUserFromAPI();
      
      expect(mockApiService.get).toHaveBeenCalledWith('/auth/me');
      expect(result).toEqual(userData);
    });

    it('should handle API error', async () => {
      mockApiService.get.mockRejectedValue(new Error('Unauthorized'));
      
      await expect(authService.getCurrentUserFromAPI()).rejects.toThrow('Unauthorized');
    });
  });

  describe('init', () => {
    it('should initialize with valid stored credentials', async () => {
      localStorage.getItem
        .mockReturnValueOnce('stored-token')  // getToken() call
        .mockReturnValueOnce(JSON.stringify({ id: 1, email: 'test@example.com' })); // getStoredUser() call
      
      const userData = { id: 1, email: 'test@example.com', name: 'Updated User' };
      mockApiService.get.mockResolvedValue({ data: { user: userData } });
      
      await authService.init();
      
      expect(authService.isAuthenticated).toBe(true);
      expect(authService.currentUser).toEqual(userData);
      expect(mockApiService.get).toHaveBeenCalledWith('/auth/me');
    });

    it('should clear auth data if token verification fails', async () => {
      localStorage.getItem
        .mockReturnValueOnce('invalid-token')
        .mockReturnValueOnce(JSON.stringify({ id: 1, email: 'test@example.com' }));
      
      mockApiService.get.mockRejectedValue(new Error('Invalid token'));
      
      await authService.init();
      
      expect(authService.isAuthenticated).toBe(false);
      expect(authService.currentUser).toBeNull();
      expect(localStorage.removeItem).toHaveBeenCalledWith('specsrv-token');
    });

    it('should handle missing stored data', async () => {
      localStorage.getItem.mockReturnValue(null);
      
      await authService.init();
      
      expect(authService.isAuthenticated).toBe(false);
      expect(authService.currentUser).toBeNull();
      expect(mockApiService.get).not.toHaveBeenCalled();
    });
  });

  describe('updateProfile', () => {
    it('should update user profile', async () => {
      const profileData = {
        name: 'Updated Name',
        email: 'updated@example.com'
      };
      
      const updatedUser = { id: 1, ...profileData };
      mockApiService.post.mockResolvedValue({ data: { user: updatedUser } });
      
      const result = await authService.updateProfile(profileData);
      
      expect(mockApiService.post).toHaveBeenCalledWith('/auth/profile', profileData);
      expect(authService.currentUser).toEqual(updatedUser);
      expect(result).toEqual(updatedUser);
    });
  });

  describe('changePassword', () => {
    it('should change password successfully', async () => {
      const passwordData = {
        currentPassword: 'old-password',
        newPassword: 'new-password'
      };
      
      mockApiService.post.mockResolvedValue({ success: true });
      
      const result = await authService.changePassword(passwordData);
      
      expect(mockApiService.post).toHaveBeenCalledWith('/auth/change-password', passwordData);
      expect(result).toEqual({ success: true });
    });
  });

  describe('event handling', () => {
    it('should trigger event with data', () => {
      const callback1 = jest.fn();
      const callback2 = jest.fn();
      const eventData = { test: 'data' };
      
      authService.addEventListener('login', callback1);
      authService.addEventListener('login', callback2);
      
      authService.triggerEvent('login', eventData);
      
      expect(callback1).toHaveBeenCalledWith(eventData);
      expect(callback2).toHaveBeenCalledWith(eventData);
    });

    it('should handle missing event listeners', () => {
      // Should not throw error
      expect(() => {
        authService.triggerEvent('nonexistent', {});
      }).not.toThrow();
    });
  });

  describe('authentication state checks', () => {
    it('should return true when authenticated', () => {
      authService.isAuthenticated = true;
      
      expect(authService.isLoggedIn()).toBe(true);
    });

    it('should return false when not authenticated', () => {
      authService.isAuthenticated = false;
      
      expect(authService.isLoggedIn()).toBe(false);
    });

    it('should return current user', () => {
      const userData = { id: 1, email: 'test@example.com' };
      authService.currentUser = userData;
      
      expect(authService.getCurrentUser()).toEqual(userData);
    });

    it('should return null when no current user', () => {
      authService.currentUser = null;
      
      expect(authService.getCurrentUser()).toBeNull();
    });
  });

  describe('clearAuthData', () => {
    it('should clear all authentication data', () => {
      authService.currentUser = { id: 1, email: 'test@example.com' };
      authService.isAuthenticated = true;
      
      authService.clearAuthData();
      
      expect(authService.currentUser).toBeNull();
      expect(authService.isAuthenticated).toBe(false);
      expect(localStorage.removeItem).toHaveBeenCalledWith('specsrv-token');
      expect(localStorage.removeItem).toHaveBeenCalledWith('specsrv-user');
      expect(localStorage.removeItem).toHaveBeenCalledWith('specsrv-refresh-token');
    });
  });
});