/**
 * Project List Page Component
 * Displays all projects with ability to create new ones
 */
class ProjectListPage {
  constructor() {
    this.projects = [];
    this.allProjects = [];
    this.isLoading = false;
    this.projectService = null;
  }

  /**
   * Generate the project list page HTML
   * @returns {string} HTML content
   */
  generateHTML() {
    return `
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="md:flex md:items-center md:justify-between mb-8">
          <div class="flex-1 min-w-0">
            <h1 class="text-2xl font-bold leading-7 text-white sm:text-3xl sm:truncate">
              Projects
            </h1>
            <p class="mt-1 text-sm text-gray-300">
              Manage your projects and track their progress
            </p>
          </div>
          <div class="mt-4 flex md:mt-0 md:ml-4">
            <button 
              type="button"
              id="new-project-btn"
              class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
              </svg>
              New Project
            </button>
          </div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="bg-slate-600 rounded-lg shadow-sm border border-slate-500 p-4 mb-6">
          <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
              <input 
                type="text" 
                id="search-input"
                placeholder="Search projects..."
                class="w-full px-3 py-2 border border-gray-500 bg-slate-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
            </div>
            <div class="flex gap-2">
              <select 
                id="status-filter"
                class="px-3 py-2 border border-gray-500 bg-slate-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">All Projects</option>
                <option value="active">Active</option>
                <option value="archived">Archived</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Projects Grid -->
        <div id="projects-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          ${this.renderProjectsGrid()}
        </div>

        <!-- Loading Indicator -->
        <div id="loading-indicator" class="text-center py-12" style="display: none;">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p class="mt-2 text-sm text-gray-300">Loading projects...</p>
        </div>
      </div>

      <!-- Create Project Modal -->
      <div id="project-modal" style="display: none;" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg bg-slate-700 rounded-lg shadow-lg">
          <div class="mt-3">
            <h3 class="text-lg font-medium text-white mb-4">Create New Project</h3>
            <form id="project-form">
              <div class="mb-4">
                <label for="project-title" class="block text-sm font-medium text-gray-300 mb-2">Project Title</label>
                <input 
                  type="text" 
                  id="project-title" 
                  name="title"
                  class="w-full px-3 py-2 border border-gray-500 bg-slate-600 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                >
              </div>
              <div class="mb-4">
                <label for="project-description" class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                <textarea 
                  id="project-description" 
                  name="description"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-500 bg-slate-600 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                ></textarea>
              </div>
              <div class="flex justify-end space-x-3">
                <button 
                  type="button"
                  id="cancel-project-btn"
                  class="px-4 py-2 text-sm font-medium text-gray-300 bg-slate-600 hover:bg-slate-500 border border-gray-500 rounded-md"
                >
                  Cancel
                </button>
                <button 
                  type="submit"
                  class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md"
                >
                  Create Project
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    `;
  }

  /**
   * Render the projects grid
   * @returns {string}
   */
  renderProjectsGrid() {
    if (this.isLoading) {
      return '<div class="text-center py-8">Loading projects...</div>';
    }

    if (this.projects.length === 0) {
      return `
        <div class="col-span-full text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-white">No projects</h3>
          <p class="mt-1 text-sm text-gray-300">Get started by creating a new project.</p>
          <div class="mt-6">
            <button 
              type="button" 
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              onclick="document.getElementById('new-project-btn').click()"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
              </svg>
              New Project
            </button>
          </div>
        </div>
      `;
    }

    return this.projects.map(project => this.renderProjectCard(project)).join('');
  }

  /**
   * Render a project card
   * @param {Object} project
   * @returns {string}
   */
  renderProjectCard(project) {
    const taskCount = project.task_count || 0;
    const completedTasks = project.completed_tasks || 0;
    const progress = taskCount > 0 ? Math.round((completedTasks / taskCount) * 100) : 0;

    return `
      <div class="bg-slate-600 rounded-lg shadow-sm border border-slate-500 p-6 hover:shadow-md transition-shadow cursor-pointer" onclick="window.router.navigate('/projects/${project.id}')">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-white truncate">${project.title}</h3>
          <span class="text-xs px-2 py-1 ${project.archived_at ? 'bg-gray-100 text-gray-800' : 'bg-green-100 text-green-800'} rounded-full">${project.archived_at ? 'Archived' : 'Active'}</span>
        </div>
        
        ${project.description ? `<p class="text-gray-300 text-sm mb-4 line-clamp-2">${project.description}</p>` : ''}
        
        <div class="flex items-center justify-between text-sm text-gray-300">
          <span>${taskCount} tasks</span>
          <span>${progress}% complete</span>
        </div>
        
        <div class="mt-3 w-full bg-slate-500 rounded-full h-2">
          <div class="bg-blue-600 h-2 rounded-full" style="width: ${progress}%"></div>
        </div>
        
        <div class="mt-4 flex justify-between items-center text-xs text-gray-400">
          <span>Created ${new Date(project.created_at).toLocaleDateString()}</span>
          <span>Updated ${new Date(project.updated_at).toLocaleDateString()}</span>
        </div>
      </div>
    `;
  }

  /**
   * Render method called by router
   * @param {HTMLElement} container - Container element
   */
  async render(container) {
    container.innerHTML = this.generateHTML();
    await this.init();
  }

  /**
   * Initialize the page after rendering
   */
  async init() {
    this.projectService = new (await import('../services/ProjectService.js')).ProjectService(window.app.apiService);
    this.bindEvents();
    await this.loadProjects();
  }

  /**
   * Bind event listeners
   */
  bindEvents() {
    const newProjectBtn = document.getElementById('new-project-btn');
    const cancelBtn = document.getElementById('cancel-project-btn');
    const projectForm = document.getElementById('project-form');
    const modal = document.getElementById('project-modal');

    // New project button
    if (newProjectBtn) {
      newProjectBtn.addEventListener('click', () => {
        modal.style.display = 'flex';
      });
    }

    // Cancel button
    if (cancelBtn) {
      cancelBtn.addEventListener('click', () => {
        modal.style.display = 'none';
        projectForm.reset();
      });
    }

    // Click outside modal to close
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.style.display = 'none';
        projectForm.reset();
      }
    });

    // Project form submission
    if (projectForm) {
      projectForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await this.createProject();
      });
    }

    // Search functionality
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
      let searchTimeout;
      searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          this.filterProjects();
        }, 300);
      });
    }

    // Status filter
    const statusFilter = document.getElementById('status-filter');
    if (statusFilter) {
      statusFilter.addEventListener('change', () => {
        this.filterProjects();
      });
    }
  }

  /**
   * Load projects from API
   */
  async loadProjects() {
    try {
      this.isLoading = true;
      this.updateProjectsGrid();

      const response = await this.projectService.getProjects();
      
      if (response.success) {
        this.allProjects = response.data?.items || [];
        this.projects = [...this.allProjects];
        
        // Load task statistics for each project
        await this.loadTaskStatistics();
        
        this.updateProjectsGrid();
      } else {
        throw new Error(response.error?.message || 'Failed to load projects');
      }
    } catch (error) {
      console.error('Failed to load projects:', error);
      this.showError('Failed to load projects. Please try again.');
    } finally {
      this.isLoading = false;
      this.updateProjectsGrid();
    }
  }

  /**
   * Load task statistics for all projects
   */
  async loadTaskStatistics() {
    const taskPromises = this.allProjects.map(async (project) => {
      try {
        const tasksResponse = await this.projectService.getProjectTasks(project.id);
        
        if (tasksResponse.success) {
          const tasks = tasksResponse.data?.items || [];
          const completedTasks = tasks.filter(task => task.status === 'completed').length;
          
          // Add task statistics to project object
          project.task_count = tasks.length;
          project.completed_tasks = completedTasks;
        } else {
          // Default values if task loading fails
          project.task_count = 0;
          project.completed_tasks = 0;
        }
      } catch (error) {
        console.warn(`Failed to load tasks for project ${project.id}:`, error);
        // Default values if task loading fails
        project.task_count = 0;
        project.completed_tasks = 0;
      }
    });

    // Wait for all task statistics to load
    await Promise.all(taskPromises);
  }

  /**
   * Create a new project
   */
  async createProject() {
    try {
      const formData = new FormData(document.getElementById('project-form'));
      const projectData = {
        title: formData.get('title'),
        description: formData.get('description')
      };

      const response = await this.projectService.createProject(projectData);
      
      if (response.success) {
        // Close modal and reset form
        document.getElementById('project-modal').style.display = 'none';
        document.getElementById('project-form').reset();
        
        // Reload projects
        await this.loadProjects();
        
        this.showSuccess('Project created successfully!');
      } else {
        throw new Error(response.error?.message || 'Failed to create project');
      }
    } catch (error) {
      console.error('Failed to create project:', error);
      this.showError('Failed to create project. Please try again.');
    }
  }

  /**
   * Update the projects grid display
   */
  updateProjectsGrid() {
    const container = document.getElementById('projects-container');
    if (container) {
      container.innerHTML = this.renderProjectsGrid();
    }
  }

  /**
   * Filter projects based on search and status
   */
  filterProjects() {
    const searchInput = document.getElementById('search-input');
    const statusFilter = document.getElementById('status-filter');
    
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const statusValue = statusFilter ? statusFilter.value : '';
    
    let filteredProjects = [...this.allProjects];
    
    // Apply search filter
    if (searchTerm) {
      filteredProjects = filteredProjects.filter(project => {
        return (
          project.title?.toLowerCase().includes(searchTerm) ||
          project.description?.toLowerCase().includes(searchTerm)
        );
      });
    }
    
    // Apply status filter
    if (statusValue) {
      filteredProjects = filteredProjects.filter(project => {
        if (statusValue === 'active') {
          return !project.archived_at;
        } else if (statusValue === 'archived') {
          return project.archived_at;
        }
        return true;
      });
    }
    
    this.projects = filteredProjects;
    this.updateProjectsGrid();
  }

  /**
   * Show success message
   */
  showSuccess(message) {
    // Implementation for success notification
    console.log('Success:', message);
  }

  /**
   * Show error message
   */
  showError(message) {
    // Implementation for error notification
    console.error('Error:', message);
  }

  /**
   * Clean up resources
   */
  destroy() {
    // Clean up any timers, event listeners, etc.
  }
}

// Export for dynamic import
export default ProjectListPage;