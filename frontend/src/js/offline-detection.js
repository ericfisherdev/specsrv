// Offline Detection and Messaging
document.addEventListener("DOMContentLoaded", function() {
  const offlineManager = {
    isOnline: navigator.onLine,
    pendingRequests: [],
    offlineNotification: null,

    init() {
      this.createOfflineIndicator();
      this.setupEventListeners();
      this.interceptHtmxRequests();

      // Check initial state
      if (!this.isOnline) {
        this.showOfflineMessage();
      }
    },

    createOfflineIndicator() {
      const indicator = document.createElement("div");
      indicator.id = "offline-indicator";
      indicator.className = "fixed top-0 left-0 right-0 bg-red-600 text-white text-center py-2 px-4 transform -translate-y-full transition-transform duration-300 z-50";
      indicator.innerHTML = `
                <div class="flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span>You"re offline. Some features may not work properly.</span>
                    <button onclick="this.parentElement.parentElement.style.transform = "translateY(-100%)"" class="ml-4 text-red-200 hover:text-white">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            `;
      document.body.appendChild(indicator);
      this.offlineNotification = indicator;
    },

    setupEventListeners() {
      window.addEventListener("online", () => {
        this.isOnline = true;
        this.hideOfflineMessage();
        this.showOnlineMessage();
        this.processPendingRequests();
      });

      window.addEventListener("offline", () => {
        this.isOnline = false;
        this.showOfflineMessage();
      });

      // Monitor failed requests
      document.addEventListener("htmx:sendError", (event) => {
        if (!this.isOnline) {
          this.queueRequest(event.detail);
        }
      });

      document.addEventListener("htmx:responseError", (event) => {
        if (!this.isOnline || event.detail.xhr.status === 0) {
          this.queueRequest(event.detail);
        }
      });
    },

    interceptHtmxRequests() {
      document.addEventListener("htmx:beforeRequest", (event) => {
        if (!this.isOnline) {
          event.preventDefault();
          this.showOfflineActionMessage();
          return false;
        }
      });
    },

    showOfflineMessage() {
      if (this.offlineNotification) {
        this.offlineNotification.style.transform = "translateY(0)";
        this.offlineNotification.className = this.offlineNotification.className.replace("bg-red-600", "bg-red-600");
      }
    },

    hideOfflineMessage() {
      if (this.offlineNotification) {
        this.offlineNotification.style.transform = "translateY(-100%)";
      }
    },

    showOnlineMessage() {
      if (this.offlineNotification) {
        this.offlineNotification.className = this.offlineNotification.className.replace("bg-red-600", "bg-green-600");
        this.offlineNotification.innerHTML = `
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>You"re back online! ${this.pendingRequests.length > 0 ? "Processing queued actions..." : ""}</span>
                    </div>
                `;
        this.offlineNotification.style.transform = "translateY(0)";

        // Auto-hide after 3 seconds
        setTimeout(() => {
          this.hideOfflineMessage();
        }, 3000);
      }
    },

    showOfflineActionMessage() {
      this.showToast("Action queued - will execute when you\"re back online", "warning");
    },

    queueRequest(requestDetail) {
      // Store request details for retry when online
      this.pendingRequests.push({
        element: requestDetail.elt,
        event: requestDetail.event,
        timestamp: Date.now()
      });

      // Limit queue size
      if (this.pendingRequests.length > 10) {
        this.pendingRequests = this.pendingRequests.slice(-10);
      }
    },

    async processPendingRequests() {
      if (this.pendingRequests.length === 0) {return;}

      const requests = [...this.pendingRequests];
      this.pendingRequests = [];

      for (const request of requests) {
        try {
          // Retry the HTMX request
          if (request.element && request.element.dispatchEvent) {
            // Create a new event to trigger the request
            const event = new Event(request.event?.type || "click", {
              bubbles: true,
              cancelable: true
            });
            request.element.dispatchEvent(event);
          }

          // Small delay between requests to avoid overwhelming the server
          await new Promise(resolve => setTimeout(resolve, 200));
        } catch (error) {
          console.warn("Failed to process pending request:", error);
        }
      }

      if (requests.length > 0) {
        this.showToast(`Processed ${requests.length} queued action(s)`, "success");
      }
    },

    showToast(message, type = "info") {
      const toast = document.createElement("div");
      toast.className = `fixed bottom-4 right-4 max-w-sm w-full ${this.getToastClasses(type)} rounded-lg shadow-lg p-4 z-50 transform translate-y-2 opacity-0 transition-all duration-300`;

      toast.innerHTML = `
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        ${this.getToastIcon(type)}
                    </div>
                    <div class="ml-3 w-0 flex-1">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button onclick="this.closest(".fixed").remove()" class="inline-flex text-current hover:opacity-75">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `;

      document.body.appendChild(toast);

      // Animate in
      setTimeout(() => {
        toast.classList.remove("translate-y-2", "opacity-0");
        toast.classList.add("translate-y-0", "opacity-100");
      }, 10);

      // Auto-remove after 5 seconds
      setTimeout(() => {
        toast.classList.add("translate-y-2", "opacity-0");
        setTimeout(() => toast.remove(), 300);
      }, 5000);
    },

    getToastClasses(type) {
      const classes = {
        "info": "bg-blue-50 text-blue-900 border border-blue-200",
        "success": "bg-green-50 text-green-900 border border-green-200",
        "warning": "bg-yellow-50 text-yellow-900 border border-yellow-200",
        "error": "bg-red-50 text-red-900 border border-red-200"
      };
      return classes[type] || classes.info;
    },

    getToastIcon(type) {
      const icons = {
        "info": "<svg class='w-5 h-5 text-blue-400' fill='currentColor' viewBox='0 0 20 20'><path fill-rule='evenodd' d='M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z' clip-rule='evenodd'/></svg>",
        "success": "<svg class='w-5 h-5 text-green-400' fill='currentColor' viewBox='0 0 20 20'><path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' clip-rule='evenodd'/></svg>",
        "warning": "<svg class='w-5 h-5 text-yellow-400' fill='currentColor' viewBox='0 0 20 20'><path fill-rule='evenodd' d='M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z' clip-rule='evenodd'/></svg>",
        "error": "<svg class='w-5 h-5 text-red-400' fill='currentColor' viewBox='0 0 20 20'><path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z' clip-rule='evenodd'/></svg>"
      };
      return icons[type] || icons.info;
    }
  };

  // Initialize offline detection
  offlineManager.init();

  // Expose to global scope for debugging
  window.offlineManager = offlineManager;

  // Add connection status indicator to existing elements
  const statusIndicators = document.querySelectorAll("[data-connection-status]");
  statusIndicators.forEach(indicator => {
    const updateStatus = () => {
      indicator.textContent = navigator.onLine ? "Online" : "Offline";
      indicator.className = navigator.onLine ? "text-green-600" : "text-red-600";
    };

    updateStatus();
    window.addEventListener("online", updateStatus);
    window.addEventListener("offline", updateStatus);
  });
});