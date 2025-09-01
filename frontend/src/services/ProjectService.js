/**
 * Project Service for SpecSrv Frontend
 * Handles all project-related API operations
 */
export class ProjectService {
  constructor(apiService) {
    this.apiService = apiService;
  }
  
  /**
   * Get all projects
   * @param {Object} params - Query parameters
   * @returns {Promise<Object>}
   */
  async getProjects(params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const endpoint = queryString ? `/projects?${queryString}` : '/projects';
    return this.apiService.get(endpoint);
  }
  
  /**
   * Get project by ID
   * @param {number} id - Project ID
   * @returns {Promise<Object>}
   */
  async getProject(id) {
    return this.apiService.get(`/projects/${id}`);
  }
  
  /**
   * Create new project
   * @param {Object} projectData - Project data
   * @returns {Promise<Object>}
   */
  async createProject(projectData) {
    return this.apiService.post('/projects', projectData);
  }
  
  /**
   * Update project
   * @param {number} id - Project ID
   * @param {Object} projectData - Updated project data
   * @returns {Promise<Object>}
   */
  async updateProject(id, projectData) {
    return this.apiService.put(`/projects/${id}`, projectData);
  }
  
  /**
   * Delete project
   * @param {number} id - Project ID
   * @returns {Promise<void>}
   */
  async deleteProject(id) {
    return this.apiService.delete(`/projects/${id}`);
  }
  
  /**
   * Get project tasks
   * @param {number} projectId - Project ID
   * @param {Object} params - Query parameters
   * @returns {Promise<Object>}
   */
  async getProjectTasks(projectId, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const endpoint = queryString 
      ? `/projects/${projectId}/tasks?${queryString}` 
      : `/projects/${projectId}/tasks`;
    return this.apiService.get(endpoint);
  }
  
  /**
   * Get project members
   * @param {number} projectId - Project ID
   * @returns {Promise<Array>}
   */
  async getProjectMembers(projectId) {
    return this.apiService.get(`/projects/${projectId}/members`);
  }
  
  /**
   * Add project member
   * @param {number} projectId - Project ID
   * @param {Object} memberData - Member data
   * @returns {Promise<Object>}
   */
  async addProjectMember(projectId, memberData) {
    return this.apiService.post(`/projects/${projectId}/members`, memberData);
  }
  
  /**
   * Update project member
   * @param {number} projectId - Project ID
   * @param {number} memberId - Member ID
   * @param {Object} memberData - Updated member data
   * @returns {Promise<Object>}
   */
  async updateProjectMember(projectId, memberId, memberData) {
    return this.apiService.put(`/projects/${projectId}/members/${memberId}`, memberData);
  }
  
  /**
   * Remove project member
   * @param {number} projectId - Project ID
   * @param {number} memberId - Member ID
   * @returns {Promise<void>}
   */
  async removeProjectMember(projectId, memberId) {
    return this.apiService.delete(`/projects/${projectId}/members/${memberId}`);
  }
  
  /**
   * Get project files
   * @param {number} projectId - Project ID
   * @returns {Promise<Array>}
   */
  async getProjectFiles(projectId) {
    return this.apiService.get(`/projects/${projectId}/files`);
  }
  
  /**
   * Upload file to project
   * @param {number} projectId - Project ID
   * @param {FormData} formData - File data
   * @returns {Promise<Object>}
   */
  async uploadProjectFile(projectId, formData) {
    return this.apiService.upload(`/projects/${projectId}/files`, formData);
  }
  
  /**
   * Delete project file
   * @param {number} projectId - Project ID
   * @param {number} fileId - File ID
   * @returns {Promise<void>}
   */
  async deleteProjectFile(projectId, fileId) {
    return this.apiService.delete(`/projects/${projectId}/files/${fileId}`);
  }
  
  /**
   * Get project statistics
   * @param {number} projectId - Project ID
   * @returns {Promise<Object>}
   */
  async getProjectStats(projectId) {
    return this.apiService.get(`/projects/${projectId}/stats`);
  }
  
  /**
   * Archive project
   * @param {number} id - Project ID
   * @returns {Promise<Object>}
   */
  async archiveProject(id) {
    return this.apiService.post(`/projects/${id}/archive`);
  }
  
  /**
   * Restore archived project
   * @param {number} id - Project ID
   * @returns {Promise<Object>}
   */
  async restoreProject(id) {
    return this.apiService.post(`/projects/${id}/restore`);
  }
  
  /**
   * Duplicate project
   * @param {number} id - Project ID
   * @param {Object} options - Duplication options
   * @returns {Promise<Object>}
   */
  async duplicateProject(id, options = {}) {
    return this.apiService.post(`/projects/${id}/duplicate`, options);
  }
  
  /**
   * Get project activity log
   * @param {number} projectId - Project ID
   * @param {Object} params - Query parameters
   * @returns {Promise<Object>}
   */
  async getProjectActivity(projectId, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const endpoint = queryString 
      ? `/projects/${projectId}/activity?${queryString}` 
      : `/projects/${projectId}/activity`;
    return this.apiService.get(endpoint);
  }
  
  /**
   * Export project data
   * @param {number} projectId - Project ID
   * @param {string} format - Export format (json, csv, xlsx)
   * @returns {Promise<void>}
   */
  async exportProject(projectId, format = 'json') {
    const filename = `project-${projectId}-${Date.now()}.${format}`;
    return this.apiService.download(`/projects/${projectId}/export?format=${format}`, filename);
  }
  
  /**
   * Search projects
   * @param {string} query - Search query
   * @param {Object} filters - Search filters
   * @returns {Promise<Object>}
   */
  async searchProjects(query, filters = {}) {
    const params = { q: query, ...filters };
    const queryString = new URLSearchParams(params).toString();
    return this.apiService.get(`/projects/search?${queryString}`);
  }
}