import { TaskService } from '../../src/services/TaskService.js';

describe('TaskService', () => {
  let taskService;
  let mockApiService;

  beforeEach(() => {
    mockApiService = {
      get: jest.fn(),
      post: jest.fn(),
      put: jest.fn(),
      patch: jest.fn(),
      delete: jest.fn(),
      upload: jest.fn(),
    };
    
    taskService = new TaskService(mockApiService);
  });

  describe('constructor', () => {
    it('should initialize with apiService', () => {
      expect(taskService.apiService).toBe(mockApiService);
    });
  });

  describe('getTasks', () => {
    it('should make GET request to /tasks', async () => {
      await taskService.getTasks();
      
      expect(mockApiService.get).toHaveBeenCalledWith('/tasks');
    });

    it('should include query parameters', async () => {
      const params = { 
        project: 123, 
        status: 'todo', 
        priority: 'high',
        page: 1,
        limit: 20 
      };
      
      await taskService.getTasks(params);
      
      expect(mockApiService.get).toHaveBeenCalledWith(
        '/tasks?project=123&status=todo&priority=high&page=1&limit=20'
      );
    });

    it('should handle empty params object', async () => {
      await taskService.getTasks({});
      
      expect(mockApiService.get).toHaveBeenCalledWith('/tasks');
    });
  });

  describe('getTask', () => {
    it('should make GET request to specific task endpoint', async () => {
      await taskService.getTask(456);
      
      expect(mockApiService.get).toHaveBeenCalledWith('/tasks/456');
    });

    it('should handle string ID', async () => {
      await taskService.getTask('456');
      
      expect(mockApiService.get).toHaveBeenCalledWith('/tasks/456');
    });
  });

  describe('createTask', () => {
    it('should make POST request with task data', async () => {
      const taskData = {
        title: 'New Task',
        description: 'Task description',
        projectId: 123,
        priority: 'medium',
        status: 'todo'
      };
      
      await taskService.createTask(taskData);
      
      expect(mockApiService.post).toHaveBeenCalledWith('/tasks', taskData);
    });

    it('should handle minimal task data', async () => {
      const taskData = {
        title: 'Minimal Task'
      };
      
      await taskService.createTask(taskData);
      
      expect(mockApiService.post).toHaveBeenCalledWith('/tasks', taskData);
    });
  });

  describe('updateTask', () => {
    it('should make PUT request with updated data', async () => {
      const taskData = {
        title: 'Updated Task',
        description: 'Updated description',
        status: 'in_progress'
      };
      
      await taskService.updateTask(456, taskData);
      
      expect(mockApiService.put).toHaveBeenCalledWith('/tasks/456', taskData);
    });
  });

  describe('deleteTask', () => {
    it('should make DELETE request to specific task', async () => {
      await taskService.deleteTask(456);
      
      expect(mockApiService.delete).toHaveBeenCalledWith('/tasks/456');
    });
  });

  describe('task status operations', () => {
    it('should update task status', async () => {
      await taskService.updateTaskStatus(456, 'completed');
      
      expect(mockApiService.patch).toHaveBeenCalledWith('/tasks/456', { 
        status: 'completed' 
      });
    });

    it('should mark task as complete', async () => {
      await taskService.markComplete(456);
      
      expect(mockApiService.patch).toHaveBeenCalledWith('/tasks/456', { 
        status: 'completed' 
      });
    });

    it('should mark task as todo', async () => {
      await taskService.markTodo(456);
      
      expect(mockApiService.patch).toHaveBeenCalledWith('/tasks/456', { 
        status: 'todo' 
      });
    });
  });

  describe('task priority operations', () => {
    it('should update task priority', async () => {
      await taskService.updatePriority(456, 'high');
      
      expect(mockApiService.patch).toHaveBeenCalledWith('/tasks/456', { 
        priority: 'high' 
      });
    });

    it('should set critical priority', async () => {
      await taskService.setCritical(456);
      
      expect(mockApiService.patch).toHaveBeenCalledWith('/tasks/456', { 
        priority: 'critical' 
      });
    });
  });

  describe('task files operations', () => {
    it('should get task files', async () => {
      await taskService.getTaskFiles(456);
      
      expect(mockApiService.get).toHaveBeenCalledWith('/tasks/456/files');
    });

    it('should upload file to task', async () => {
      const formData = new FormData();
      formData.append('file', 'test file');
      
      await taskService.uploadTaskFile(456, formData);
      
      expect(mockApiService.upload).toHaveBeenCalledWith('/tasks/456/files', formData);
    });

    it('should delete task file', async () => {
      await taskService.deleteTaskFile(456, 789);
      
      expect(mockApiService.delete).toHaveBeenCalledWith('/tasks/456/files/789');
    });
  });

  describe('task comments operations', () => {
    it('should get task comments', async () => {
      await taskService.getTaskComments(456);
      
      expect(mockApiService.get).toHaveBeenCalledWith('/tasks/456/comments');
    });

    it('should add task comment', async () => {
      const commentData = {
        content: 'This is a comment',
        mentions: ['@user123']
      };
      
      await taskService.addTaskComment(456, commentData);
      
      expect(mockApiService.post).toHaveBeenCalledWith('/tasks/456/comments', commentData);
    });

    it('should update task comment', async () => {
      const commentData = {
        content: 'Updated comment'
      };
      
      await taskService.updateTaskComment(456, 789, commentData);
      
      expect(mockApiService.put).toHaveBeenCalledWith('/tasks/456/comments/789', commentData);
    });

    it('should delete task comment', async () => {
      await taskService.deleteTaskComment(456, 789);
      
      expect(mockApiService.delete).toHaveBeenCalledWith('/tasks/456/comments/789');
    });
  });

  describe('task assignment operations', () => {
    it('should assign task to user', async () => {
      await taskService.assignTask(456, 123);
      
      expect(mockApiService.patch).toHaveBeenCalledWith('/tasks/456', { 
        assigneeId: 123 
      });
    });

    it('should unassign task', async () => {
      await taskService.unassignTask(456);
      
      expect(mockApiService.patch).toHaveBeenCalledWith('/tasks/456', { 
        assigneeId: null 
      });
    });
  });

  describe('task time tracking', () => {
    it('should log time to task', async () => {
      const timeData = {
        hours: 2.5,
        description: 'Working on feature',
        date: '2023-01-01'
      };
      
      await taskService.logTime(456, timeData);
      
      expect(mockApiService.post).toHaveBeenCalledWith('/tasks/456/time', timeData);
    });

    it('should get task time entries', async () => {
      await taskService.getTimeEntries(456);
      
      expect(mockApiService.get).toHaveBeenCalledWith('/tasks/456/time');
    });

    it('should update time entry', async () => {
      const timeData = {
        hours: 3.0,
        description: 'Updated time entry'
      };
      
      await taskService.updateTimeEntry(456, 789, timeData);
      
      expect(mockApiService.put).toHaveBeenCalledWith('/tasks/456/time/789', timeData);
    });

    it('should delete time entry', async () => {
      await taskService.deleteTimeEntry(456, 789);
      
      expect(mockApiService.delete).toHaveBeenCalledWith('/tasks/456/time/789');
    });
  });

  describe('bulk operations', () => {
    it('should bulk update tasks', async () => {
      const taskIds = [1, 2, 3];
      const updateData = {
        status: 'completed',
        priority: 'low'
      };
      
      await taskService.bulkUpdateTasks(taskIds, updateData);
      
      expect(mockApiService.patch).toHaveBeenCalledWith('/tasks/bulk', {
        taskIds,
        ...updateData
      });
    });

    it('should bulk delete tasks', async () => {
      const taskIds = [1, 2, 3];
      
      await taskService.bulkDeleteTasks(taskIds);
      
      expect(mockApiService.delete).toHaveBeenCalledWith('/tasks/bulk', {
        taskIds
      });
    });
  });

  describe('task search and filtering', () => {
    it('should search tasks', async () => {
      const searchParams = {
        query: 'search term',
        project: 123,
        status: 'todo',
        priority: 'high',
        assignee: 456
      };
      
      await taskService.searchTasks(searchParams);
      
      expect(mockApiService.get).toHaveBeenCalledWith(
        '/tasks/search?query=search+term&project=123&status=todo&priority=high&assignee=456'
      );
    });

    it('should get tasks by project', async () => {
      await taskService.getTasksByProject(123);
      
      expect(mockApiService.get).toHaveBeenCalledWith('/tasks?project=123');
    });

    it('should get tasks by status', async () => {
      await taskService.getTasksByStatus('todo');
      
      expect(mockApiService.get).toHaveBeenCalledWith('/tasks?status=todo');
    });

    it('should get tasks by assignee', async () => {
      await taskService.getTasksByAssignee(456);
      
      expect(mockApiService.get).toHaveBeenCalledWith('/tasks?assignee=456');
    });
  });

  describe('task dependencies', () => {
    it('should get task dependencies', async () => {
      await taskService.getTaskDependencies(456);
      
      expect(mockApiService.get).toHaveBeenCalledWith('/tasks/456/dependencies');
    });

    it('should add task dependency', async () => {
      await taskService.addDependency(456, 789);
      
      expect(mockApiService.post).toHaveBeenCalledWith('/tasks/456/dependencies', {
        dependsOnTaskId: 789
      });
    });

    it('should remove task dependency', async () => {
      await taskService.removeDependency(456, 789);
      
      expect(mockApiService.delete).toHaveBeenCalledWith('/tasks/456/dependencies/789');
    });
  });

  describe('error handling', () => {
    it('should propagate API service errors', async () => {
      const error = new Error('API Error');
      mockApiService.get.mockRejectedValue(error);
      
      await expect(taskService.getTasks()).rejects.toThrow('API Error');
    });

    it('should handle network errors', async () => {
      const networkError = new Error('Network Error');
      mockApiService.post.mockRejectedValue(networkError);
      
      await expect(taskService.createTask({})).rejects.toThrow('Network Error');
    });
  });

  describe('return values', () => {
    it('should return API service response', async () => {
      const mockResponse = { 
        id: 456, 
        title: 'Test Task',
        status: 'todo'
      };
      
      mockApiService.get.mockResolvedValue(mockResponse);
      
      const result = await taskService.getTask(456);
      
      expect(result).toEqual(mockResponse);
    });

    it('should handle void operations', async () => {
      mockApiService.delete.mockResolvedValue(undefined);
      
      const result = await taskService.deleteTask(456);
      
      expect(result).toBeUndefined();
    });
  });

  describe('parameter validation', () => {
    it('should handle undefined parameters gracefully', async () => {
      await taskService.getTasks(undefined);
      
      expect(mockApiService.get).toHaveBeenCalledWith('/tasks');
    });

    it('should handle null task data', async () => {
      await taskService.createTask(null);
      
      expect(mockApiService.post).toHaveBeenCalledWith('/tasks', null);
    });

    it('should handle empty arrays in bulk operations', async () => {
      await taskService.bulkUpdateTasks([], { status: 'completed' });
      
      expect(mockApiService.patch).toHaveBeenCalledWith('/tasks/bulk', {
        taskIds: [],
        status: 'completed'
      });
    });
  });
});