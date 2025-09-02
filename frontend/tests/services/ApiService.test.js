import { ApiService } from '../../src/services/ApiService.js';

describe('ApiService', () => {
  let apiService;
  
  beforeEach(() => {
    apiService = new ApiService();
    // Clear any stored tokens
    localStorage.clear();
    fetch.mockClear();
  });

  describe('constructor', () => {
    it('should initialize with default configuration', () => {
      expect(apiService.baseURL).toBe('http://localhost:8080/api');
      expect(apiService.version).toBe('v1');
      expect(apiService.timeout).toBe(30000);
      expect(apiService.requestInterceptors).toHaveLength(2);
      expect(apiService.responseInterceptors).toHaveLength(1);
    });

    it('should use environment variable for API base URL', () => {
      const originalEnv = process.env.API_BASE_URL;
      process.env.API_BASE_URL = 'https://api.example.com';
      
      const customApiService = new ApiService();
      expect(customApiService.baseURL).toBe('https://api.example.com');
      
      process.env.API_BASE_URL = originalEnv;
    });
  });

  describe('getUrl', () => {
    it('should construct correct URL with endpoint', () => {
      expect(apiService.getUrl('/test')).toBe('http://localhost:8080/api/v1/test');
      expect(apiService.getUrl('test')).toBe('http://localhost:8080/api/v1/test');
    });

    it('should handle empty endpoint', () => {
      expect(apiService.getUrl('')).toBe('http://localhost:8080/api/v1/');
    });
  });

  describe('interceptors', () => {
    it('should add request interceptor', () => {
      const mockInterceptor = jest.fn();
      apiService.addRequestInterceptor(mockInterceptor);
      
      expect(apiService.requestInterceptors).toContain(mockInterceptor);
    });

    it('should add response interceptor', () => {
      const mockInterceptor = jest.fn();
      apiService.addResponseInterceptor(mockInterceptor);
      
      expect(apiService.responseInterceptors).toContain(mockInterceptor);
    });
  });

  describe('addAuthHeader', () => {
    it('should add authorization header when token exists', () => {
      localStorage.getItem.mockReturnValue('test-token');
      
      const config = { headers: {} };
      const result = apiService.addAuthHeader(config);
      
      expect(result.headers.Authorization).toBe('Bearer test-token');
    });

    it('should not add authorization header when token does not exist', () => {
      localStorage.getItem.mockReturnValue(null);
      
      const config = { headers: {} };
      const result = apiService.addAuthHeader(config);
      
      expect(result.headers.Authorization).toBeUndefined();
    });

    it('should preserve existing headers', () => {
      localStorage.getItem.mockReturnValue('test-token');
      
      const config = { 
        headers: { 
          'Custom-Header': 'custom-value' 
        } 
      };
      const result = apiService.addAuthHeader(config);
      
      expect(result.headers['Custom-Header']).toBe('custom-value');
      expect(result.headers.Authorization).toBe('Bearer test-token');
    });
  });

  describe('addCommonHeaders', () => {
    it('should add standard headers', () => {
      const config = { headers: {} };
      const result = apiService.addCommonHeaders(config);
      
      expect(result.headers['Content-Type']).toBe('application/json');
      expect(result.headers['Accept']).toBe('application/json');
      expect(result.headers['X-Requested-With']).toBe('XMLHttpRequest');
    });

    it('should not override existing headers', () => {
      const config = { 
        headers: { 
          'Content-Type': 'text/plain' 
        } 
      };
      const result = apiService.addCommonHeaders(config);
      
      expect(result.headers['Content-Type']).toBe('text/plain');
    });
  });

  describe('handleResponseErrors', () => {
    beforeEach(() => {
      // Mock window.dispatchEvent
      window.dispatchEvent = jest.fn();
    });

    it('should return response if ok', async () => {
      const mockResponse = { 
        ok: true,
        status: 200 
      };
      
      const result = await apiService.handleResponseErrors(mockResponse);
      expect(result).toBe(mockResponse);
    });

    it('should handle 401 unauthorized', async () => {
      const mockResponse = { 
        ok: false, 
        status: 401,
        clone: () => ({ json: async () => ({ message: 'Unauthorized' }) })
      };
      
      await expect(apiService.handleResponseErrors(mockResponse)).rejects.toThrow();
      expect(localStorage.removeItem).toHaveBeenCalledWith('specsrv-token');
      expect(window.dispatchEvent).toHaveBeenCalledWith(
        expect.objectContaining({ type: 'auth:logout' })
      );
    });

    it('should handle 403 forbidden', async () => {
      const mockResponse = { 
        ok: false, 
        status: 403,
        clone: () => ({ json: async () => ({ message: 'Forbidden' }) })
      };
      
      await expect(apiService.handleResponseErrors(mockResponse)).rejects.toThrow();
      expect(window.dispatchEvent).toHaveBeenCalledWith(
        expect.objectContaining({ type: 'auth:forbidden' })
      );
    });

    it('should handle 429 rate limit', async () => {
      const mockResponse = { 
        ok: false, 
        status: 429,
        clone: () => ({ json: async () => ({ message: 'Rate limited' }) })
      };
      
      await expect(apiService.handleResponseErrors(mockResponse)).rejects.toThrow();
      expect(window.dispatchEvent).toHaveBeenCalledWith(
        expect.objectContaining({ type: 'api:rateLimit' })
      );
    });

    it('should handle 500 server error', async () => {
      const mockResponse = { 
        ok: false, 
        status: 500,
        clone: () => ({ json: async () => ({ message: 'Server error' }) })
      };
      
      await expect(apiService.handleResponseErrors(mockResponse)).rejects.toThrow();
      expect(window.dispatchEvent).toHaveBeenCalledWith(
        expect.objectContaining({ type: 'api:serverError' })
      );
    });

    it('should handle response with invalid JSON', async () => {
      const mockResponse = { 
        ok: false, 
        status: 400,
        statusText: 'Bad Request',
        clone: () => ({ json: async () => { throw new Error('Invalid JSON'); } })
      };
      
      try {
        await apiService.handleResponseErrors(mockResponse);
      } catch (error) {
        expect(error.message).toBe('HTTP 400: Bad Request');
        expect(error.status).toBe(400);
      }
    });
  });

  describe('HTTP methods', () => {
    beforeEach(() => {
      // Mock successful response
      const mockResponse = {
        ok: true,
        status: 200,
        headers: new Map([['content-type', 'application/json']]),
        json: async () => ({ success: true, data: 'test' })
      };
      
      fetch.mockResolvedValue(mockResponse);
    });

    describe('get', () => {
      it('should make GET request', async () => {
        await apiService.get('/test');
        
        expect(fetch).toHaveBeenCalledWith(
          'http://localhost:8080/api/v1/test',
          expect.objectContaining({
            method: 'GET',
          })
        );
      });
    });

    describe('post', () => {
      it('should make POST request with data', async () => {
        const data = { key: 'value' };
        await apiService.post('/test', data);
        
        expect(fetch).toHaveBeenCalledWith(
          'http://localhost:8080/api/v1/test',
          expect.objectContaining({
            method: 'POST',
            body: JSON.stringify(data),
          })
        );
      });

      it('should make POST request without data', async () => {
        await apiService.post('/test');
        
        expect(fetch).toHaveBeenCalledWith(
          'http://localhost:8080/api/v1/test',
          expect.objectContaining({
            method: 'POST',
            body: undefined,
          })
        );
      });
    });

    describe('put', () => {
      it('should make PUT request with data', async () => {
        const data = { key: 'value' };
        await apiService.put('/test', data);
        
        expect(fetch).toHaveBeenCalledWith(
          'http://localhost:8080/api/v1/test',
          expect.objectContaining({
            method: 'PUT',
            body: JSON.stringify(data),
          })
        );
      });
    });

    describe('patch', () => {
      it('should make PATCH request with data', async () => {
        const data = { key: 'value' };
        await apiService.patch('/test', data);
        
        expect(fetch).toHaveBeenCalledWith(
          'http://localhost:8080/api/v1/test',
          expect.objectContaining({
            method: 'PATCH',
            body: JSON.stringify(data),
          })
        );
      });
    });

    describe('delete', () => {
      it('should make DELETE request', async () => {
        await apiService.delete('/test');
        
        expect(fetch).toHaveBeenCalledWith(
          'http://localhost:8080/api/v1/test',
          expect.objectContaining({
            method: 'DELETE',
          })
        );
      });
    });
  });

  describe('upload', () => {
    it('should make upload request with FormData', async () => {
      const mockResponse = {
        ok: true,
        status: 200,
        headers: new Map([['content-type', 'application/json']]),
        json: async () => ({ success: true })
      };
      
      fetch.mockResolvedValue(mockResponse);
      
      const formData = new FormData();
      formData.append('file', 'test file');
      
      await apiService.upload('/test', formData);
      
      expect(fetch).toHaveBeenCalledWith(
        'http://localhost:8080/api/v1/test',
        expect.objectContaining({
          method: 'POST',
          body: formData,
        })
      );
      
      // Should not have Content-Type header for FormData
      const callArgs = fetch.mock.calls[0][1];
      expect(callArgs.headers['Content-Type']).toBeUndefined();
    });
  });

  describe('download', () => {
    it('should handle file download', async () => {
      const mockResponse = {
        ok: true,
        status: 200,
        blob: async () => new Blob(['test content'])
      };
      
      fetch.mockResolvedValue(mockResponse);
      
      // Mock DOM methods
      const mockElement = {
        style: {},
        click: jest.fn(),
      };
      
      document.createElement = jest.fn(() => mockElement);
      document.body.appendChild = jest.fn();
      document.body.removeChild = jest.fn();
      
      window.URL = {
        createObjectURL: jest.fn(() => 'blob:url'),
        revokeObjectURL: jest.fn(),
      };
      
      await apiService.download('/test', 'test.txt');
      
      expect(fetch).toHaveBeenCalledWith(
        'http://localhost:8080/api/v1/test',
        expect.any(Object)
      );
      
      expect(mockElement.click).toHaveBeenCalled();
      expect(window.URL.createObjectURL).toHaveBeenCalled();
      expect(window.URL.revokeObjectURL).toHaveBeenCalled();
    });
  });

  describe('request timeout', () => {
    it('should timeout after specified time', async () => {
      // Mock a slow response
      fetch.mockImplementation(() => 
        new Promise(resolve => setTimeout(resolve, 35000))
      );
      
      await expect(apiService.get('/test')).rejects.toThrow('Request timeout');
    });
  });

  describe('healthCheck', () => {
    it('should call health endpoint', async () => {
      const mockResponse = {
        ok: true,
        status: 200,
        headers: new Map([['content-type', 'application/json']]),
        json: async () => ({ status: 'healthy' })
      };
      
      fetch.mockResolvedValue(mockResponse);
      
      const result = await apiService.healthCheck();
      
      expect(fetch).toHaveBeenCalledWith(
        'http://localhost:8080/api/v1/health',
        expect.any(Object)
      );
      
      expect(result).toEqual({ status: 'healthy' });
    });
  });

  describe('request interceptor execution', () => {
    it('should apply all request interceptors in order', async () => {
      const interceptor1 = jest.fn(config => ({ ...config, header1: 'value1' }));
      const interceptor2 = jest.fn(config => ({ ...config, header2: 'value2' }));
      
      apiService.addRequestInterceptor(interceptor1);
      apiService.addRequestInterceptor(interceptor2);
      
      const mockResponse = {
        ok: true,
        status: 200,
        headers: new Map([['content-type', 'application/json']]),
        json: async () => ({ success: true })
      };
      
      fetch.mockResolvedValue(mockResponse);
      
      await apiService.get('/test');
      
      expect(interceptor1).toHaveBeenCalled();
      expect(interceptor2).toHaveBeenCalled();
    });
  });

  describe('error handling', () => {
    it('should throw error with status and data', async () => {
      const errorResponse = {
        ok: false,
        status: 400,
        clone: () => ({
          json: async () => ({ message: 'Bad request', code: 'VALIDATION_ERROR' })
        })
      };
      
      fetch.mockResolvedValue(errorResponse);
      
      try {
        await apiService.get('/test');
      } catch (error) {
        expect(error.status).toBe(400);
        expect(error.data.message).toBe('Bad request');
        expect(error.data.code).toBe('VALIDATION_ERROR');
      }
    });
  });
});