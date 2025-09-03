/**
 * Theme Manager - Handles dark/light theme switching and persistence
 */
class ThemeManager {
  constructor() {
    this.storageKey = "specsrv-theme";
    this.themes = {
      LIGHT: "light",
      DARK: "dark",
      SYSTEM: "system"
    };
    this.currentTheme = this.getStoredTheme();
    this.mediaQuery = window.matchMedia("(prefers-color-scheme: dark)");

    this.init();
  }

  init() {
    // Apply initial theme
    this.applyTheme(this.currentTheme);

    // Listen for system theme changes
    this.mediaQuery.addEventListener("change", (e) => {
      if (this.currentTheme === this.themes.SYSTEM) {
        this.updateDocumentClass(e.matches);
      }
    });

    // Initialize theme toggle buttons
    this.initThemeToggles();

    // Create theme change event
    this.dispatchThemeChange();
  }

  getStoredTheme() {
    const stored = localStorage.getItem(this.storageKey);
    if (stored && Object.values(this.themes).includes(stored)) {
      return stored;
    }
    return this.themes.SYSTEM; // Default to system preference
  }

  storeTheme(theme) {
    localStorage.setItem(this.storageKey, theme);
  }

  getEffectiveTheme(theme = this.currentTheme) {
    if (theme === this.themes.SYSTEM) {
      return this.mediaQuery.matches ? this.themes.DARK : this.themes.LIGHT;
    }
    return theme;
  }

  updateDocumentClass(isDark) {
    const html = document.documentElement;
    if (isDark) {
      html.classList.add("dark");
      html.setAttribute("data-theme", "dark");
    } else {
      html.classList.remove("dark");
      html.setAttribute("data-theme", "light");
    }
  }

  applyTheme(theme) {
    this.currentTheme = theme;
    const effectiveTheme = this.getEffectiveTheme(theme);
    const isDark = effectiveTheme === this.themes.DARK;

    this.updateDocumentClass(isDark);
    this.storeTheme(theme);
    this.updateThemeToggles();
    this.dispatchThemeChange();
  }

  setTheme(theme) {
    if (Object.values(this.themes).includes(theme)) {
      this.applyTheme(theme);
    }
  }

  toggleTheme() {
    const nextTheme = this.getEffectiveTheme() === this.themes.DARK
      ? this.themes.LIGHT
      : this.themes.DARK;
    this.setTheme(nextTheme);
  }

  initThemeToggles() {
    // Initialize simple toggle buttons
    document.querySelectorAll("[data-theme-toggle]").forEach(button => {
      button.addEventListener("click", () => this.toggleTheme());
    });

    // Initialize theme selector dropdowns
    document.querySelectorAll("[data-theme-selector]").forEach(selector => {
      selector.addEventListener("change", (e) => {
        this.setTheme(e.target.value);
      });
    });

    // Initialize individual theme buttons
    document.querySelectorAll("[data-theme]").forEach(button => {
      button.addEventListener("click", (e) => {
        const theme = e.currentTarget.getAttribute("data-theme");
        this.setTheme(theme);
      });
    });
  }

  updateThemeToggles() {
    const effectiveTheme = this.getEffectiveTheme();

    // Update toggle button states
    document.querySelectorAll("[data-theme-toggle]").forEach(button => {
      const isDark = effectiveTheme === this.themes.DARK;
      button.setAttribute("aria-pressed", isDark);

      // Update icon if present
      const lightIcon = button.querySelector("[data-theme-icon='light']");
      const darkIcon = button.querySelector("[data-theme-icon='dark']");

      if (lightIcon && darkIcon) {
        lightIcon.style.display = isDark ? "none" : "block";
        darkIcon.style.display = isDark ? "block" : "none";
      }
    });

    // Update theme selector values
    document.querySelectorAll("[data-theme-selector]").forEach(selector => {
      selector.value = this.currentTheme;
    });

    // Update individual theme button states
    document.querySelectorAll("[data-theme]").forEach(button => {
      const theme = button.getAttribute("data-theme");
      const isActive = theme === this.currentTheme;
      button.setAttribute("aria-pressed", isActive);
      button.classList.toggle("active", isActive);
    });
  }

  dispatchThemeChange() {
    const event = new CustomEvent("themechange", {
      detail: {
        theme: this.currentTheme,
        effectiveTheme: this.getEffectiveTheme(),
        isDark: this.getEffectiveTheme() === this.themes.DARK
      }
    });
    document.dispatchEvent(event);
  }

  // Public API
  get theme() {
    return this.currentTheme;
  }

  get effectiveTheme() {
    return this.getEffectiveTheme();
  }

  get isDark() {
    return this.getEffectiveTheme() === this.themes.DARK;
  }
}

// Auto-initialize when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  window.themeManager = new ThemeManager();
});

// Handle theme preference changes from system
if (typeof window !== "undefined") {
  // Immediate theme application to prevent flash
  const storedTheme = localStorage.getItem("specsrv-theme") || "system";
  const systemDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
  const effectiveTheme = storedTheme === "system"
    ? (systemDark ? "dark" : "light")
    : storedTheme;

  if (effectiveTheme === "dark") {
    document.documentElement.classList.add("dark");
  }
}

export default ThemeManager;