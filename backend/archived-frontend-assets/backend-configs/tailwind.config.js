/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.html.twig',
    './assets/**/*.js',
    './assets/**/*.jsx',
    './src/**/*.php', // Include PHP files for class extraction
  ],
  
  // Enable dark mode with class strategy
  darkMode: 'class',
  
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
    // Dark mode classes
    {
      pattern: /^dark:(text|bg|border)-.*/,
    },
    // Animation and interaction classes
    {
      pattern: /^(hover|focus|active|group-hover):(scale|rotate|translate|opacity).*/,
    },
  ],
  
  theme: {
    extend: {
      colors: {
        // Enhanced primary brand colors
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
          950: '#082f49',
        },
        
        // Extended secondary palette
        secondary: {
          50: '#f8fafc',
          100: '#f1f5f9',
          200: '#e2e8f0',
          300: '#cbd5e1',
          400: '#94a3b8',
          500: '#64748b',
          600: '#475569',
          700: '#334155',
          800: '#1e293b',
          900: '#0f172a',
          950: '#020617',
        },
        
        // Semantic colors for task management with dark mode variants
        status: {
          backlog: {
            light: '#6b7280',
            dark: '#9ca3af',
          },
          todo: {
            light: '#3b82f6',
            dark: '#60a5fa',
          },
          progress: {
            light: '#f59e0b',
            dark: '#fbbf24',
          },
          review: {
            light: '#8b5cf6',
            dark: '#a78bfa',
          },
          completed: {
            light: '#10b981',
            dark: '#34d399',
          },
        },
        
        priority: {
          low: {
            light: '#10b981',
            dark: '#34d399',
          },
          medium: {
            light: '#f59e0b',
            dark: '#fbbf24',
          },
          high: {
            light: '#f97316',
            dark: '#fb923c',
          },
          critical: {
            light: '#ef4444',
            dark: '#f87171',
          },
        },
        
        // Surface colors for components
        surface: {
          50: '#fafafa',
          100: '#f5f5f5',
          200: '#e5e5e5',
          300: '#d4d4d4',
          800: '#262626',
          900: '#171717',
          950: '#0a0a0a',
        },
        
        // Accent colors for highlights and CTAs
        accent: {
          cyan: '#06b6d4',
          emerald: '#10b981',
          violet: '#8b5cf6',
          pink: '#ec4899',
          orange: '#f97316',
        },
      },
      
      // Enhanced animation system
      animation: {
        // Basic transitions
        'fade-in': 'fadeIn 0.2s ease-in-out',
        'fade-out': 'fadeOut 0.2s ease-in-out',
        'slide-up': 'slideUp 0.3s ease-out',
        'slide-down': 'slideDown 0.3s ease-out',
        'slide-in-right': 'slideInRight 0.3s ease-out',
        'slide-in-left': 'slideInLeft 0.3s ease-out',
        
        // Interactive animations
        'pulse-soft': 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        'bounce-soft': 'bounceSoft 1s ease-in-out infinite',
        'wiggle': 'wiggle 1s ease-in-out infinite',
        'heartbeat': 'heartbeat 1.5s ease-in-out infinite',
        
        // Loading animations
        'spin-slow': 'spin 3s linear infinite',
        'ping-slow': 'ping 2s cubic-bezier(0, 0, 0.2, 1) infinite',
        'float': 'float 3s ease-in-out infinite',
        
        // Modal and drawer animations
        'scale-in': 'scaleIn 0.2s ease-out',
        'scale-out': 'scaleOut 0.2s ease-in',
        'drawer-slide': 'drawerSlide 0.3s ease-out',
      },
      
      keyframes: {
        // Fade animations
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        fadeOut: {
          '0%': { opacity: '1' },
          '100%': { opacity: '0' },
        },
        
        // Slide animations
        slideUp: {
          '0%': { transform: 'translateY(10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
        slideDown: {
          '0%': { transform: 'translateY(-10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
        slideInRight: {
          '0%': { transform: 'translateX(100%)', opacity: '0' },
          '100%': { transform: 'translateX(0)', opacity: '1' },
        },
        slideInLeft: {
          '0%': { transform: 'translateX(-100%)', opacity: '0' },
          '100%': { transform: 'translateX(0)', opacity: '1' },
        },
        
        // Interactive animations
        bounceSoft: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-5px)' },
        },
        wiggle: {
          '0%, 100%': { transform: 'rotate(0deg)' },
          '25%': { transform: 'rotate(-3deg)' },
          '75%': { transform: 'rotate(3deg)' },
        },
        heartbeat: {
          '0%, 100%': { transform: 'scale(1)' },
          '50%': { transform: 'scale(1.05)' },
        },
        float: {
          '0%, 100%': { transform: 'translateY(0px)' },
          '50%': { transform: 'translateY(-10px)' },
        },
        
        // Scale animations
        scaleIn: {
          '0%': { transform: 'scale(0.95)', opacity: '0' },
          '100%': { transform: 'scale(1)', opacity: '1' },
        },
        scaleOut: {
          '0%': { transform: 'scale(1)', opacity: '1' },
          '100%': { transform: 'scale(0.95)', opacity: '0' },
        },
        
        // Drawer animation
        drawerSlide: {
          '0%': { transform: 'translateX(-100%)', opacity: '0' },
          '100%': { transform: 'translateX(0)', opacity: '1' },
        },
      },
      
      // Enhanced typography system
      fontSize: {
        'xs': ['0.75rem', { lineHeight: '1rem' }],
        'sm': ['0.875rem', { lineHeight: '1.25rem' }],
        'base': ['1rem', { lineHeight: '1.5rem' }],
        'lg': ['1.125rem', { lineHeight: '1.75rem' }],
        'xl': ['1.25rem', { lineHeight: '1.75rem' }],
        '2xl': ['1.5rem', { lineHeight: '2rem' }],
        '3xl': ['1.875rem', { lineHeight: '2.25rem' }],
        '4xl': ['2.25rem', { lineHeight: '2.5rem' }],
        '5xl': ['3rem', { lineHeight: '1' }],
        '6xl': ['3.75rem', { lineHeight: '1' }],
      },
      
      // Enhanced font families
      fontFamily: {
        'sans': ['Inter var', 'Inter', 'system-ui', 'sans-serif'],
        'mono': ['JetBrains Mono', 'Fira Code', 'monospace'],
        'display': ['Poppins', 'system-ui', 'sans-serif'],
      },
      
      // Enhanced spacing system
      spacing: {
        '18': '4.5rem',
        '22': '5.5rem',
        '88': '22rem',
        '128': '32rem',
        '144': '36rem',
      },
      
      // Screen sizes for responsive design
      screens: {
        'xs': '475px',
        'sm': '640px',
        'md': '768px',
        'lg': '1024px',
        'xl': '1280px',
        '2xl': '1536px',
        '3xl': '1920px',
      },
      
      // Enhanced shadows
      boxShadow: {
        'xs': '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
        'sm': '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1)',
        'md': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1)',
        'lg': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1)',
        'xl': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1)',
        '2xl': '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
        'inner': 'inset 0 2px 4px 0 rgba(0, 0, 0, 0.05)',
        'glow': '0 0 20px rgba(59, 130, 246, 0.5)',
        'glow-lg': '0 0 40px rgba(59, 130, 246, 0.3)',
      },
      
      // Backdrop blur variations
      backdropBlur: {
        'xs': '2px',
        'sm': '4px',
        'md': '12px',
        'lg': '16px',
        'xl': '24px',
        '2xl': '40px',
        '3xl': '64px',
      },
    },
  },
  
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
    
    // Enhanced component utilities with dark mode support
    function({ addComponents, theme, addUtilities }) {
      addComponents({
        // Enhanced Kanban components
        '.kanban-card': {
          '@apply bg-white dark:bg-surface-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md dark:hover:shadow-xl cursor-pointer transition-all duration-200 group': {},
        },
        '.kanban-column': {
          '@apply bg-gray-50 dark:bg-surface-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700 min-h-96': {},
        },
        '.kanban-column-header': {
          '@apply flex items-center justify-between mb-4 pb-2 border-b border-gray-200 dark:border-gray-700': {},
        },
        
        // Enhanced badge system
        '.priority-badge': {
          '@apply inline-flex items-center px-2 py-1 text-xs font-medium rounded-full backdrop-blur-sm': {},
        },
        '.status-badge': {
          '@apply inline-flex items-center px-2 py-1 text-xs font-medium rounded-full backdrop-blur-sm': {},
        },
        '.badge-low': {
          '@apply bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300': {},
        },
        '.badge-medium': {
          '@apply bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300': {},
        },
        '.badge-high': {
          '@apply bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300': {},
        },
        '.badge-critical': {
          '@apply bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': {},
        },
        
        // Enhanced button system
        '.btn': {
          '@apply px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed': {},
        },
        '.btn-sm': {
          '@apply px-3 py-1.5 text-xs rounded-md': {},
        },
        '.btn-lg': {
          '@apply px-6 py-3 text-base rounded-xl': {},
        },
        '.btn-primary': {
          '@apply bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500 shadow-sm hover:shadow-md dark:bg-primary-500 dark:hover:bg-primary-600': {},
        },
        '.btn-secondary': {
          '@apply bg-gray-100 text-gray-700 hover:bg-gray-200 focus:ring-gray-500 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600': {},
        },
        '.btn-success': {
          '@apply bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500 dark:bg-emerald-500 dark:hover:bg-emerald-600': {},
        },
        '.btn-warning': {
          '@apply bg-amber-500 text-white hover:bg-amber-600 focus:ring-amber-500 dark:bg-amber-500 dark:hover:bg-amber-600': {},
        },
        '.btn-danger': {
          '@apply bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 dark:bg-red-500 dark:hover:bg-red-600': {},
        },
        '.btn-ghost': {
          '@apply text-gray-700 hover:bg-gray-100 focus:ring-gray-500 dark:text-gray-300 dark:hover:bg-gray-800': {},
        },
        '.btn-outline': {
          '@apply border border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-gray-500 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800': {},
        },
        
        // Card components
        '.card': {
          '@apply bg-white dark:bg-surface-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden': {},
        },
        '.card-elevated': {
          '@apply shadow-lg hover:shadow-xl transition-shadow duration-300': {},
        },
        '.card-header': {
          '@apply px-6 py-4 border-b border-gray-200 dark:border-gray-700': {},
        },
        '.card-body': {
          '@apply px-6 py-4': {},
        },
        '.card-footer': {
          '@apply px-6 py-4 bg-gray-50 dark:bg-surface-900 border-t border-gray-200 dark:border-gray-700': {},
        },
        
        // Form components
        '.form-input': {
          '@apply block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-surface-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500 sm:text-sm transition-colors duration-200': {},
        },
        '.form-input-error': {
          '@apply border-red-300 dark:border-red-600 text-red-900 dark:text-red-100 placeholder-red-300 focus:border-red-500 focus:ring-red-500': {},
        },
        '.form-label': {
          '@apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2': {},
        },
        '.form-error': {
          '@apply text-sm text-red-600 dark:text-red-400 mt-1': {},
        },
        '.form-help': {
          '@apply text-sm text-gray-500 dark:text-gray-400 mt-1': {},
        },
        
        // Modal components
        '.modal-backdrop': {
          '@apply fixed inset-0 bg-black/50 backdrop-blur-sm z-40': {},
        },
        '.modal-panel': {
          '@apply fixed inset-0 z-50 flex items-center justify-center p-4': {},
        },
        '.modal-content': {
          '@apply bg-white dark:bg-surface-800 rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto': {},
        },
        '.modal-header': {
          '@apply px-6 py-4 border-b border-gray-200 dark:border-gray-700': {},
        },
        '.modal-body': {
          '@apply px-6 py-4': {},
        },
        '.modal-footer': {
          '@apply px-6 py-4 bg-gray-50 dark:bg-surface-900 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3': {},
        },
        
        // Loading states
        '.loading-spinner': {
          '@apply inline-block w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin': {},
        },
        '.loading-dots': {
          '@apply flex space-x-1': {},
          '& > div': {
            '@apply w-2 h-2 bg-current rounded-full animate-pulse': {},
          },
        },
        '.skeleton': {
          '@apply bg-gray-200 dark:bg-gray-700 rounded animate-pulse': {},
        },
        
        // Focus states
        '.focus-visible': {
          '@apply focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800': {},
        },
      });
      
      // Utility classes
      addUtilities({
        '.text-balance': {
          'text-wrap': 'balance',
        },
        '.text-pretty': {
          'text-wrap': 'pretty',
        },
        '.scrollbar-hidden': {
          '-ms-overflow-style': 'none',
          'scrollbar-width': 'none',
          '&::-webkit-scrollbar': {
            'display': 'none',
          },
        },
        '.scrollbar-thin': {
          'scrollbar-width': 'thin',
          '&::-webkit-scrollbar': {
            'width': '6px',
          },
          '&::-webkit-scrollbar-track': {
            'background': theme('colors.gray.100'),
          },
          '&::-webkit-scrollbar-thumb': {
            'background': theme('colors.gray.300'),
            'border-radius': '3px',
          },
        },
      });
    },
  ],
}