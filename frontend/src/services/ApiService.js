/**
 * API Service for SpecSrv Frontend
 * Handles all HTTP communication with the backend API
 */
export class ApiService {
  constructor() {
    this.baseURL = process.env.API_BASE_URL || "http://localhost:8080/api";
    this.version = "v1";
    this.timeout = 30000; // 30 seconds

    // Request interceptors
    this.requestInterceptors = [];
    this.responseInterceptors = [];

    // Add default interceptors
    this.addRequestInterceptor(this.addAuthHeader.bind(this));
    this.addRequestInterceptor(this.addCommonHeaders.bind(this));
    this.addResponseInterceptor(this.handleResponseErrors.bind(this));
  }

  /**
   * Get full API URL
   * @param {string} endpoint
   * @returns {string}
   */
  getUrl(endpoint) {
    const cleanEndpoint = endpoint.startsWith("/") ? endpoint.slice(1) : endpoint;
    return `${this.baseURL}/${this.version}/${cleanEndpoint}`;
  }

  /**
   * Add request interceptor
   * @param {Function} interceptor
   */
  addRequestInterceptor(interceptor) {
    this.requestInterceptors.push(interceptor);
  }

  /**
   * Add response interceptor
   * @param {Function} interceptor
   */
  addResponseInterceptor(interceptor) {
    this.responseInterceptors.push(interceptor);
  }

  /**
   * Apply request interceptors
   * @param {Object} config
   * @returns {Object}
   */
  async applyRequestInterceptors(config) {
    let modifiedConfig = { ...config };

    for (const interceptor of this.requestInterceptors) {
      modifiedConfig = await interceptor(modifiedConfig);
    }

    return modifiedConfig;
  }

  /**
   * Apply response interceptors
   * @param {Response} response
   * @param {Object} config
   * @returns {Response}
   */
  async applyResponseInterceptors(response, config) {
    let modifiedResponse = response;

    for (const interceptor of this.responseInterceptors) {
      modifiedResponse = await interceptor(modifiedResponse, config);
    }

    return modifiedResponse;
  }

  /**
   * Add authentication header
   * @param {Object} config
   * @returns {Object}
   */
  addAuthHeader(config) {
    const token = localStorage.getItem("specsrv-token");
    if (token) {
      config.headers = {
        ...config.headers,
        "Authorization": `Bearer ${token}`,
      };
    }
    return config;
  }

  /**
   * Add common headers
   * @param {Object} config
   * @returns {Object}
   */
  addCommonHeaders(config) {
    // Don"t add Content-Type for FormData
    const isFormData = config.body instanceof FormData;

    config.headers = {
      ...(isFormData ? {} : { "Content-Type": "application/json" }),
      "Accept": "application/json",
      "X-Requested-With": "XMLHttpRequest",
      ...config.headers,
    };
    return config;
  }

  /**
   * Handle response errors
   * @param {Response} response
   * @param {Object} config
   * @returns {Response}
   */
  async handleResponseErrors(response, config) {
    if (!response) {
      throw new Error("No response received");
    }

    if (!response.ok) {
      // Handle specific error cases
      switch (response.status) {
      case 401:
        // Unauthorized - clear token and redirect to login
        localStorage.removeItem("specsrv-token");
        window.dispatchEvent(new CustomEvent("auth:logout"));
        break;
      case 403:
        // Forbidden
        window.dispatchEvent(new CustomEvent("auth:forbidden"));
        break;
      case 429:
        // Rate limited
        window.dispatchEvent(new CustomEvent("api:rateLimit"));
        break;
      case 500:
      case 502:
      case 503:
      case 504:
        // Server errors
        window.dispatchEvent(new CustomEvent("api:serverError"));
        break;
      }

      // Try to parse error response
      let errorData;
      try {
        errorData = await response.clone().json();
      } catch {
        errorData = { message: `HTTP ${response.status}: ${response.statusText}` };
      }

      const error = new Error(errorData.message || "Request failed");
      error.status = response.status;
      error.data = errorData;
      throw error;
    }

    return response;
  }

  /**
   * Make HTTP request
   * @param {string} method
   * @param {string} endpoint
   * @param {Object} options
   * @returns {Promise}
   */
  async request(method, endpoint, options = {}) {
    const config = {
      method: method.toUpperCase(),
      headers: {},
      ...options,
    };

    // Apply request interceptors
    const finalConfig = await this.applyRequestInterceptors(config);

    // Create AbortController for timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), this.timeout);

    try {
      const response = await fetch(this.getUrl(endpoint), {
        ...finalConfig,
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      // Apply response interceptors
      const finalResponse = await this.applyResponseInterceptors(response, finalConfig);

      // Parse JSON response
      if (finalResponse.headers.get("content-type")?.includes("application/json")) {
        const data = await finalResponse.json();
        return data;
      }

      return finalResponse;
    } catch (error) {
      clearTimeout(timeoutId);

      if (error.name === "AbortError") {
        throw new Error("Request timeout");
      }

      throw error;
    }
  }

  /**
   * GET request
   * @param {string} endpoint
   * @param {Object} options
   * @returns {Promise}
   */
  async get(endpoint, options = {}) {
    return this.request("GET", endpoint, options);
  }

  /**
   * POST request
   * @param {string} endpoint
   * @param {Object} data
   * @param {Object} options
   * @returns {Promise}
   */
  async post(endpoint, data = null, options = {}) {
    const config = {
      ...options,
      body: data ? JSON.stringify(data) : undefined,
    };

    return this.request("POST", endpoint, config);
  }

  /**
   * PUT request
   * @param {string} endpoint
   * @param {Object} data
   * @param {Object} options
   * @returns {Promise}
   */
  async put(endpoint, data = null, options = {}) {
    const config = {
      ...options,
      body: data ? JSON.stringify(data) : undefined,
    };

    return this.request("PUT", endpoint, config);
  }

  /**
   * PATCH request
   * @param {string} endpoint
   * @param {Object} data
   * @param {Object} options
   * @returns {Promise}
   */
  async patch(endpoint, data = null, options = {}) {
    const config = {
      ...options,
      body: data ? JSON.stringify(data) : undefined,
    };

    return this.request("PATCH", endpoint, config);
  }

  /**
   * DELETE request
   * @param {string} endpoint
   * @param {Object} options
   * @returns {Promise}
   */
  async delete(endpoint, options = {}) {
    return this.request("DELETE", endpoint, options);
  }

  /**
   * Upload file
   * @param {string} endpoint
   * @param {FormData} formData
   * @param {Object} options
   * @returns {Promise}
   */
  async upload(endpoint, formData, options = {}) {
    const config = {
      ...options,
      body: formData,
      headers: {
        // Don"t set Content-Type for FormData, let browser set it with boundary
        ...options.headers,
      },
    };

    return this.request("POST", endpoint, config);
  }

  /**
   * Download file
   * @param {string} endpoint
   * @param {string} filename
   * @param {Object} options
   * @returns {Promise}
   */
  async download(endpoint, filename, options = {}) {
    const config = await this.applyRequestInterceptors({
      method: "GET",
      headers: {},
      ...options,
    });

    const response = await fetch(this.getUrl(endpoint), config);
    await this.applyResponseInterceptors(response, config);

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);

    const a = document.createElement("a");
    a.style.display = "none";
    a.href = url;
    a.download = filename;

    document.body.appendChild(a);
    a.click();

    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
  }

  /**
   * Health check
   * @returns {Promise<Object>}
   */
  async healthCheck() {
    return this.get("/health");
  }
}