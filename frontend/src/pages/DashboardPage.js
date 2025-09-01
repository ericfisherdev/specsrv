import { BasePage } from './BasePage.js';

/**
 * Dashboard Page for SpecSrv Frontend
 */
export class DashboardPage extends BasePage {
  constructor(params, state) {
    super(params, state);
    this.dashboardData = null;
  }
  
  /**
   * Check if user can access this page
   * @returns {boolean}
   */
  canAccess() {
    return this.authService?.isUserAuthenticated();
  }
  
  /**
   * Get page title
   * @returns {string}
   */
  getTitle() {
    return 'Dashboard - SpecSrv';
  }
  
  /**
   * Get page breadcrumbs
   * @returns {Array<Object>}
   */
  getBreadcrumbs() {
    return [
      { label: 'Dashboard', url: '/dashboard' }
    ];
  }
  
  /**
   * Load dashboard data
   */
  async loadData() {
    try {
      // Load dashboard statistics
      this.dashboardData = await this.apiService.get('/dashboard/stats');
    } catch (error) {
      console.error('Failed to load dashboard data:', error);
      this.notify('Failed to load dashboard data', 'error');
      
      // Set fallback data
      this.dashboardData = {
        stats: {
          totalTasks: 0,
          completedTasks: 0,
          activeTasks: 0,
          totalProjects: 0,
        },
        recentTasks: [],
        recentActivity: [],
        upcomingDeadlines: [],
      };
    }
  }
  
  /**
   * Create the dashboard page element
   * @returns {HTMLElement}
   */
  createElement() {
    const container = document.createElement('div');
    container.className = 'dashboard-page';
    
    // Create page header
    const header = this.createPageHeader('Dashboard', {
      subtitle: 'Welcome back! Here\'s what\'s happening with your tasks and projects.',
      actions: [
        this.createActionButton('New Task', '/tasks/new', 'primary'),
        this.createActionButton('New Project', '/projects/new', 'secondary'),
      ]
    });
    
    // Create main content
    const main = document.createElement('div');
    main.className = 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8';
    
    // Stats grid
    const statsGrid = this.createStatsGrid();
    main.appendChild(statsGrid);
    
    // Content grid (charts, recent items, etc.)
    const contentGrid = document.createElement('div');
    contentGrid.className = 'grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8';
    
    // Recent tasks
    const recentTasks = this.createRecentTasks();
    contentGrid.appendChild(recentTasks);
    
    // Recent activity
    const recentActivity = this.createRecentActivity();
    contentGrid.appendChild(recentActivity);
    
    main.appendChild(contentGrid);
    
    // Upcoming deadlines
    const upcomingDeadlines = this.createUpcomingDeadlines();
    main.appendChild(upcomingDeadlines);
    
    container.appendChild(header);
    container.appendChild(main);
    
    return container;
  }
  
  /**
   * Create action button
   * @param {string} text - Button text
   * @param {string} href - Button link
   * @param {string} style - Button style
   * @returns {HTMLElement}
   */
  createActionButton(text, href, style = 'primary') {
    const button = document.createElement('a');
    button.href = href;
    button.className = `btn btn-${style}`;
    button.textContent = text;
    return button;
  }
  
  /**
   * Create stats grid
   * @returns {HTMLElement}
   */
  createStatsGrid() {
    const grid = document.createElement('div');
    grid.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6';
    
    const stats = [
      {
        title: 'Total Tasks',
        value: this.dashboardData?.stats?.totalTasks || 0,
        icon: '📋',
        color: 'blue',
        change: '+12%',
        changeType: 'positive'
      },
      {
        title: 'Active Tasks',
        value: this.dashboardData?.stats?.activeTasks || 0,
        icon: '⚡',
        color: 'yellow',
        change: '+5%',
        changeType: 'positive'
      },
      {
        title: 'Completed Tasks',
        value: this.dashboardData?.stats?.completedTasks || 0,
        icon: '✅',
        color: 'green',
        change: '+23%',
        changeType: 'positive'
      },
      {
        title: 'Total Projects',
        value: this.dashboardData?.stats?.totalProjects || 0,
        icon: '📁',
        color: 'purple',
        change: '+3%',
        changeType: 'positive'
      }
    ];
    
    stats.forEach(stat => {
      const card = this.createStatCard(stat);
      grid.appendChild(card);
    });
    
    return grid;
  }
  
  /**
   * Create stat card
   * @param {Object} stat - Stat data
   * @returns {HTMLElement}
   */
  createStatCard(stat) {
    const card = document.createElement('div');
    card.className = 'card card-elevated';
    
    card.innerHTML = `
      <div class="card-body">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 flex items-center justify-center text-2xl">
              ${stat.icon}
            </div>
          </div>
          <div class="ml-4 flex-1">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">${stat.title}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">${stat.value}</p>
          </div>
        </div>
        <div class="mt-4">
          <span class="text-sm font-medium ${stat.changeType === 'positive' ? 'text-green-600' : 'text-red-600'}">
            ${stat.change}
          </span>
          <span class="text-sm text-gray-500 dark:text-gray-400 ml-1">from last month</span>
        </div>
      </div>
    `;
    
    return card;
  }
  
  /**
   * Create recent tasks section
   * @returns {HTMLElement}
   */
  createRecentTasks() {
    const section = document.createElement('div');
    section.className = 'card';
    
    const tasks = this.dashboardData?.recentTasks || [];
    
    section.innerHTML = `
      <div class="card-header">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Tasks</h3>
        <a href="/tasks" class="text-sm text-primary-600 hover:text-primary-500">View all</a>
      </div>
      <div class="card-body">
        ${tasks.length > 0 ? this.renderTaskList(tasks) : this.renderEmptyState('No recent tasks')}
      </div>
    `;
    
    return section;
  }
  
  /**
   * Create recent activity section
   * @returns {HTMLElement}
   */
  createRecentActivity() {
    const section = document.createElement('div');
    section.className = 'card';
    
    const activities = this.dashboardData?.recentActivity || [];
    
    section.innerHTML = `
      <div class="card-header">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Activity</h3>
      </div>
      <div class="card-body">
        ${activities.length > 0 ? this.renderActivityList(activities) : this.renderEmptyState('No recent activity')}
      </div>
    `;
    
    return section;
  }
  
  /**
   * Create upcoming deadlines section
   * @returns {HTMLElement}
   */
  createUpcomingDeadlines() {
    const section = document.createElement('div');
    section.className = 'card mt-8';
    
    const deadlines = this.dashboardData?.upcomingDeadlines || [];
    
    section.innerHTML = `
      <div class="card-header">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Upcoming Deadlines</h3>
        <a href="/tasks?filter=upcoming" class="text-sm text-primary-600 hover:text-primary-500">View all</a>
      </div>
      <div class="card-body">
        ${deadlines.length > 0 ? this.renderDeadlineList(deadlines) : this.renderEmptyState('No upcoming deadlines')}
      </div>
    `;
    
    return section;
  }
  
  /**
   * Render task list
   * @param {Array} tasks - Tasks array
   * @returns {string}
   */
  renderTaskList(tasks) {
    return `
      <div class="space-y-3">
        ${tasks.map(task => `
          <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <div class="flex items-center">
              <div class="w-2 h-2 rounded-full mr-3 ${this.getStatusColor(task.status)}"></div>
              <div>
                <a href="/tasks/${task.id}" class="text-sm font-medium text-gray-900 dark:text-white hover:text-primary-600">
                  ${task.title}
                </a>
                <p class="text-xs text-gray-500 dark:text-gray-400">${task.project?.name || 'No project'}</p>
              </div>
            </div>
            <span class="badge-${task.priority} text-xs">${task.priority}</span>
          </div>
        `).join('')}
      </div>
    `;
  }
  
  /**
   * Render activity list
   * @param {Array} activities - Activities array
   * @returns {string}
   */
  renderActivityList(activities) {
    return `
      <div class="space-y-4">
        ${activities.map(activity => `
          <div class="flex items-start">
            <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center mr-3">
              <span class="text-xs">${this.getActivityIcon(activity.type)}</span>
            </div>
            <div class="flex-1">
              <p class="text-sm text-gray-900 dark:text-white">${activity.description}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">${this.formatRelativeTime(activity.created_at)}</p>
            </div>
          </div>
        `).join('')}
      </div>
    `;
  }
  
  /**
   * Render deadline list
   * @param {Array} deadlines - Deadlines array
   * @returns {string}
   */
  renderDeadlineList(deadlines) {
    return `
      <div class="space-y-3">
        ${deadlines.map(task => `
          <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-600 rounded-lg">
            <div>
              <a href="/tasks/${task.id}" class="text-sm font-medium text-gray-900 dark:text-white hover:text-primary-600">
                ${task.title}
              </a>
              <p class="text-xs text-gray-500 dark:text-gray-400">${task.project?.name || 'No project'}</p>
            </div>
            <div class="text-right">
              <p class="text-xs font-medium ${this.getDeadlineColor(task.due_date)}">
                ${this.formatDeadline(task.due_date)}
              </p>
              <span class="badge-${task.priority} text-xs">${task.priority}</span>
            </div>
          </div>
        `).join('')}
      </div>
    `;
  }
  
  /**
   * Render empty state
   * @param {string} message - Empty message
   * @returns {string}
   */
  renderEmptyState(message) {
    return `
      <div class="text-center py-8">
        <p class="text-gray-500 dark:text-gray-400">${message}</p>
      </div>
    `;
  }
  
  /**
   * Get status color class
   * @param {string} status - Task status
   * @returns {string}
   */
  getStatusColor(status) {
    const colors = {
      todo: 'bg-blue-500',
      progress: 'bg-yellow-500',
      review: 'bg-purple-500',
      completed: 'bg-green-500',
    };
    return colors[status] || 'bg-gray-500';
  }
  
  /**
   * Get activity icon
   * @param {string} type - Activity type
   * @returns {string}
   */
  getActivityIcon(type) {
    const icons = {
      task_created: '➕',
      task_completed: '✅',
      task_updated: '✏️',
      project_created: '📁',
      comment_added: '💬',
    };
    return icons[type] || '📌';
  }
  
  /**
   * Get deadline color class
   * @param {string} dueDate - Due date
   * @returns {string}
   */
  getDeadlineColor(dueDate) {
    const due = new Date(dueDate);
    const now = new Date();
    const diff = due.getTime() - now.getTime();
    const days = Math.ceil(diff / (1000 * 60 * 60 * 24));
    
    if (days < 0) return 'text-red-600';
    if (days <= 3) return 'text-orange-600';
    if (days <= 7) return 'text-yellow-600';
    return 'text-gray-600';
  }
  
  /**
   * Format deadline
   * @param {string} dueDate - Due date
   * @returns {string}
   */
  formatDeadline(dueDate) {
    const due = new Date(dueDate);
    const now = new Date();
    const diff = due.getTime() - now.getTime();
    const days = Math.ceil(diff / (1000 * 60 * 60 * 24));
    
    if (days < 0) return `${Math.abs(days)} days overdue`;
    if (days === 0) return 'Due today';
    if (days === 1) return 'Due tomorrow';
    return `Due in ${days} days`;
  }
  
  /**
   * Format relative time
   * @param {string} timestamp - Timestamp
   * @returns {string}
   */
  formatRelativeTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const minutes = Math.floor(diff / (1000 * 60));
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    
    if (days > 0) return `${days} day${days > 1 ? 's' : ''} ago`;
    if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    if (minutes > 0) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    return 'Just now';
  }
  
  /**
   * Handle page-specific keyboard shortcuts
   * @param {KeyboardEvent} event - Keyboard event
   */
  handleKeyboard(event) {
    if (event.ctrlKey || event.metaKey) {
      switch (event.key) {
        case 'n':
          event.preventDefault();
          this.navigate('/tasks/new');
          break;
        case 'p':
          event.preventDefault();
          this.navigate('/projects/new');
          break;
      }
    }
  }
}

export default DashboardPage;