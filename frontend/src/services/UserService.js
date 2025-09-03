/*jslint browser: true, devel: true */
import ApiService from "./ApiService.js";

/**
 * User Service - Handles user-related operations through the API
 */
export class UserService extends ApiService {
  constructor() {
    super();
    this.basePath = "/users";
  }

  /**
     * Get current user profile
     */
  async getCurrentUser() {
    return this.get(`${this.basePath}/me`);
  }

  /**
     * Update current user profile
     * @param {Object} userData - User data to update
     */
  async updateProfile(userData) {
    return this.put(`${this.basePath}/me`, userData);
  }

  /**
     * Change password
     * @param {Object} passwordData - Password change data
     */
  async changePassword(passwordData) {
    return this.put(`${this.basePath}/me/password`, passwordData);
  }

  /**
     * Get user by ID
     * @param {number} userId - User ID
     */
  async getUser(userId) {
    return this.get(`${this.basePath}/${userId}`);
  }

  /**
     * Get all users (admin only)
     * @param {Object} options - Query options
     */
  async getUsers(options = {}) {
    const params = new URLSearchParams(options);
    return this.get(`${this.basePath}?${params}`);
  }

  /**
     * Create new user (admin only)
     * @param {Object} userData - User data
     */
  async createUser(userData) {
    return this.post(`${this.basePath}`, userData);
  }

  /**
     * Update user (admin only)
     * @param {number} userId - User ID
     * @param {Object} userData - User data to update
     */
  async updateUser(userId, userData) {
    return this.put(`${this.basePath}/${userId}`, userData);
  }

  /**
     * Delete user (admin only)
     * @param {number} userId - User ID
     */
  async deleteUser(userId) {
    return this.delete(`${this.basePath}/${userId}`);
  }

  /**
     * Search users
     * @param {string} query - Search query
     * @param {Object} options - Search options
     */
  async searchUsers(query, options = {}) {
    const params = new URLSearchParams({
      q: query,
      ...options
    });
    return this.get(`${this.basePath}/search?${params}`);
  }

  /**
     * Get user"s activity log
     * @param {number} userId - User ID (optional, defaults to current user)
     * @param {Object} options - Query options
     */
  async getUserActivity(userId = null, options = {}) {
    const endpoint = userId
      ? `${this.basePath}/${userId}/activity`
      : `${this.basePath}/me/activity`;

    const params = new URLSearchParams(options);
    return this.get(`${endpoint}?${params}`);
  }

  /**
     * Get user"s projects
     * @param {number} userId - User ID (optional, defaults to current user)
     */
  async getUserProjects(userId = null) {
    const endpoint = userId
      ? `${this.basePath}/${userId}/projects`
      : `${this.basePath}/me/projects`;

    return this.get(endpoint);
  }

  /**
     * Get user"s tasks
     * @param {number} userId - User ID (optional, defaults to current user)
     * @param {Object} options - Query options
     */
  async getUserTasks(userId = null, options = {}) {
    const endpoint = userId
      ? `${this.basePath}/${userId}/tasks`
      : `${this.basePath}/me/tasks`;

    const params = new URLSearchParams(options);
    return this.get(`${endpoint}?${params}`);
  }

  /**
     * Update user preferences
     * @param {Object} preferences - User preferences
     */
  async updatePreferences(preferences) {
    return this.put(`${this.basePath}/me/preferences`, preferences);
  }

  /**
     * Get user preferences
     */
  async getPreferences() {
    return this.get(`${this.basePath}/me/preferences`);
  }

  /**
     * Update notification settings
     * @param {Object} settings - Notification settings
     */
  async updateNotificationSettings(settings) {
    return this.put(`${this.basePath}/me/notifications`, settings);
  }

  /**
     * Get notification settings
     */
  async getNotificationSettings() {
    return this.get(`${this.basePath}/me/notifications`);
  }

  /**
     * Upload user avatar
     * @param {File} file - Avatar image file
     */
  async uploadAvatar(file) {
    const formData = new FormData();
    formData.append("avatar", file);

    return this.request(`${this.basePath}/me/avatar`, {
      method: "POST",
      body: formData,
      headers: {}
    });
  }

  /**
     * Delete user avatar
     */
  async deleteAvatar() {
    return this.delete(`${this.basePath}/me/avatar`);
  }

  /**
     * Get user statistics
     * @param {number} userId - User ID (optional, defaults to current user)
     */
  async getUserStats(userId = null) {
    const endpoint = userId
      ? `${this.basePath}/${userId}/stats`
      : `${this.basePath}/me/stats`;

    return this.get(endpoint);
  }

  /**
     * Request password reset
     * @param {string} email - User email
     */
  async requestPasswordReset(email) {
    return this.post(`${this.basePath}/password-reset`, { email });
  }

  /**
     * Reset password with token
     * @param {Object} resetData - Password reset data (token, password)
     */
  async resetPassword(resetData) {
    return this.post(`${this.basePath}/password-reset/confirm`, resetData);
  }

  /**
     * Verify email address
     * @param {string} token - Verification token
     */
  async verifyEmail(token) {
    return this.post(`${this.basePath}/verify-email`, { token });
  }

  /**
     * Resend email verification
     */
  async resendEmailVerification() {
    return this.post(`${this.basePath}/me/verify-email`);
  }

  /**
     * Enable two-factor authentication
     * @param {Object} twoFactorData - 2FA setup data
     */
  async enableTwoFactor(twoFactorData) {
    return this.post(`${this.basePath}/me/2fa/enable`, twoFactorData);
  }

  /**
     * Disable two-factor authentication
     * @param {Object} data - Confirmation data
     */
  async disableTwoFactor(data) {
    return this.post(`${this.basePath}/me/2fa/disable`, data);
  }

  /**
     * Generate 2FA backup codes
     */
  async generateBackupCodes() {
    return this.post(`${this.basePath}/me/2fa/backup-codes`);
  }

  /**
     * Get user sessions
     */
  async getSessions() {
    return this.get(`${this.basePath}/me/sessions`);
  }

  /**
     * Revoke user session
     * @param {string} sessionId - Session ID
     */
  async revokeSession(sessionId) {
    return this.delete(`${this.basePath}/me/sessions/${sessionId}`);
  }

  /**
     * Revoke all other sessions
     */
  async revokeAllSessions() {
    return this.post(`${this.basePath}/me/sessions/revoke-all`);
  }

  /**
     * Export user data
     */
  async exportData() {
    return this.get(`${this.basePath}/me/export`);
  }

  /**
     * Delete user account
     * @param {Object} confirmationData - Account deletion confirmation
     */
  async deleteAccount(confirmationData) {
    return this.post(`${this.basePath}/me/delete`, confirmationData);
  }
}

export default UserService;