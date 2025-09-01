// Jest setup file for DOM testing

// Mock global objects that aren't available in jsdom
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: jest.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: jest.fn(), // deprecated
    removeListener: jest.fn(), // deprecated
    addEventListener: jest.fn(),
    removeEventListener: jest.fn(),
    dispatchEvent: jest.fn(),
  })),
});

// Mock IntersectionObserver
global.IntersectionObserver = class IntersectionObserver {
  constructor() {}
  observe() {
    return null;
  }
  disconnect() {
    return null;
  }
  unobserve() {
    return null;
  }
};

// Mock ResizeObserver
global.ResizeObserver = class ResizeObserver {
  constructor() {}
  observe() {
    return null;
  }
  disconnect() {
    return null;
  }
  unobserve() {
    return null;
  }
};

// Mock fetch for API testing
global.fetch = jest.fn();

// Mock localStorage
const localStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn(),
};
global.localStorage = localStorageMock;

// Mock sessionStorage
const sessionStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn(),
};
global.sessionStorage = sessionStorageMock;

// Mock console methods to reduce test noise (can be overridden in individual tests)
global.console = {
  ...console,
  // Uncomment the next line if you want to hide console.log statements in tests
  // log: jest.fn(),
  warn: jest.fn(),
  error: jest.fn(),
};

// Setup for AlpineJS testing (when we add it)
global.Alpine = {
  start: jest.fn(),
  data: jest.fn(),
  store: jest.fn(),
};

// Setup for HTMX testing
global.htmx = {
  process: jest.fn(),
  ajax: jest.fn(),
  trigger: jest.fn(),
  find: jest.fn(),
  findAll: jest.fn(),
  closest: jest.fn(),
  remove: jest.fn(),
  addClass: jest.fn(),
  removeClass: jest.fn(),
  toggleClass: jest.fn(),
  takeClass: jest.fn(),
  defineExtension: jest.fn(),
  on: jest.fn(),
  off: jest.fn(),
  swap: jest.fn(),
  settle: jest.fn(),
  values: jest.fn(),
};

// Setup for GSAP testing
global.gsap = {
  to: jest.fn(),
  from: jest.fn(),
  fromTo: jest.fn(),
  timeline: jest.fn(() => ({
    to: jest.fn(),
    from: jest.fn(),
    fromTo: jest.fn(),
    play: jest.fn(),
    pause: jest.fn(),
    reverse: jest.fn(),
    restart: jest.fn(),
  })),
  set: jest.fn(),
  killTweensOf: jest.fn(),
};

// Cleanup after each test
afterEach(() => {
  // Clean up DOM
  document.body.innerHTML = '';
  
  // Clear all mocks
  jest.clearAllMocks();
  
  // Reset localStorage and sessionStorage
  localStorageMock.getItem.mockClear();
  localStorageMock.setItem.mockClear();
  localStorageMock.removeItem.mockClear();
  localStorageMock.clear.mockClear();
  
  sessionStorageMock.getItem.mockClear();
  sessionStorageMock.setItem.mockClear();
  sessionStorageMock.removeItem.mockClear();
  sessionStorageMock.clear.mockClear();
});

// Global test utilities
global.testUtils = {
  // Helper to create DOM elements for testing
  createElement: (tagName, attributes = {}, children = []) => {
    const element = document.createElement(tagName);
    
    Object.keys(attributes).forEach(key => {
      if (key === 'className') {
        element.className = attributes[key];
      } else if (key === 'innerHTML') {
        element.innerHTML = attributes[key];
      } else {
        element.setAttribute(key, attributes[key]);
      }
    });
    
    children.forEach(child => {
      if (typeof child === 'string') {
        element.appendChild(document.createTextNode(child));
      } else {
        element.appendChild(child);
      }
    });
    
    return element;
  },
  
  // Helper to wait for promises in tests
  waitFor: (conditionFn, timeout = 1000) => {
    return new Promise((resolve, reject) => {
      const startTime = Date.now();
      
      const checkCondition = () => {
        if (conditionFn()) {
          resolve();
        } else if (Date.now() - startTime >= timeout) {
          reject(new Error('Timeout waiting for condition'));
        } else {
          setTimeout(checkCondition, 10);
        }
      };
      
      checkCondition();
    });
  },
  
  // Helper for async test assertions
  eventually: async (assertion, timeout = 1000) => {
    const startTime = Date.now();
    let lastError;
    
    while (Date.now() - startTime < timeout) {
      try {
        await assertion();
        return;
      } catch (error) {
        lastError = error;
        await new Promise(resolve => setTimeout(resolve, 10));
      }
    }
    
    throw lastError;
  }
};