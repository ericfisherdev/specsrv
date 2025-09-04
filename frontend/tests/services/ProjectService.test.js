import { ProjectService } from '../../src/services/ProjectService.js';

describe('ProjectService', () => {
  let projectService;
  let mockApiService;

  beforeEach(() => {
    mockApiService = {
      get: jest.fn(),
      post: jest.fn(),
      put: jest.fn(),
      delete: jest.fn(),
      upload: jest.fn(),
      download: jest.fn(),
    };
    
    projectService = new ProjectService(mockApiService);
  });

  describe('constructor', () => {
    it('should initialize with apiService', () => {
      expect(projectService.apiService).toBe(mockApiService);
    });
  });

  describe('getProjects', () => {
    it('should make GET request to /projects', async () => {
      await projectService.getProjects();
      
      expect(mockApiService.get).toHaveBeenCalledWith('/projects');
    });

    it('should include query parameters', async () => {
      const params = { page: 1, limit: 10, status: 'active' };
      await projectService.getProjects(params);
      
      expect(mockApiService.get).toHaveBeenCalledWith('/projects?page=1&limit=10&status=active');
    });

    it('should handle empty params object', async () => {
      await projectService.getProjects({});
      
      expect(mockApiService.get).toHaveBeenCalledWith('/projects');
    });
  });

  describe('getProject', () => {
    it('should make GET request to specific project endpoint', async () => {
      await projectService.getProject(123);
      
      expect(mockApiService.get).toHaveBeenCalledWith('/projects/123');
    });
  });

  describe('createProject', () => {
    it('should make POST request with project data', async () => {
      const projectData = {
        title: 'Test Project',
        description: 'A test project'
      };
      
      await projectService.createProject(projectData);
      
      expect(mockApiService.post).toHaveBeenCalledWith('/projects', projectData);
    });
  });

  describe('updateProject', () => {
    it('should make PUT request with updated data', async () => {
      const projectData = {
        title: 'Updated Project',
        description: 'Updated description'
      };
      
      await projectService.updateProject(123, projectData);
      
      expect(mockApiService.put).toHaveBeenCalledWith('/projects/123', projectData);
    });
  });

  describe('deleteProject', () => {
    it('should make DELETE request to specific project', async () => {
      await projectService.deleteProject(123);
      
      expect(mockApiService.delete).toHaveBeenCalledWith('/projects/123');
    });
  });

  describe('getProjectTasks', () => {
    it('should make GET request to project tasks endpoint', async () => {
      await projectService.getProjectTasks(123);
      
      expect(mockApiService.get).toHaveBeenCalledWith('/projects/123/tasks');
    });

    it('should include query parameters for tasks', async () => {
      const params = { status: 'todo', priority: 'high' };
      await projectService.getProjectTasks(123, params);
      
      expect(mockApiService.get).toHaveBeenCalledWith('/projects/123/tasks?status=todo&priority=high');
    });
  });

  describe('getProjectMembers', () => {
    it('should make GET request to project members endpoint', async () => {
      await projectService.getProjectMembers(123);
      
      expect(mockApiService.get).toHaveBeenCalledWith('/projects/123/members');
    });
  });

  describe('addProjectMember', () => {
    it('should make POST request to add member', async () => {
      const memberData = {
        userId: 456,
        role: 'contributor'
      };
      
      await projectService.addProjectMember(123, memberData);
      
      expect(mockApiService.post).toHaveBeenCalledWith('/projects/123/members', memberData);
    });
  });

  describe('updateProjectMember', () => {
    it('should make PUT request to update member', async () => {
      const memberData = {
        role: 'admin'
      };
      
      await projectService.updateProjectMember(123, 456, memberData);
      
      expect(mockApiService.put).toHaveBeenCalledWith('/projects/123/members/456', memberData);
    });
  });

  describe('removeProjectMember', () => {
    it('should make DELETE request to remove member', async () => {
      await projectService.removeProjectMember(123, 456);
      
      expect(mockApiService.delete).toHaveBeenCalledWith('/projects/123/members/456');
    });
  });

  describe('getProjectFiles', () => {
    it('should make GET request to project files endpoint', async () => {
      await projectService.getProjectFiles(123);
      
      expect(mockApiService.get).toHaveBeenCalledWith('/projects/123/files');
    });
  });

  describe('uploadProjectFile', () => {
    it('should use upload method for file upload', async () => {
      const formData = new FormData();
      formData.append('file', 'test file');
      
      await projectService.uploadProjectFile(123, formData);
      
      expect(mockApiService.upload).toHaveBeenCalledWith('/projects/123/files', formData);
    });
  });

  describe('deleteProjectFile', () => {
    it('should make DELETE request to remove file', async () => {
      await projectService.deleteProjectFile(123, 789);
      
      expect(mockApiService.delete).toHaveBeenCalledWith('/projects/123/files/789');
    });
  });

  describe('getProjectStats', () => {
    it('should make GET request to project stats endpoint', async () => {
      await projectService.getProjectStats(123);
      
      expect(mockApiService.get).toHaveBeenCalledWith('/projects/123/stats');
    });
  });

  describe('archiveProject', () => {
    it('should make POST request to archive project', async () => {
      await projectService.archiveProject(123);
      
      expect(mockApiService.post).toHaveBeenCalledWith('/projects/123/archive');
    });
  });

  describe('restoreProject', () => {
    it('should make POST request to restore project', async () => {
      await projectService.restoreProject(123);
      
      expect(mockApiService.post).toHaveBeenCalledWith('/projects/123/restore');
    });
  });

  describe('duplicateProject', () => {
    it('should make POST request to duplicate project', async () => {
      await projectService.duplicateProject(123);
      
      expect(mockApiService.post).toHaveBeenCalledWith('/projects/123/duplicate', {});
    });

    it('should include duplication options', async () => {
      const options = {
        includeMembers: true,
        includeFiles: false
      };
      
      await projectService.duplicateProject(123, options);
      
      expect(mockApiService.post).toHaveBeenCalledWith('/projects/123/duplicate', options);
    });
  });

  describe('getProjectActivity', () => {
    it('should make GET request to project activity endpoint', async () => {
      await projectService.getProjectActivity(123);
      
      expect(mockApiService.get).toHaveBeenCalledWith('/projects/123/activity');
    });

    it('should include activity query parameters', async () => {
      const params = { 
        limit: 20, 
        since: '2023-01-01',
        type: 'task_update' 
      };
      
      await projectService.getProjectActivity(123, params);
      
      expect(mockApiService.get).toHaveBeenCalledWith(
        '/projects/123/activity?limit=20&since=2023-01-01&type=task_update'
      );
    });
  });

  describe('exportProject', () => {
    it('should use download method with default JSON format', async () => {
      const mockDate = Date.now();
      jest.spyOn(Date, 'now').mockReturnValue(mockDate);
      
      await projectService.exportProject(123);
      
      expect(mockApiService.download).toHaveBeenCalledWith(
        '/projects/123/export?format=json',
        `project-123-${mockDate}.json`
      );
      
      Date.now.mockRestore();
    });

    it('should use specified format', async () => {
      const mockDate = Date.now();
      jest.spyOn(Date, 'now').mockReturnValue(mockDate);
      
      await projectService.exportProject(123, 'csv');
      
      expect(mockApiService.download).toHaveBeenCalledWith(
        '/projects/123/export?format=csv',
        `project-123-${mockDate}.csv`
      );
      
      Date.now.mockRestore();
    });

    it('should handle xlsx format', async () => {
      const mockDate = Date.now();
      jest.spyOn(Date, 'now').mockReturnValue(mockDate);
      
      await projectService.exportProject(123, 'xlsx');
      
      expect(mockApiService.download).toHaveBeenCalledWith(
        '/projects/123/export?format=xlsx',
        `project-123-${mockDate}.xlsx`
      );
      
      Date.now.mockRestore();
    });
  });

  describe('searchProjects', () => {
    it('should make GET request to search endpoint with query', async () => {
      await projectService.searchProjects('test query');
      
      expect(mockApiService.get).toHaveBeenCalledWith('/projects/search?q=test+query');
    });

    it('should include search filters', async () => {
      const filters = {
        status: 'active',
        owner: 'user123',
        tag: 'frontend'
      };
      
      await projectService.searchProjects('test query', filters);
      
      expect(mockApiService.get).toHaveBeenCalledWith(
        '/projects/search?q=test+query&status=active&owner=user123&tag=frontend'
      );
    });

    it('should handle empty query', async () => {
      await projectService.searchProjects('');
      
      expect(mockApiService.get).toHaveBeenCalledWith('/projects/search?q=');
    });

    it('should handle special characters in query', async () => {
      await projectService.searchProjects('test & special % chars');
      
      expect(mockApiService.get).toHaveBeenCalledWith(
        '/projects/search?q=test+%26+special+%25+chars'
      );
    });
  });

  describe('error handling', () => {
    it('should propagate API service errors', async () => {
      const error = new Error('API Error');
      mockApiService.get.mockRejectedValue(error);
      
      await expect(projectService.getProjects()).rejects.toThrow('API Error');
    });

    it('should handle numeric IDs correctly', async () => {
      await projectService.getProject('123');
      
      expect(mockApiService.get).toHaveBeenCalledWith('/projects/123');
    });
  });

  describe('method chaining and return values', () => {
    it('should return API service promise', async () => {
      const mockData = { id: 123, title: 'Test Project' };
      mockApiService.get.mockResolvedValue(mockData);
      
      const result = await projectService.getProject(123);
      
      expect(result).toEqual(mockData);
    });

    it('should handle void returns', async () => {
      mockApiService.delete.mockResolvedValue(undefined);
      
      const result = await projectService.deleteProject(123);
      
      expect(result).toBeUndefined();
    });
  });
});