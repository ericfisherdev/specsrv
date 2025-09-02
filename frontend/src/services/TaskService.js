/**
 * Task Service for SpecSrv Frontend
 * Handles all task-related API operations
 */
export class TaskService {
  constructor(apiService) {
    this.apiService = apiService;
  }
  
  /**
   * Get all tasks
   * @param {Object} params - Query parameters
   * @returns {Promise<Object>}
   */
  async getTasks(params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const endpoint = queryString ? `/tasks?${queryString}` : '/tasks';
    return this.apiService.get(endpoint);
  }
  
  /**
   * Get task by ID
   * @param {number} id - Task ID
   * @returns {Promise<Object>}
   */
  async getTask(id) {
    return this.apiService.get(`/tasks/${id}`);
  }
  
  /**
   * Create new task
   * @param {Object} taskData - Task data
   * @returns {Promise<Object>}
   */
  async createTask(taskData) {
    return this.apiService.post('/tasks', taskData);
  }
  
  /**
   * Update task
   * @param {number} id - Task ID
   * @param {Object} taskData - Updated task data
   * @returns {Promise<Object>}
   */
  async updateTask(id, taskData) {
    return this.apiService.put(`/tasks/${id}`, taskData);
  }
  
  /**
   * Patch task (partial update)
   * @param {number} id - Task ID
   * @param {Object} changes - Changes to apply
   * @returns {Promise<Object>}
   */
  async patchTask(id, changes) {
    return this.apiService.patch(`/tasks/${id}`, changes);
  }
  
  /**
   * Delete task
   * @param {number} id - Task ID
   * @returns {Promise<void>}
   */
  async deleteTask(id) {
    return this.apiService.delete(`/tasks/${id}`);
  }
  
  /**
   * Update task status
   * @param {number} id - Task ID
   * @param {string} status - New status
   * @returns {Promise<Object>}
   */
  async updateTaskStatus(id, status) {
    return this.patchTask(id, { status });
  }
  
  /**
   * Update task priority
   * @param {number} id - Task ID
   * @param {string} priority - New priority
   * @returns {Promise<Object>}
   */
  async updateTaskPriority(id, priority) {
    return this.patchTask(id, { priority });
  }
  
  /**
   * Assign task to user
   * @param {number} id - Task ID
   * @param {number} assigneeId - User ID to assign to
   * @returns {Promise<Object>}
   */
  async assignTask(id, assigneeId) {
    return this.patchTask(id, { assignee_id: assigneeId });
  }
  
  /**
   * Unassign task
   * @param {number} id - Task ID
   * @returns {Promise<Object>}
   */
  async unassignTask(id) {
    return this.patchTask(id, { assignee_id: null });
  }
  
  /**
   * Update task due date
   * @param {number} id - Task ID
   * @param {string} dueDate - New due date (ISO string)
   * @returns {Promise<Object>}
   */
  async updateTaskDueDate(id, dueDate) {
    return this.patchTask(id, { due_date: dueDate });
  }
  
  /**
   * Add task comment
   * @param {number} taskId - Task ID
   * @param {Object} commentData - Comment data
   * @returns {Promise<Object>}
   */
  async addTaskComment(taskId, commentData) {
    return this.apiService.post(`/tasks/${taskId}/comments`, commentData);
  }
  
  /**
   * Get task comments
   * @param {number} taskId - Task ID
   * @param {Object} params - Query parameters
   * @returns {Promise<Array>}
   */
  async getTaskComments(taskId, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const endpoint = queryString 
      ? `/tasks/${taskId}/comments?${queryString}` 
      : `/tasks/${taskId}/comments`;
    return this.apiService.get(endpoint);
  }
  
  /**
   * Update task comment
   * @param {number} taskId - Task ID
   * @param {number} commentId - Comment ID
   * @param {Object} commentData - Updated comment data
   * @returns {Promise<Object>}
   */
  async updateTaskComment(taskId, commentId, commentData) {
    return this.apiService.put(`/tasks/${taskId}/comments/${commentId}`, commentData);
  }
  
  /**
   * Delete task comment
   * @param {number} taskId - Task ID
   * @param {number} commentId - Comment ID
   * @returns {Promise<void>}
   */
  async deleteTaskComment(taskId, commentId) {
    return this.apiService.delete(`/tasks/${taskId}/comments/${commentId}`);
  }
  
  /**
   * Add task attachment
   * @param {number} taskId - Task ID
   * @param {FormData} formData - Attachment data
   * @returns {Promise<Object>}
   */
  async addTaskAttachment(taskId, formData) {
    return this.apiService.upload(`/tasks/${taskId}/attachments`, formData);
  }
  
  /**
   * Get task attachments
   * @param {number} taskId - Task ID
   * @returns {Promise<Array>}
   */
  async getTaskAttachments(taskId) {
    return this.apiService.get(`/tasks/${taskId}/attachments`);
  }
  
  /**
   * Delete task attachment
   * @param {number} taskId - Task ID
   * @param {number} attachmentId - Attachment ID
   * @returns {Promise<void>}
   */
  async deleteTaskAttachment(taskId, attachmentId) {
    return this.apiService.delete(`/tasks/${taskId}/attachments/${attachmentId}`);
  }
  
  /**
   * Get task activity log
   * @param {number} taskId - Task ID
   * @param {Object} params - Query parameters
   * @returns {Promise<Object>}
   */
  async getTaskActivity(taskId, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const endpoint = queryString 
      ? `/tasks/${taskId}/activity?${queryString}` 
      : `/tasks/${taskId}/activity`;
    return this.apiService.get(endpoint);
  }
  
  /**
   * Get subtasks
   * @param {number} parentTaskId - Parent task ID
   * @returns {Promise<Array>}
   */
  async getSubtasks(parentTaskId) {
    return this.apiService.get(`/tasks/${parentTaskId}/subtasks`);
  }
  
  /**
   * Create subtask
   * @param {number} parentTaskId - Parent task ID
   * @param {Object} taskData - Subtask data
   * @returns {Promise<Object>}
   */
  async createSubtask(parentTaskId, taskData) {
    return this.apiService.post(`/tasks/${parentTaskId}/subtasks`, taskData);
  }
  
  /**
   * Get tasks by project
   * @param {number} projectId - Project ID
   * @param {Object} params - Query parameters
   * @returns {Promise<Object>}
   */
  async getTasksByProject(projectId, params = {}) {
    const queryParams = { project_id: projectId, ...params };
    return this.getTasks(queryParams);
  }
  
  /**
   * Get tasks by assignee
   * @param {number} assigneeId - Assignee user ID
   * @param {Object} params - Query parameters
   * @returns {Promise<Object>}
   */
  async getTasksByAssignee(assigneeId, params = {}) {
    const queryParams = { assignee_id: assigneeId, ...params };
    return this.getTasks(queryParams);
  }
  
  /**
   * Get tasks by status
   * @param {string} status - Task status
   * @param {Object} params - Query parameters
   * @returns {Promise<Object>}
   */
  async getTasksByStatus(status, params = {}) {
    const queryParams = { status, ...params };
    return this.getTasks(queryParams);
  }
  
  /**
   * Get overdue tasks
   * @param {Object} params - Query parameters
   * @returns {Promise<Object>}
   */
  async getOverdueTasks(params = {}) {
    const queryParams = { overdue: true, ...params };
    return this.getTasks(queryParams);
  }
  
  /**
   * Get my tasks
   * @param {Object} params - Query parameters
   * @returns {Promise<Object>}
   */
  async getMyTasks(params = {}) {
    return this.apiService.get('/tasks/me?' + new URLSearchParams(params).toString());
  }
  
  /**
   * Bulk update tasks
   * @param {Array} taskIds - Array of task IDs
   * @param {Object} updates - Updates to apply
   * @returns {Promise<Object>}
   */
  async bulkUpdateTasks(taskIds, updates) {
    return this.apiService.post('/tasks/bulk-update', {
      task_ids: taskIds,
      updates,
    });
  }
  
  /**
   * Bulk delete tasks
   * @param {Array} taskIds - Array of task IDs
   * @returns {Promise<Object>}
   */
  async bulkDeleteTasks(taskIds) {
    return this.apiService.post('/tasks/bulk-delete', {
      task_ids: taskIds,
    });
  }
  
  /**
   * Search tasks
   * @param {string} query - Search query
   * @param {Object} filters - Search filters
   * @returns {Promise<Object>}
   */
  async searchTasks(query, filters = {}) {
    const params = { q: query, ...filters };
    const queryString = new URLSearchParams(params).toString();
    return this.apiService.get(`/tasks/search?${queryString}`);
  }
  
  /**
   * Get kanban board data
   * @param {Object} filters - Board filters
   * @returns {Promise<Object>}
   */
  async getKanbanBoard(filters = {}) {
    const queryString = new URLSearchParams(filters).toString();
    const endpoint = queryString ? `/kanban/boards?${queryString}` : '/kanban/boards';
    return this.apiService.get(endpoint);
  }
  
  /**
   * Update task position in kanban
   * @param {number} taskId - Task ID
   * @param {string} status - New status/column
   * @param {number} position - New position in column
   * @returns {Promise<Object>}
   */
  async updateTaskPosition(taskId, status, position) {
    return this.apiService.post(`/tasks/${taskId}/move`, {
      status,
      position,
    });
  }

  // Convenience methods for common operations

  /**
   * Mark task as complete
   * @param {number} id - Task ID
   * @returns {Promise<Object>}
   */
  async markComplete(id) {
    return this.updateTaskStatus(id, 'completed');
  }

  /**
   * Mark task as todo
   * @param {number} id - Task ID
   * @returns {Promise<Object>}
   */
  async markTodo(id) {
    return this.updateTaskStatus(id, 'todo');
  }

  /**
   * Update task priority
   * @param {number} id - Task ID
   * @param {string} priority - Priority level
   * @returns {Promise<Object>}
   */
  async updatePriority(id, priority) {
    return this.updateTaskPriority(id, priority);
  }

  /**
   * Set task priority to critical
   * @param {number} id - Task ID
   * @returns {Promise<Object>}
   */
  async setCritical(id) {
    return this.updateTaskPriority(id, 'critical');
  }

  /**
   * Get task files
   * @param {number} taskId - Task ID
   * @returns {Promise<Array>}
   */
  async getTaskFiles(taskId) {
    return this.getTaskAttachments(taskId);
  }

  /**
   * Upload file to task
   * @param {number} taskId - Task ID
   * @param {FormData} formData - File data
   * @returns {Promise<Object>}
   */
  async uploadTaskFile(taskId, formData) {
    return this.addTaskAttachment(taskId, formData);
  }

  /**
   * Delete task file
   * @param {number} taskId - Task ID
   * @param {number} fileId - File ID
   * @returns {Promise<void>}
   */
  async deleteTaskFile(taskId, fileId) {
    return this.deleteTaskAttachment(taskId, fileId);
  }
}