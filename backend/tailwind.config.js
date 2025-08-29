/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.html.twig',
    './assets/**/*.js',
    './assets/**/*.jsx',
    './src/**/*.php', // Include PHP files for class extraction
  ],
  
  // Enable JIT mode for better performance
  mode: 'jit',
  
  // Production optimization
  safelist: [
    // Keep these classes even if not found in content scanning
    'opacity-0',
    'opacity-100',
    'scale-95',
    'scale-100',
    'transform',
    'transition-all',
    'duration-75',
    'duration-100',
    'duration-150',
    'duration-200',
    'duration-300',
    // Alpine.js animation classes
    {
      pattern: /^(enter|leave)(-active|-from|-to)?$/,
    },
    // Dynamic status and priority colors
    {
      pattern: /^(text|bg)-(red|green|blue|yellow|orange|gray)-(100|500|600|700)$/,
    },
  ],
  
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#f0f9ff',
          100: '#e0f2fe',
          200: '#bae6fd',
          300: '#7dd3fc',
          400: '#38bdf8',
          500: '#0ea5e9',
          600: '#0284c7',
          700: '#0369a1',
          800: '#075985',
          900: '#0c4a6e',
        },
        // Custom semantic colors for task management
        status: {
          backlog: '#6b7280',    // gray-500
          todo: '#3b82f6',       // blue-500
          progress: '#f59e0b',   // amber-500
          review: '#8b5cf6',     // violet-500
          completed: '#10b981',  // emerald-500
        },
        priority: {
          low: '#10b981',        // emerald-500
          medium: '#f59e0b',     // amber-500  
          high: '#f97316',       // orange-500
          critical: '#ef4444',   // red-500
        },
      },
      
      // Custom animations for better UX
      animation: {
        'fade-in': 'fadeIn 0.2s ease-in-out',
        'fade-out': 'fadeOut 0.2s ease-in-out',
        'slide-up': 'slideUp 0.3s ease-out',
        'slide-down': 'slideDown 0.3s ease-out',
        'pulse-soft': 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
      },
      
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        fadeOut: {
          '0%': { opacity: '1' },
          '100%': { opacity: '0' },
        },
        slideUp: {
          '0%': { transform: 'translateY(10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
        slideDown: {
          '0%': { transform: 'translateY(-10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
      },
      
      // Custom font sizes for better typography
      fontSize: {
        'xs': '0.75rem',
        'sm': '0.875rem', 
        'base': '1rem',
        'lg': '1.125rem',
        'xl': '1.25rem',
        '2xl': '1.5rem',
        '3xl': '1.875rem',
      },
      
      // Custom spacing for consistent layout
      spacing: {
        '18': '4.5rem',
        '88': '22rem',
      },
    },
  },
  
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
    
    // Custom plugin for component utilities
    function({ addComponents, theme }) {
      addComponents({
        '.kanban-card': {
          '@apply bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md cursor-pointer transition-shadow duration-200': {},
        },
        '.kanban-column': {
          '@apply bg-gray-50 rounded-lg p-4 border border-gray-200 min-h-96': {},
        },
        '.priority-badge': {
          '@apply inline-flex items-center px-2 py-1 text-xs font-medium rounded-full': {},
        },
        '.status-badge': {
          '@apply inline-flex items-center px-2 py-1 text-xs font-medium rounded-full': {},
        },
        '.btn': {
          '@apply px-4 py-2 text-sm font-medium rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2': {},
        },
        '.btn-primary': {
          '@apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500': {},
        },
        '.btn-secondary': {
          '@apply bg-gray-100 text-gray-700 hover:bg-gray-200 focus:ring-gray-500': {},
        },
        '.btn-danger': {
          '@apply bg-red-600 text-white hover:bg-red-700 focus:ring-red-500': {},
        },
      });
    },
  ],
}