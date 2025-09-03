export function createSearchAutocomplete(options = {}) {
  const searchPlaceholder = options.placeholder || 'Search tasks and projects...';
    
  return `
        <div x-data="searchAutocomplete()" class="relative" @click.outside="hideSuggestions()">
            <div class="relative">
                <input 
                    type="text" 
                    x-model="query"
                    @input.debounce.300ms="search()"
                    @keydown.arrow-down.prevent="selectNext()"
                    @keydown.arrow-up.prevent="selectPrevious()"
                    @keydown.enter.prevent="selectCurrent()"
                    @keydown.escape="hideSuggestions()"
                    @focus="showSuggestions = true"
                    placeholder="${searchPlaceholder}"
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    id="global-search"
                    name="search"
                >
                
                <!-- Search Icon -->
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                
                <!-- Loading Indicator -->
                <div x-show="loading" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <svg class="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
            
            <!-- Suggestions Dropdown -->
            <div 
                x-show="showSuggestions && (suggestions.length > 0 || query.length > 0)"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-80 overflow-y-auto"
                x-cloak
            >
                <!-- Recent Searches -->
                <div x-show="query.length === 0 && recentSearches.length > 0" class="p-3 border-b border-gray-200 dark:border-gray-600">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Recent Searches</div>
                    <template x-for="recent in recentSearches" :key="recent">
                        <button 
                            @click="selectRecent(recent)"
                            class="block w-full text-left px-2 py-1 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                            x-text="recent"
                        ></button>
                    </template>
                </div>
                
                <!-- Search Results -->
                <div x-show="suggestions.length > 0">
                    <!-- Tasks Section -->
                    <div x-show="suggestions.tasks && suggestions.tasks.length > 0">
                        <div class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide border-b border-gray-100 dark:border-gray-700">
                            Tasks
                        </div>
                        <template x-for="(task, index) in suggestions.tasks" :key="task.id">
                            <a 
                                :href="task.url"
                                @click="selectSuggestion(task, 'task')"
                                class="flex items-center px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                                :class="{ 'bg-blue-50 dark:bg-blue-900': selectedIndex === index }"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="task.title"></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="task.project_title"></div>
                                </div>
                                <div class="ml-2 flex-shrink-0">
                                    <span 
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium rounded"
                                        :class="getPriorityClass(task.priority)"
                                        x-text="task.priority"
                                    ></span>
                                </div>
                            </a>
                        </template>
                    </div>
                    
                    <!-- Projects Section -->
                    <div x-show="suggestions.projects && suggestions.projects.length > 0">
                        <div class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide border-b border-gray-100 dark:border-gray-700">
                            Projects
                        </div>
                        <template x-for="(project, index) in suggestions.projects" :key="project.id">
                            <a 
                                :href="project.url"
                                @click="selectSuggestion(project, 'project')"
                                class="flex items-center px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                                :class="{ 'bg-blue-50 dark:bg-blue-900': selectedIndex === (suggestions.tasks ? suggestions.tasks.length : 0) + index }"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="project.title"></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="project.description"></div>
                                </div>
                                <div class="ml-2 flex-shrink-0">
                                    <span class="text-xs text-gray-400" x-text="project.task_count + ' tasks'"></span>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>
                
                <!-- No Results -->
                <div x-show="query.length > 0 && suggestions.length === 0 && !loading" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <div class="text-sm">No results found for "<span x-text="query" class="font-medium"></span>"</div>
                </div>
                
                <!-- Search Tips -->
                <div x-show="query.length === 0 && recentSearches.length === 0" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                    <div class="text-xs">
                        <div class="mb-2">💡 <strong>Search Tips:</strong></div>
                        <div>• Type to search tasks and projects</div>
                        <div>• Use <kbd class="px-1 py-0.5 text-xs bg-gray-200 dark:bg-gray-600 rounded">Ctrl+K</kbd> for quick search</div>
                        <div>• Use <kbd class="px-1 py-0.5 text-xs bg-gray-200 dark:bg-gray-600 rounded">↑↓</kbd> to navigate results</div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

window.searchAutocomplete = function() {
  return {
    query: '',
    suggestions: [],
    recentSearches: JSON.parse(localStorage.getItem('recent_searches') || '[]'),
    showSuggestions: false,
    loading: false,
    selectedIndex: -1,
        
    async search() {
      if (this.query.length < 2) {
        this.suggestions = [];
        this.showSuggestions = true;
        return;
      }
            
      this.loading = true;
            
      try {
        const apiService = window.apiService;
        const response = await apiService.get(`/search/suggestions?q=${encodeURIComponent(this.query)}`);
                
        if (response) {
          this.suggestions = response;
          this.showSuggestions = true;
          this.selectedIndex = -1;
        }
                
      } catch (error) {
        console.error('Search error:', error);
        this.suggestions = [];
      } finally {
        this.loading = false;
      }
    },
        
    selectNext() {
      const totalItems = (this.suggestions.tasks?.length || 0) + (this.suggestions.projects?.length || 0);
      if (totalItems > 0) {
        this.selectedIndex = Math.min(this.selectedIndex + 1, totalItems - 1);
      }
    },
        
    selectPrevious() {
      this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
    },
        
    selectCurrent() {
      if (this.selectedIndex === -1) {
        this.performGlobalSearch();
        return;
      }
            
      const taskCount = this.suggestions.tasks?.length || 0;
      let selectedItem;
            
      if (this.selectedIndex < taskCount) {
        selectedItem = this.suggestions.tasks[this.selectedIndex];
      } else {
        selectedItem = this.suggestions.projects[this.selectedIndex - taskCount];
      }
            
      if (selectedItem && selectedItem.url) {
        window.location.href = selectedItem.url;
      }
    },
        
    selectSuggestion(item, type) {
      this.addToRecentSearches(this.query);
      window.location.href = item.url;
    },
        
    selectRecent(query) {
      this.query = query;
      this.search();
    },
        
    performGlobalSearch() {
      if (this.query.trim()) {
        this.addToRecentSearches(this.query);
        window.location.href = `/search?q=${encodeURIComponent(this.query)}`;
      }
    },
        
    addToRecentSearches(query) {
      if (!query.trim()) {return;}
            
      this.recentSearches = this.recentSearches.filter(item => item !== query);
      this.recentSearches.unshift(query);
      this.recentSearches = this.recentSearches.slice(0, 5);
            
      localStorage.setItem('recent_searches', JSON.stringify(this.recentSearches));
    },
        
    hideSuggestions() {
      setTimeout(() => {
        this.showSuggestions = false;
        this.selectedIndex = -1;
      }, 150);
    },
        
    getPriorityClass(priority) {
      const classes = {
        'low': 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900',
        'medium': 'text-yellow-600 bg-yellow-100 dark:text-yellow-400 dark:bg-yellow-900',
        'high': 'text-orange-600 bg-orange-100 dark:text-orange-400 dark:bg-orange-900',
        'critical': 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-900'
      };
      return classes[priority] || 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-700';
    }
  };
};

export function initializeSearchAutocomplete() {
  const searchContainer = document.getElementById('search-autocomplete');
  if (searchContainer) {
    searchContainer.innerHTML = createSearchAutocomplete();
  }
}