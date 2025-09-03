// JSLint configuration for SpecSrv frontend
export default {
  // JSLint options
  browser: true,     // Assume browser environment
  devel: true,       // Allow console, alert, etc.
  es6: true,         // Enable ES6 features
  indent: 2,         // 2-space indentation
  maxlen: 120,       // Maximum line length
  node: false,       // Not a Node.js environment
  
  // Allow specific globals
  predef: [
    'Alpine',
    'htmx',
    'gsap',
    'fetch',
    'URLSearchParams',
    'FormData',
    'localStorage',
    'sessionStorage',
    'setTimeout',
    'clearTimeout',
    'setInterval',
    'clearInterval'
  ]
};