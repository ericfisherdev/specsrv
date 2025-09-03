/**
 * Theme Manager for SpecSrv Frontend
 * Handles light/dark mode switching and persistence
 */
export class ThemeManager {
  constructor() {
    this.storageKey = 'specsrv-theme';
    this.themes = ['light', 'dark', 'system'];
    this.currentTheme = 'system';
    this.isDark = false;
    this.mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

    // Event listeners
    this.listeners = {
      change: [],
    };
  }

  /**
   * Initialize theme manager
   */
  init() {
    // Load saved theme preference
    this.currentTheme = this.getStoredTheme();

    // Set up system theme listener
    this.mediaQuery.addEventListener(
      'change',
      this.handleSystemThemeChange.bind(this)
    );

    // Apply initial theme
    this.applyTheme();

    // Prevent flash of unstyled content
    this.preventFlash();
  }

  /**
   * Add event listener
   * @param {string} event - Event name
   * @param {Function} callback - Callback function
   */
  addEventListener(event, callback) {
    if (this.listeners[event]) {
      this.listeners[event].push(callback);
    }
  }

  /**
   * Remove event listener
   * @param {string} event - Event name
   * @param {Function} callback - Callback function
   */
  removeEventListener(event, callback) {
    if (this.listeners[event]) {
      this.listeners[event] = this.listeners[event].filter(
        (cb) => cb !== callback
      );
    }
  }

  /**
   * Emit event
   * @param {string} event - Event name
   * @param {*} data - Event data
   */
  emit(event, data) {
    if (this.listeners[event]) {
      this.listeners[event].forEach(callback => callback(data));
    }

    // Also dispatch DOM event for easier integration
    document.dispatchEvent(new CustomEvent(`theme:${event}`, {
      detail: data
    }));
  }

  /**
   * Get stored theme preference
   * @returns {string}
   */
  getStoredTheme() {
    const stored = localStorage.getItem(this.storageKey);
    return this.themes.includes(stored) ? stored : 'system';
  }

  /**
   * Store theme preference
   * @param {string} theme - Theme to store
   */
  storeTheme(theme) {
    localStorage.setItem(this.storageKey, theme);
  }

  /**
   * Get effective theme (resolving "system" to actual theme)
   * @param {string} theme - Theme to resolve
   * @returns {string}
   */
  getEffectiveTheme(theme = this.currentTheme) {
    if (theme === 'system') {
      return this.mediaQuery.matches ? 'dark' : 'light';
    }
    return theme;
  }

  /**
   * Set theme
   * @param {string} theme - Theme to set
   */
  setTheme(theme) {
    if (!this.themes.includes(theme)) {
      console.warn(`Invalid theme: ${theme}. Using "system" instead.`);
      theme = 'system';
    }

    const oldTheme = this.currentTheme;
    const oldEffectiveTheme = this.getEffectiveTheme(oldTheme);

    this.currentTheme = theme;
    this.storeTheme(theme);

    const newEffectiveTheme = this.getEffectiveTheme(theme);

    // Apply theme if effective theme changed
    if (oldEffectiveTheme !== newEffectiveTheme) {
      this.applyTheme(newEffectiveTheme);
    }

    // Emit change event
    this.emit('change', {
      theme: this.currentTheme,
      effectiveTheme: newEffectiveTheme,
      isDark: newEffectiveTheme === 'dark',
      oldTheme,
      oldEffectiveTheme,
    });
  }

  /**
   * Toggle between light and dark themes
   * @returns {string} - New theme
   */
  toggle() {
    const effectiveTheme = this.getEffectiveTheme();
    const newTheme = effectiveTheme === 'dark' ? 'light' : 'dark';
    this.setTheme(newTheme);
    return newTheme;
  }

  /**
   * Cycle through all themes
   * @returns {string} - New theme
   */
  cycle() {
    const currentIndex = this.themes.indexOf(this.currentTheme);
    const nextIndex = (currentIndex + 1) % this.themes.length;
    const newTheme = this.themes[nextIndex];
    this.setTheme(newTheme);
    return newTheme;
  }

  /**
   * Apply theme to document
   * @param {string} effectiveTheme - Effective theme to apply
   */
  applyTheme(effectiveTheme = this.getEffectiveTheme()) {
    const html = document.documentElement;

    // Update dark class
    if (effectiveTheme === 'dark') {
      html.classList.add('dark');
      this.isDark = true;
    } else {
      html.classList.remove('dark');
      this.isDark = false;
    }

    // Update data attribute for CSS selector targeting
    html.setAttribute('data-theme', effectiveTheme);

    // Update meta theme-color for mobile browsers
    this.updateThemeColor(effectiveTheme);
  }

  /**
   * Update meta theme-color
   * @param {string} effectiveTheme - Current effective theme
   */
  updateThemeColor(effectiveTheme) {
    let themeColorMeta = document.querySelector('meta[name="theme-color"]');

    if (!themeColorMeta) {
      themeColorMeta = document.createElement('meta');
      themeColorMeta.name = 'theme-color';
      document.head.appendChild(themeColorMeta);
    }

    // Set theme color based on theme
    const colors = {
      light: '#ffffff',
      dark: '#111827',
    };

    themeColorMeta.content = colors[effectiveTheme] || colors.light;
  }

  /**
   * Handle system theme change
   * @param {MediaQueryListEvent} event - Media query event
   */
  handleSystemThemeChange(event) {
    if (this.currentTheme === 'system') {
      const newEffectiveTheme = event.matches ? 'dark' : 'light';
      this.applyTheme(newEffectiveTheme);

      this.emit('change', {
        theme: this.currentTheme,
        effectiveTheme: newEffectiveTheme,
        isDark: newEffectiveTheme === 'dark',
        systemChange: true,
      });
    }
  }

  /**
   * Prevent flash of unstyled content
   */
  preventFlash() {
    // This should be called early to prevent flash
    // The actual implementation is in the HTML head script
    const effectiveTheme = this.getEffectiveTheme();
    this.applyTheme(effectiveTheme);
  }

  /**
   * Get current theme
   * @returns {string}
   */
  getCurrentTheme() {
    return this.currentTheme;
  }

  /**
   * Get effective theme
   * @returns {string}
   */
  getCurrentEffectiveTheme() {
    return this.getEffectiveTheme();
  }

  /**
   * Check if current theme is dark
   * @returns {boolean}
   */
  isDarkMode() {
    return this.getEffectiveTheme() === 'dark';
  }

  /**
   * Check if current theme is light
   * @returns {boolean}
   */
  isLightMode() {
    return this.getEffectiveTheme() === 'light';
  }

  /**
   * Check if using system theme
   * @returns {boolean}
   */
  isSystemTheme() {
    return this.currentTheme === 'system';
  }

  /**
   * Get theme options for UI
   * @returns {Array<Object>}
   */
  getThemeOptions() {
    return [
      {
        value: 'light',
        label: 'Light',
        icon: '☀️',
        description: 'Light mode',
      },
      {
        value: 'dark',
        label: 'Dark',
        icon: '🌙',
        description: 'Dark mode',
      },
      {
        value: 'system',
        label: 'System',
        icon: '💻',
        description: 'Follow system preference',
      },
    ];
  }

  /**
   * Get CSS custom properties for theme
   * @param {string} effectiveTheme - Effective theme
   * @returns {Object}
   */
  getThemeProperties(effectiveTheme = this.getEffectiveTheme()) {
    const lightProperties = {
      '--color-background': '#ffffff',
      '--color-foreground': '#000000',
      '--color-surface': '#f8fafc',
      '--color-border': '#e2e8f0',
      '--color-primary': '#3b82f6',
      '--color-secondary': '#64748b',
    };

    const darkProperties = {
      '--color-background': '#111827',
      '--color-foreground': '#ffffff',
      '--color-surface': '#1f2937',
      '--color-border': '#374151',
      '--color-primary': '#60a5fa',
      '--color-secondary': '#9ca3af',
    };

    return effectiveTheme === 'dark' ? darkProperties : lightProperties;
  }

  /**
   * Apply custom properties to document
   * @param {string} effectiveTheme - Effective theme
   */
  applyCustomProperties(effectiveTheme = this.getEffectiveTheme()) {
    const properties = this.getThemeProperties(effectiveTheme);

    Object.entries(properties).forEach(([property, value]) => {
      document.documentElement.style.setProperty(property, value);
    });
  }
}