import ApiService from './ApiService.js';

/**
 * Learning Service - Handles AI agent learning and knowledge operations
 */
export class LearningService extends ApiService {
  constructor() {
    super();
    this.basePath = '/learning';
  }

  /**
     * Get learning entries
     * @param {Object} options - Query options
     */
  async getLearningEntries(options = {}) {
    const params = new URLSearchParams(options);
    return this.get(`${this.basePath}?${params}`);
  }

  /**
     * Create a new learning entry
     * @param {Object} entryData - Learning entry data
     */
  async createLearningEntry(entryData) {
    return this.post(`${this.basePath}`, entryData);
  }

  /**
     * Get learning entry by ID
     * @param {number} entryId - Learning entry ID
     */
  async getLearningEntry(entryId) {
    return this.get(`${this.basePath}/${entryId}`);
  }

  /**
     * Update learning entry
     * @param {number} entryId - Learning entry ID
     * @param {Object} entryData - Updated entry data
     */
  async updateLearningEntry(entryId, entryData) {
    return this.put(`${this.basePath}/${entryId}`, entryData);
  }

  /**
     * Delete learning entry
     * @param {number} entryId - Learning entry ID
     */
  async deleteLearningEntry(entryId) {
    return this.delete(`${this.basePath}/${entryId}`);
  }

  /**
     * Search learning entries
     * @param {string} query - Search query
     * @param {Object} options - Search options
     */
  async searchLearningEntries(query, options = {}) {
    const params = new URLSearchParams({
      q: query,
      ...options
    });
    return this.get(`${this.basePath}/search?${params}`);
  }

  /**
     * Get learning categories
     */
  async getCategories() {
    return this.get(`${this.basePath}/categories`);
  }

  /**
     * Create learning category
     * @param {Object} categoryData - Category data
     */
  async createCategory(categoryData) {
    return this.post(`${this.basePath}/categories`, categoryData);
  }

  /**
     * Update learning category
     * @param {number} categoryId - Category ID
     * @param {Object} categoryData - Updated category data
     */
  async updateCategory(categoryId, categoryData) {
    return this.put(`${this.basePath}/categories/${categoryId}`, categoryData);
  }

  /**
     * Delete learning category
     * @param {number} categoryId - Category ID
     */
  async deleteCategory(categoryId) {
    return this.delete(`${this.basePath}/categories/${categoryId}`);
  }

  /**
     * Get learning entries by category
     * @param {number} categoryId - Category ID
     * @param {Object} options - Query options
     */
  async getEntriesByCategory(categoryId, options = {}) {
    const params = new URLSearchParams(options);
    return this.get(`${this.basePath}/categories/${categoryId}/entries?${params}`);
  }

  /**
     * Get learning tags
     */
  async getTags() {
    return this.get(`${this.basePath}/tags`);
  }

  /**
     * Get learning entries by tag
     * @param {string} tag - Tag name
     * @param {Object} options - Query options
     */
  async getEntriesByTag(tag, options = {}) {
    const params = new URLSearchParams(options);
    return this.get(`${this.basePath}/tags/${encodeURIComponent(tag)}/entries?${params}`);
  }

  /**
     * Add tags to learning entry
     * @param {number} entryId - Learning entry ID
     * @param {string[]} tags - Array of tag names
     */
  async addTags(entryId, tags) {
    return this.post(`${this.basePath}/${entryId}/tags`, { tags });
  }

  /**
     * Remove tags from learning entry
     * @param {number} entryId - Learning entry ID
     * @param {string[]} tags - Array of tag names to remove
     */
  async removeTags(entryId, tags) {
    return this.delete(`${this.basePath}/${entryId}/tags`, { tags });
  }

  /**
     * Get learning statistics
     * @param {Object} options - Statistics options
     */
  async getStats(options = {}) {
    const params = new URLSearchParams(options);
    return this.get(`${this.basePath}/stats?${params}`);
  }

  /**
     * Get learning entry history/versions
     * @param {number} entryId - Learning entry ID
     */
  async getEntryHistory(entryId) {
    return this.get(`${this.basePath}/${entryId}/history`);
  }

  /**
     * Restore learning entry version
     * @param {number} entryId - Learning entry ID
     * @param {number} versionId - Version ID to restore
     */
  async restoreEntryVersion(entryId, versionId) {
    return this.post(`${this.basePath}/${entryId}/history/${versionId}/restore`);
  }

  /**
     * Rate learning entry
     * @param {number} entryId - Learning entry ID
     * @param {number} rating - Rating (1-5)
     * @param {string} feedback - Optional feedback
     */
  async rateLearningEntry(entryId, rating, feedback = '') {
    return this.post(`${this.basePath}/${entryId}/rating`, {
      rating,
      feedback
    });
  }

  /**
     * Get learning entry ratings
     * @param {number} entryId - Learning entry ID
     */
  async getEntryRatings(entryId) {
    return this.get(`${this.basePath}/${entryId}/ratings`);
  }

  /**
     * Export learning data
     * @param {Object} options - Export options
     */
  async exportLearningData(options = {}) {
    const params = new URLSearchParams(options);
    return this.get(`${this.basePath}/export?${params}`);
  }

  /**
     * Import learning data
     * @param {File} file - Import file
     * @param {Object} options - Import options
     */
  async importLearningData(file, options = {}) {
    const formData = new FormData();
    formData.append('file', file);

    Object.keys(options).forEach(key => {
      formData.append(key, options[key]);
    });

    return this.request(`${this.basePath}/import`, {
      method: 'POST',
      body: formData,
      headers: {}
    });
  }

  /**
     * Generate learning insights
     * @param {Object} options - Insight generation options
     */
  async generateInsights(options = {}) {
    return this.post(`${this.basePath}/insights`, options);
  }

  /**
     * Get learning recommendations
     * @param {Object} options - Recommendation options
     */
  async getRecommendations(options = {}) {
    const params = new URLSearchParams(options);
    return this.get(`${this.basePath}/recommendations?${params}`);
  }

  /**
     * Mark learning entry as favorite
     * @param {number} entryId - Learning entry ID
     */
  async addToFavorites(entryId) {
    return this.post(`${this.basePath}/${entryId}/favorite`);
  }

  /**
     * Remove learning entry from favorites
     * @param {number} entryId - Learning entry ID
     */
  async removeFromFavorites(entryId) {
    return this.delete(`${this.basePath}/${entryId}/favorite`);
  }

  /**
     * Get favorite learning entries
     * @param {Object} options - Query options
     */
  async getFavorites(options = {}) {
    const params = new URLSearchParams(options);
    return this.get(`${this.basePath}/favorites?${params}`);
  }

  /**
     * Get recent learning entries
     * @param {number} limit - Number of entries to return
     */
  async getRecentEntries(limit = 10) {
    return this.get(`${this.basePath}/recent?limit=${limit}`);
  }

  /**
     * Get popular learning entries
     * @param {Object} options - Query options
     */
  async getPopularEntries(options = {}) {
    const params = new URLSearchParams(options);
    return this.get(`${this.basePath}/popular?${params}`);
  }

  /**
     * Create learning entry from project/task context
     * @param {Object} contextData - Context information
     */
  async createFromContext(contextData) {
    return this.post(`${this.basePath}/from-context`, contextData);
  }

  /**
     * Get learning entries related to a project
     * @param {number} projectId - Project ID
     */
  async getProjectRelatedEntries(projectId) {
    return this.get(`${this.basePath}/project/${projectId}`);
  }

  /**
     * Get learning entries related to a task
     * @param {number} taskId - Task ID
     */
  async getTaskRelatedEntries(taskId) {
    return this.get(`${this.basePath}/task/${taskId}`);
  }
}

export default LearningService;