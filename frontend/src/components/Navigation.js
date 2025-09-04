export function createNavigation() {
  return `
        <nav x-data="{ showMobileMenu: false }" class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 transition-colors duration-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <a href="/dashboard"
                           class="text-xl font-bold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200">
                            📋 SpecSrv
                        </a>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="/dashboard"
                           class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200"
                           data-nav-item="dashboard">
                            Dashboard
                        </a>
                        <a href="/kanban"
                           class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200"
                           data-nav-item="kanban">
                            Kanban
                        </a>
                        <a href="/projects"
                           class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200"
                           data-nav-item="projects">
                            Projects
                        </a>

                        <!-- Search Bar -->
                        <div class="hidden md:block flex-1 max-w-md mx-8">
                            <div id="search-autocomplete" data-component="search-autocomplete"></div>
                        </div>

                        <!-- Theme Toggle -->
                        <button x-data="themeToggle"
                                @click="toggle()"
                                class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 focus-visible"
                                title="Toggle theme"
                                aria-label="Toggle theme">
                            <!-- Light mode icon -->
                            <svg x-show="!$store.theme?.isDark"
                                 class="w-5 h-5"
                                 fill="none"
                                 stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <!-- Dark mode icon -->
                            <svg x-show="$store.theme?.isDark"
                                 class="w-5 h-5"
                                 fill="none"
                                 stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                        </button>

                        <!-- User Menu -->
                        <div class="relative" x-data="dropdown()">
                            <button @click="toggle()"
                                    class="flex items-center text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-md transition-colors duration-200 focus-visible"
                                    :aria-expanded="open">
                                <div class="w-8 h-8 bg-primary-500 text-white rounded-full flex items-center justify-center mr-3"
                                     x-text="$store.user?.email?.charAt(0).toUpperCase() || 'U'">
                                </div>
                                <span x-text="$store.user?.email || 'User'"></span>
                                <svg class="ml-2 h-4 w-4 transition-transform duration-200"
                                     :class="{ 'rotate-180': open }"
                                     fill="currentColor"
                                     viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>

                            <div x-show="open"
                                 @click.away="close()"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50">
                                <a href="/profile"
                                   class="flex items-center px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    Profile
                                </a>
                                <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                                <button @click="$store.auth.logout()"
                                        class="flex items-center w-full px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 text-left">
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Sign out
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="md:hidden">
                        <button @click="showMobileMenu = !showMobileMenu" class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Mobile Navigation -->
                <div x-show="showMobileMenu" x-transition x-cloak class="md:hidden">
                    <div class="px-2 pt-2 pb-3 space-y-1">
                        <a href="/dashboard" class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white block px-3 py-2 text-base font-medium">Dashboard</a>
                        <a href="/kanban" class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white block px-3 py-2 text-base font-medium">Kanban</a>
                        <a href="/projects" class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white block px-3 py-2 text-base font-medium">Projects</a>
                        <a href="/profile" class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white block px-3 py-2 text-base font-medium">Profile</a>
                        <button @click="$store.auth.logout()" class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white block px-3 py-2 text-base font-medium w-full text-left">Sign out</button>
                    </div>
                </div>
            </div>
        </nav>
    `;
}

export function initializeNavigation() {
  // Set current year in footer
  const yearElement = document.querySelector('[data-current-year]');
  if (yearElement) {
    yearElement.textContent = new Date().getFullYear();
  }

  // Highlight active navigation item
  const currentPath = window.location.pathname;
  const navItems = document.querySelectorAll('[data-nav-item]');

  navItems.forEach(item => {
    const navType = item.getAttribute('data-nav-item');
    if (currentPath.includes(navType)) {
      item.classList.add('text-primary-600', 'dark:text-primary-400');
      item.classList.remove('text-gray-700', 'dark:text-gray-300');
    }
  });
}