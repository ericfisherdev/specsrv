/**
 * Notification Manager for SpecSrv Frontend
 * Handles toast notifications and alerts
 */
export class NotificationManager {
  constructor() {
    this.notifications = [];
    this.container = null;
    this.defaultDuration = 5000;
    this.maxNotifications = 5;
    this.position = 'top-right'; // top-right, top-left, bottom-right, bottom-left, top-center, bottom-center

    this.init();
  }

  /**
   * Initialize notification manager
   */
  init() {
    this.createContainer();
    this.bindEvents();
  }

  /**
   * Create notification container
   */
  createContainer() {
    this.container = document.createElement('div');
    this.container.id = 'notification-container';
    this.container.className = this.getContainerClasses();
    document.body.appendChild(this.container);
  }

  /**
   * Get container CSS classes based on position
   * @returns {string}
   */
  getContainerClasses() {
    const baseClasses = 'fixed z-50 pointer-events-none';

    const positionClasses = {
      'top-right': 'top-4 right-4',
      'top-left': 'top-4 left-4',
      'bottom-right': 'bottom-4 right-4',
      'bottom-left': 'bottom-4 left-4',
      'top-center': 'top-4 left-1/2 transform -translate-x-1/2',
      'bottom-center': 'bottom-4 left-1/2 transform -translate-x-1/2',
    };

    return `${baseClasses} ${positionClasses[this.position]}`;
  }

  /**
   * Bind global events
   */
  bindEvents() {
    // Listen for global notification events
    document.addEventListener('notification', (event) => {
      this.show(event.detail);
    });

    // Listen for Alpine.js store updates
    if (window.Alpine?.store) {
      const notificationStore = window.Alpine.store('notifications');
      if (notificationStore) {
        // Watch for new notifications in the store
        this.watchAlpineStore(notificationStore);
      }
    }
  }

  /**
   * Watch Alpine.js store for changes
   * @param {Object} store - Alpine store
   */
  watchAlpineStore(_store) {
    // This would need to be implemented based on Alpine.js store reactivity
    // For now, we"ll handle notifications through direct method calls
  }

  /**
   * Show notification
   * @param {Object} options - Notification options
   * @returns {string} - Notification ID
   */
  show(options) {
    const notification = this.createNotification(options);

    // Remove oldest notifications if we exceed max
    while (this.notifications.length >= this.maxNotifications) {
      this.remove(this.notifications[0].id);
    }

    this.notifications.push(notification);
    this.render(notification);

    // Auto-dismiss if duration is set
    if (notification.duration > 0) {
      setTimeout(() => {
        this.remove(notification.id);
      }, notification.duration);
    }

    return notification.id;
  }

  /**
   * Create notification object
   * @param {Object} options - Notification options
   * @returns {Object}
   */
  createNotification(options) {
    const id = `notification-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

    return {
      id,
      type: options.type || 'info',
      title: options.title || '',
      message: options.message || '',
      duration: options.duration !== undefined ? options.duration : this.defaultDuration,
      icon: options.icon || this.getDefaultIcon(options.type || 'info'),
      actions: options.actions || [],
      dismissible: options.dismissible !== false,
      persistent: options.persistent === true,
      data: options.data || {},
      createdAt: new Date(),
    };
  }

  /**
   * Get default icon for notification type
   * @param {string} type - Notification type
   * @returns {string}
   */
  getDefaultIcon(type) {
    const icons = {
      success: '✓',
      error: '✕',
      warning: '⚠',
      info: 'ℹ',
    };

    return icons[type] || icons.info;
  }

  /**
   * Render notification
   * @param {Object} notification - Notification object
   */
  render(notification) {
    const element = this.createElement(notification);
    this.container.appendChild(element);

    // Trigger entrance animation
    requestAnimationFrame(() => {
      element.classList.add('notification-enter');
    });
  }

  /**
   * Create notification DOM element
   * @param {Object} notification - Notification object
   * @returns {HTMLElement}
   */
  createElement(notification) {
    const element = document.createElement('div');
    element.id = notification.id;
    element.className = this.getNotificationClasses(notification);
    element.style.pointerEvents = 'auto';

    element.innerHTML = `
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <div class="notification-icon ${this.getIconClasses(notification.type)}">
            ${this.renderIcon(notification)}
          </div>
        </div>
        <div class="ml-3 w-0 flex-1">
          ${notification.title ? '<p class="text-sm font-medium text-gray-900 dark:text-white">' + notification.title + '</p>' : ''}
          <p class="text-sm text-gray-500 dark:text-gray-400 ${notification.title ? 'mt-1' : ''}">${notification.message}</p>
          ${notification.actions.length > 0 ? this.renderActions(notification) : ''}
        </div>
        ${notification.dismissible ? this.renderCloseButton(notification) : ''}
      </div>
    `;

    // Add event listeners
    this.addEventListeners(element, notification);

    return element;
  }

  /**
   * Get notification CSS classes
   * @param {Object} notification - Notification object
   * @returns {string}
   */
  getNotificationClasses(notification) {
    const baseClasses = 'notification max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto overflow-hidden mb-4 transition-all duration-300 opacity-0 transform translate-x-full';
    const typeClasses = this.getTypeClasses(notification.type);

    return `${baseClasses} ${typeClasses}`;
  }

  /**
   * Get type-specific CSS classes
   * @param {string} type - Notification type
   * @returns {string}
   */
  getTypeClasses(type) {
    const typeClasses = {
      success: 'border-l-4 border-l-green-500',
      error: 'border-l-4 border-l-red-500',
      warning: 'border-l-4 border-l-yellow-500',
      info: 'border-l-4 border-l-blue-500',
    };

    return typeClasses[type] || typeClasses.info;
  }

  /**
   * Get icon CSS classes
   * @param {string} type - Notification type
   * @returns {string}
   */
  getIconClasses(type) {
    const iconClasses = {
      success: 'text-green-400',
      error: 'text-red-400',
      warning: 'text-yellow-400',
      info: 'text-blue-400',
    };

    return `w-6 h-6 ${iconClasses[type] || iconClasses.info}`;
  }

  /**
   * Render notification icon
   * @param {Object} notification - Notification object
   * @returns {string}
   */
  renderIcon(notification) {
    if (typeof notification.icon === 'string') {
      // Check if it"s an SVG or emoji
      if (notification.icon.includes('<svg')) {
        return notification.icon;
      } else {
        return `<span class="text-lg">${notification.icon}</span>`;
      }
    }

    // Default SVG icons
    const icons = {
      success: '<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>',
      error: '<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>',
      warning: '<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>',
      info: '<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>',
    };

    return icons[notification.type] || icons.info;
  }

  /**
   * Render action buttons
   * @param {Object} notification - Notification object
   * @returns {string}
   */
  renderActions(notification) {
    if (!notification.actions.length) {return '';}

    const actionsHtml = notification.actions.map(action => `
      <button class="notification-action text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-500 mr-3"
              data-action="${action.id}">
        ${action.label}
      </button>
    `).join('');

    return `<div class="mt-2">${actionsHtml}</div>`;
  }

  /**
   * Render close button
   * @param {Object} notification - Notification object
   * @returns {string}
   */
  renderCloseButton(_notification) {
    return `
      <div class="ml-4 flex-shrink-0 flex">
        <button class="notification-close bg-white dark:bg-gray-800 rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
          <span class="sr-only">Close</span>
          <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
          </svg>
        </button>
      </div>
    `;
  }

  /**
   * Add event listeners to notification element
   * @param {HTMLElement} element - Notification element
   * @param {Object} notification - Notification object
   */
  addEventListeners(element, notification) {
    // Close button
    const closeButton = element.querySelector('.notification-close');
    if (closeButton) {
      closeButton.addEventListener('click', () => {
        this.remove(notification.id);
      });
    }

    // Action buttons
    const actionButtons = element.querySelectorAll('.notification-action');
    actionButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        const actionId = e.target.dataset.action;
        const action = notification.actions.find(a => a.id === actionId);
        if (action && typeof action.handler === 'function') {
          action.handler(notification, e);
        }
      });
    });

    // Auto-dismiss on click (if not persistent)
    if (!notification.persistent) {
      element.addEventListener('click', () => {
        this.remove(notification.id);
      });
    }
  }

  /**
   * Remove notification
   * @param {string} id - Notification ID
   */
  remove(id) {
    const notification = this.notifications.find(n => n.id === id);
    if (!notification) {return;}

    const element = document.getElementById(id);
    if (element) {
      // Trigger exit animation
      element.classList.add('notification-exit');
      element.classList.remove('notification-enter');

      setTimeout(() => {
        if (element.parentNode) {
          element.parentNode.removeChild(element);
        }
      }, 300);
    }

    // Remove from notifications array
    this.notifications = this.notifications.filter(n => n.id !== id);
  }

  /**
   * Clear all notifications
   */
  clear() {
    this.notifications.forEach(notification => {
      this.remove(notification.id);
    });
  }

  /**
   * Show success notification
   * @param {string} message - Message
   * @param {Object} options - Additional options
   * @returns {string} - Notification ID
   */
  success(message, options = {}) {
    return this.show({ ...options, message, type: 'success' });
  }

  /**
   * Show error notification
   * @param {string} message - Message
   * @param {Object} options - Additional options
   * @returns {string} - Notification ID
   */
  error(message, options = {}) {
    return this.show({
      ...options,
      message,
      type: 'error',
      duration: options.duration || 0 // Errors persist by default
    });
  }

  /**
   * Show warning notification
   * @param {string} message - Message
   * @param {Object} options - Additional options
   * @returns {string} - Notification ID
   */
  warning(message, options = {}) {
    return this.show({ ...options, message, type: 'warning' });
  }

  /**
   * Show info notification
   * @param {string} message - Message
   * @param {Object} options - Additional options
   * @returns {string} - Notification ID
   */
  info(message, options = {}) {
    return this.show({ ...options, message, type: 'info' });
  }

  /**
   * Get all notifications
   * @returns {Array}
   */
  getAll() {
    return [...this.notifications];
  }

  /**
   * Get notification by ID
   * @param {string} id - Notification ID
   * @returns {Object|null}
   */
  getById(id) {
    return this.notifications.find(n => n.id === id) || null;
  }

  /**
   * Set position
   * @param {string} position - New position
   */
  setPosition(position) {
    this.position = position;
    if (this.container) {
      this.container.className = this.getContainerClasses();
    }
  }

  /**
   * Set max notifications
   * @param {number} max - Maximum number of notifications
   */
  setMaxNotifications(max) {
    this.maxNotifications = Math.max(1, max);
  }

  /**
   * Set default duration
   * @param {number} duration - Default duration in ms
   */
  setDefaultDuration(duration) {
    this.defaultDuration = duration;
  }
}