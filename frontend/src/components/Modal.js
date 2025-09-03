/**
 * Modal Component
 * Reusable modal dialog with Alpine.js integration
 */
export class Modal {
  constructor(options = {}) {
    this.options = {
      title: 'Modal',
      size: 'md', // sm, md, lg, xl, full
      closable: true,
      backdrop: true, // Click outside to close
      keyboard: true, // ESC key to close
      scrollable: false,
      centered: true,
      ...options
    };
        
    this.isOpen = false;
    this.element = null;
    this.backdropElement = null;
    this.contentElement = null;
        
    this.onOpen = options.onOpen || (() => {});
    this.onClose = options.onClose || (() => {});
    this.onConfirm = options.onConfirm || (() => {});
    this.onCancel = options.onCancel || (() => {});
        
    this.create();
  }

  create() {
    // Create modal container
    this.element = document.createElement('div');
    this.element.className = 'modal-overlay fixed inset-0 z-50 overflow-y-auto';
    this.element.style.display = 'none';
        
    this.element.innerHTML = this.render();
        
    // Add to body
    document.body.appendChild(this.element);
        
    // Bind events
    this.bindEvents();
        
    // Initialize Alpine.js data if available
    if (typeof Alpine !== 'undefined') {
      this.element._x_dataStack = [{ 
        isOpen: false,
        close: () => this.close(),
        confirm: () => this.confirm(),
        cancel: () => this.cancel()
      }];
    }
  }

  render() {
    const sizeClasses = {
      sm: 'max-w-sm',
      md: 'max-w-md',
      lg: 'max-w-lg',
      xl: 'max-w-xl',
      '2xl': 'max-w-2xl',
      '3xl': 'max-w-3xl',
      '4xl': 'max-w-4xl',
      '5xl': 'max-w-5xl',
      '6xl': 'max-w-6xl',
      full: 'max-w-full'
    };

    const sizeClass = sizeClasses[this.options.size] || sizeClasses.md;
    const centeringClass = this.options.centered ? 'flex items-center justify-center min-h-screen' : 'flex justify-center pt-4 pb-20';

    return `
            <div class="modal-backdrop fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity" aria-hidden="true"></div>
            <div class="${centeringClass} p-4 text-center sm:p-0">
                <div class="modal-content inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle ${sizeClass} sm:w-full sm:p-6" 
                     role="dialog" 
                     aria-modal="true" 
                     aria-labelledby="modal-title">
                    
                    <!-- Modal Header -->
                    <div class="modal-header ${this.options.closable ? 'flex items-center justify-between pb-4' : 'pb-4'}">
                        <h3 class="modal-title text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            ${this.options.title}
                        </h3>
                        ${this.options.closable ? `
                            <button type="button" 
                                    class="modal-close-btn bg-white rounded-md text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    aria-label="Close">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        ` : ''}
                    </div>

                    <!-- Modal Body -->
                    <div class="modal-body ${this.options.scrollable ? 'overflow-y-auto max-h-96' : ''}">
                        <!-- Content will be inserted here -->
                    </div>

                    <!-- Modal Footer (will be shown if actions are provided) -->
                    <div class="modal-footer mt-5 sm:mt-6 sm:flex sm:flex-row-reverse" style="display: none;">
                        <!-- Footer content will be inserted here -->
                    </div>
                </div>
            </div>
        `;
  }

  bindEvents() {
    // Get elements
    this.backdropElement = this.element.querySelector('.modal-backdrop');
    this.contentElement = this.element.querySelector('.modal-content');
        
    // Close button
    const closeBtn = this.element.querySelector('.modal-close-btn');
    if (closeBtn) {
      closeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        this.close();
      });
    }

    // Backdrop click
    if (this.options.backdrop) {
      this.backdropElement.addEventListener('click', () => {
        this.close();
      });
    }

    // Keyboard events
    if (this.options.keyboard) {
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.isOpen) {
          this.close();
        }
      });
    }

    // Prevent modal content clicks from closing modal
    this.contentElement.addEventListener('click', (e) => {
      e.stopPropagation();
    });
  }

  open(content = '', actions = null) {
    this.setContent(content);
    this.setActions(actions);
        
    this.isOpen = true;
    this.element.style.display = 'block';
        
    // Add body class to prevent scrolling
    document.body.classList.add('modal-open', 'overflow-hidden');
        
    // Trigger enter animation
    setTimeout(() => {
      this.element.classList.add('opacity-100');
      this.contentElement.classList.add('opacity-100', 'translate-y-0', 'sm:scale-100');
    }, 10);
        
    // Call onOpen callback
    this.onOpen();
        
    // Dispatch event
    this.element.dispatchEvent(new CustomEvent('modal:open'));
        
    return this;
  }

  close() {
    this.isOpen = false;
        
    // Trigger exit animation
    this.element.classList.remove('opacity-100');
    this.contentElement.classList.remove('opacity-100', 'translate-y-0', 'sm:scale-100');
        
    setTimeout(() => {
      this.element.style.display = 'none';
      document.body.classList.remove('modal-open', 'overflow-hidden');
    }, 150);
        
    // Call onClose callback
    this.onClose();
        
    // Dispatch event
    this.element.dispatchEvent(new CustomEvent('modal:close'));
        
    return this;
  }

  setTitle(title) {
    const titleElement = this.element.querySelector('.modal-title');
    if (titleElement) {
      titleElement.textContent = title;
    }
    return this;
  }

  setContent(content) {
    const bodyElement = this.element.querySelector('.modal-body');
    if (bodyElement) {
      if (typeof content === 'string') {
        bodyElement.innerHTML = content;
      } else if (content instanceof HTMLElement) {
        bodyElement.innerHTML = '';
        bodyElement.appendChild(content);
      }
    }
    return this;
  }

  setActions(actions) {
    const footerElement = this.element.querySelector('.modal-footer');
        
    if (!actions || actions.length === 0) {
      footerElement.style.display = 'none';
      return this;
    }
        
    footerElement.style.display = 'block';
        
    const actionButtons = actions.map(action => {
      const buttonClass = this.getActionButtonClass(action.type || 'secondary');
      return `
                <button type="button" 
                        class="modal-action-btn ${buttonClass}"
                        data-action="${action.action || 'close'}"
                        ${action.disabled ? 'disabled' : ''}>
                    ${action.label}
                </button>
            `;
    }).join('');
        
    footerElement.innerHTML = actionButtons;
        
    // Bind action events
    footerElement.querySelectorAll('.modal-action-btn').forEach((btn, index) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const action = actions[index];
                
        if (action.handler) {
          action.handler(this);
        } else {
          switch (action.action) {
          case 'confirm':
            this.confirm();
            break;
          case 'cancel':
            this.cancel();
            break;
          case 'close':
          default:
            this.close();
            break;
          }
        }
      });
    });
        
    return this;
  }

  getActionButtonClass(type) {
    const classes = {
      primary: 'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm',
      secondary: 'mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm',
      danger: 'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm',
      success: 'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm'
    };
        
    return classes[type] || classes.secondary;
  }

  confirm() {
    this.onConfirm(this);
    this.element.dispatchEvent(new CustomEvent('modal:confirm'));
    return this;
  }

  cancel() {
    this.onCancel(this);
    this.element.dispatchEvent(new CustomEvent('modal:cancel'));
    this.close();
    return this;
  }

  destroy() {
    if (this.element && this.element.parentNode) {
      this.element.parentNode.removeChild(this.element);
    }
    this.element = null;
    this.backdropElement = null;
    this.contentElement = null;
  }

  // Static helper methods
  static confirm(options = {}) {
    const modal = new Modal({
      title: options.title || 'Confirm',
      size: options.size || 'sm',
      ...options
    });
        
    const actions = [
      {
        label: options.cancelLabel || 'Cancel',
        type: 'secondary',
        action: 'cancel'
      },
      {
        label: options.confirmLabel || 'Confirm',
        type: options.confirmType || 'primary',
        action: 'confirm'
      }
    ];
        
    return new Promise((resolve) => {
      modal.onConfirm = () => {
        resolve(true);
        modal.close();
        setTimeout(() => modal.destroy(), 300);
      };
            
      modal.onCancel = () => {
        resolve(false);
        setTimeout(() => modal.destroy(), 300);
      };
            
      modal.open(options.message || 'Are you sure?', actions);
    });
  }

  static alert(options = {}) {
    const modal = new Modal({
      title: options.title || 'Alert',
      size: options.size || 'sm',
      ...options
    });
        
    const actions = [
      {
        label: options.okLabel || 'OK',
        type: options.okType || 'primary',
        action: 'close'
      }
    ];
        
    return new Promise((resolve) => {
      modal.onClose = () => {
        resolve();
        setTimeout(() => modal.destroy(), 300);
      };
            
      modal.open(options.message || 'Alert', actions);
    });
  }

  static prompt(options = {}) {
    const modal = new Modal({
      title: options.title || 'Input Required',
      size: options.size || 'md',
      ...options
    });
        
    const inputId = `modal-prompt-${Date.now()}`;
    const content = `
            <div class="mb-4">
                <label for="${inputId}" class="block text-sm font-medium text-gray-700 mb-2">
                    ${options.message || 'Please enter a value:'}
                </label>
                <input 
                    type="${options.inputType || 'text'}" 
                    id="${inputId}"
                    class="modal-prompt-input shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                    placeholder="${options.placeholder || ''}"
                    value="${options.defaultValue || ''}"
                />
            </div>
        `;
        
    const actions = [
      {
        label: options.cancelLabel || 'Cancel',
        type: 'secondary',
        action: 'cancel'
      },
      {
        label: options.confirmLabel || 'OK',
        type: options.confirmType || 'primary',
        action: 'confirm'
      }
    ];
        
    return new Promise((resolve) => {
      modal.onConfirm = () => {
        const input = modal.element.querySelector('.modal-prompt-input');
        const value = input ? input.value : '';
        resolve(value);
        modal.close();
        setTimeout(() => modal.destroy(), 300);
      };
            
      modal.onCancel = () => {
        resolve(null);
        setTimeout(() => modal.destroy(), 300);
      };
            
      modal.open(content, actions);
            
      // Focus on input after modal opens
      setTimeout(() => {
        const input = modal.element.querySelector('.modal-prompt-input');
        if (input) {input.focus();}
      }, 100);
    });
  }
}

// Export as default for easy importing
export default Modal;