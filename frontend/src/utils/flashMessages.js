export class FlashMessageManager {
  constructor() {
    this.container = null;
    this.init();
  }

  init() {
    this.container = document.getElementById("flash-messages");
    if (!this.container) {
      console.warn("Flash messages container not found");
    }
  }

  show(message, type = "info", duration = 5000) {
    if (!this.container) {
      console.warn("Cannot show flash message: container not initialized");
      return;
    }

    const messageElement = this.createMessageElement(message, type);
    this.container.appendChild(messageElement);

    // Auto-remove after duration
    if (duration > 0) {
      setTimeout(() => {
        this.remove(messageElement);
      }, duration);
    }

    return messageElement;
  }

  createMessageElement(message, type) {
    const div = document.createElement("div");
    div.className = this.getMessageClasses(type);
    div.setAttribute("role", "alert");
    div.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    ${this.getIcon(type)}
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium">${this.escapeHtml(message)}</p>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <button onclick="this.parentElement.parentElement.parentElement.remove()"
                            class="bg-transparent rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;

    return div;
  }

  getMessageClasses(type) {
    const baseClasses = "px-4 py-3 rounded mb-4 border transition-all duration-200";
    const typeClasses = {
      "error": "bg-red-50 dark:bg-red-900 border-red-200 dark:border-red-700 text-red-800 dark:text-red-200",
      "success": "bg-green-50 dark:bg-green-900 border-green-200 dark:border-green-700 text-green-800 dark:text-green-200",
      "warning": "bg-yellow-50 dark:bg-yellow-900 border-yellow-200 dark:border-yellow-700 text-yellow-800 dark:text-yellow-200",
      "info": "bg-blue-50 dark:bg-blue-900 border-blue-200 dark:border-blue-700 text-blue-800 dark:text-blue-200"
    };

    return `${baseClasses} ${typeClasses[type] || typeClasses.info}`;
  }

  getIcon(type) {
    const icons = {
      "success": `
                <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            `,
      "error": `
                <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            `,
      "warning": `
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            `,
      "info": `
                <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            `
    };

    return icons[type] || icons.info;
  }

  remove(element) {
    if (element && element.parentNode) {
      element.style.opacity = "0";
      element.style.transform = "translateX(100%)";
      setTimeout(() => {
        element.remove();
      }, 200);
    }
  }

  showSuccess(message, duration = 5000) {
    return this.show(message, "success", duration);
  }

  showError(message, duration = 7000) {
    return this.show(message, "error", duration);
  }

  showWarning(message, duration = 6000) {
    return this.show(message, "warning", duration);
  }

  showInfo(message, duration = 5000) {
    return this.show(message, "info", duration);
  }

  clear() {
    if (this.container) {
      this.container.innerHTML = "";
    }
  }

  escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }
}

// Global flash message instance
window.flashMessages = new FlashMessageManager();

// Toast system integration
window.toast = function() {
  return {
    showToast(message, type = "info", duration = 5000) {
      const id = Date.now() + Math.random();
      const toastEvent = new CustomEvent("toast", {
        detail: { id, message, type, duration }
      });
      window.dispatchEvent(toastEvent);
    },

    success(message, duration = 5000) {
      this.showToast(message, "success", duration);
    },

    error(message, duration = 7000) {
      this.showToast(message, "error", duration);
    },

    warning(message, duration = 6000) {
      this.showToast(message, "warning", duration);
    },

    info(message, duration = 5000) {
      this.showToast(message, "info", duration);
    }
  };
};