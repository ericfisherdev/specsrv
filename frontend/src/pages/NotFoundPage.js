/**
 * 404 Not Found Page Component
 */
export default class NotFoundPage {
  constructor(params = {}, state = {}) {
    this.params = params;
    this.state = state;
  }

  async render(container) {
    container.innerHTML = this.getHTML();
    this.bindEvents(container);
  }

  getHTML() {
    return `
            <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
                <div class="sm:mx-auto sm:w-full sm:max-w-md">
                    <div class="text-center">
                        <!-- 404 Icon -->
                        <div class="mx-auto h-24 w-24 text-gray-400 dark:text-gray-600 mb-6">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-full h-full">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.467.881-6.097 2.33l-.896-.897C3.42 14.853 2 12.544 2 10c0-5.523 4.477-10 10-10s10 4.477 10 10c0 2.544-1.42 4.853-2.993 6.433l-.896.897A7.962 7.962 0 0112 15z"/>
                            </svg>
                        </div>

                        <!-- 404 Text -->
                        <h1 class="text-6xl font-bold text-gray-400 dark:text-gray-600 mb-4">404</h1>
                        <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-300 mb-4">
                            Page Not Found
                        </h2>
                        <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-md mx-auto">
                            The page you"re looking for doesn"t exist or has been moved.
                            Don"t worry, let"s get you back on track.
                        </p>

                        <!-- Action Buttons -->
                        <div class="space-y-4 sm:space-y-0 sm:space-x-4 sm:flex sm:justify-center">
                            <button id="go-back-btn"
                                    class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                Go Back
                            </button>

                            <button id="go-home-btn"
                                    class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-base font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                Go Home
                            </button>
                        </div>

                        <!-- Helpful Links -->
                        <div class="mt-12">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                                Or try one of these:
                            </h3>
                            <div class="flex flex-wrap justify-center gap-4">
                                <a href="/dashboard"
                                   class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 text-sm font-medium transition-colors">
                                    Dashboard
                                </a>
                                <a href="/projects"
                                   class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 text-sm font-medium transition-colors">
                                    Projects
                                </a>
                                <a href="/tasks"
                                   class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 text-sm font-medium transition-colors">
                                    Tasks
                                </a>
                                <a href="/kanban"
                                   class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 text-sm font-medium transition-colors">
                                    Kanban Board
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
  }

  bindEvents(container) {
    // Go back button
    const goBackBtn = container.querySelector('#go-back-btn');
    if (goBackBtn) {
      goBackBtn.addEventListener('click', () => {
        if (window.router) {
          window.router.back();
        } else {
          history.back();
        }
      });
    }

    // Go home button
    const goHomeBtn = container.querySelector('#go-home-btn');
    if (goHomeBtn) {
      goHomeBtn.addEventListener('click', () => {
        if (window.router) {
          window.router.navigate('/dashboard');
        } else {
          window.location.href = '/dashboard';
        }
      });
    }

    // Animate elements in
    if (window.gsap) {
      const elements = container.querySelectorAll('h1, h2, p, button, a');
      window.gsap.from(elements, {
        opacity: 0,
        y: 20,
        duration: 0.6,
        stagger: 0.1,
        ease: 'power2.out'
      });
    }
  }
}