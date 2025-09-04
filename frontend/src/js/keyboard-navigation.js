/*jslint browser: true, devel: true */
'use strict';

// Keyboard Navigation Support
document.addEventListener('DOMContentLoaded', function () {

  // Global keyboard shortcuts
  document.addEventListener('keydown', function(e) {
    // Skip if typing in input/textarea
    if (e.target.tagName === 'INPUT' ||
        e.target.tagName === 'TEXTAREA' ||
        e.target.contentEditable === 'true') {
      return;
    }

    // Global shortcuts (with Ctrl/Cmd)
    if (e.ctrlKey || e.metaKey) {
      switch(e.key) {
      case 'k': // Ctrl+K - Quick search/command palette
        e.preventDefault();
        openQuickSearch();
        break;
      case 'n': // Ctrl+N - New task
        e.preventDefault();
        openNewTaskModal();
        break;
      case '/': // Ctrl+/ - Focus search
        e.preventDefault();
        focusSearch();
        break;
      }
    }

    // Navigation shortcuts (without modifier keys)
    switch(e.key) {
    case 'Escape':
      closeModals();
      break;
    case '?':
      if (!e.shiftKey) {return;}
      e.preventDefault();
      showKeyboardShortcuts();
      break;
    }
  });

  // Modal keyboard navigation
  setupModalKeyboardNavigation();

  // Dropdown keyboard navigation
  setupDropdownKeyboardNavigation();

  // Kanban keyboard navigation
  setupKanbanKeyboardNavigation();

  // Focus management
  setupFocusManagement();
});

function openQuickSearch() {
  const searchInput = document.querySelector('#global-search, [name=\'search\']');
  if (searchInput) {
    searchInput.focus();
    searchInput.select();
  }
}

function openNewTaskModal() {
  const newTaskButton = document.querySelector('[data-action=\'new-task\']');
  if (newTaskButton) {
    newTaskButton.click();
  }
}

function focusSearch() {
  const searchInputs = document.querySelectorAll('input[type=\'search\'], input[name=\'search\']');
  if (searchInputs.length > 0) {
    searchInputs[0].focus();
  }
}

function closeModals() {
  // Close Alpine.js modals
  document.querySelectorAll('[x-show]').forEach(function (modal) {
    const computedStyle = window.getComputedStyle(modal);
    if (computedStyle.display !== 'none' && modal.offsetParent !== null) {
      const closeBtn = modal.querySelector(
        '[data-action=\'close\'], .close-modal, [x-on\\:click*=\'false\']'
      );
      if (closeBtn) {
        closeBtn.click();
      }
    }
  });

  // Remove any overlay classes
  document.body.classList.remove('modal-open');
}

function showKeyboardShortcuts() {
  const shortcuts = `
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50" id="keyboard-shortcuts-modal">
        <div role="dialog" aria-modal="true" aria-labelledby="shortcuts-title" tabindex="-1" class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 id="shortcuts-title" class="text-lg font-semibold text-gray-900">Keyboard Shortcuts</h3>
                <button onclick="this.closest('#keyboard-shortcuts-modal').remove()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Search</span>
                    <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Ctrl + K</kbd>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">New Task</span>
                    <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Ctrl + N</kbd>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Focus Search</span>
                    <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Ctrl + /</kbd>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Close Modal</span>
                    <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Esc</kbd>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Show This Help</span>
                    <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">?</kbd>
                </div>
                <div class="pt-2 border-t">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Navigate Cards</span>
                        <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Arrow Keys</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Select Card</span>
                        <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Enter</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Move Card</span>
                        <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Shift + Arrow</kbd>
                    </div>
                </div>
            </div>
        </div>
    </div>
    `;

  document.body.insertAdjacentHTML('beforeend', shortcuts);
}

function setupModalKeyboardNavigation() {
  // Handle Tab navigation in modals
  document.addEventListener('keydown', function(e) {
    if (e.key !== 'Tab') {return;}

    const modal = document.querySelector('[role=\'dialog\']:not([style*=\'display: none\'])');
    if (!modal) {return;}

    const focusableElements = modal.querySelectorAll(
      'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex=\'-1\'])'
    );

    if (focusableElements.length === 0) {return;}

    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    if (e.shiftKey) {
      if (document.activeElement === firstElement) {
        e.preventDefault();
        lastElement.focus();
      }
    } else {
      if (document.activeElement === lastElement) {
        e.preventDefault();
        firstElement.focus();
      }
    }
  });
}

function setupDropdownKeyboardNavigation() {
  document.addEventListener('keydown', function(e) {
    const activeDropdown = document.querySelector('[x-show=\'true\'] [role=\'menu\'], .dropdown-menu:not(.hidden)');
    if (!activeDropdown) {return;}

    const items = activeDropdown.querySelectorAll('[role=\'menuitem\'], .dropdown-item');
    if (items.length === 0) {return;}

    let currentIndex = Array.from(items).findIndex((item) => item === document.activeElement);

    switch(e.key) {
    case 'ArrowDown':
      e.preventDefault();
      currentIndex = (currentIndex + 1) % items.length;
      items[currentIndex].focus();
      break;
    case 'ArrowUp':
      e.preventDefault();
      currentIndex = (currentIndex - 1 + items.length) % items.length;
      items[currentIndex].focus();
      break;
    case 'Enter':
    case ' ':
      e.preventDefault();
      if (document.activeElement) {
        document.activeElement.click();
      }
      break;
    }
  });
}

function setupKanbanKeyboardNavigation() {
  let selectedCard = null;

  document.addEventListener('keydown', function(e) {
    const kanbanBoard = document.querySelector('.kanban-board');
    if (!kanbanBoard) {return;}

    const cards = kanbanBoard.querySelectorAll('.task-card');
    if (cards.length === 0) {return;}

    switch(e.key) {
    case 'ArrowRight':
    case 'ArrowLeft':
    case 'ArrowUp':
    case 'ArrowDown':
      e.preventDefault();
      navigateCards(e.key, cards);
      break;
    case 'Enter':
      if (selectedCard) {
        e.preventDefault();
        selectedCard.click();
      }
      break;
    case ' ':
      if (selectedCard) {
        e.preventDefault();
        selectedCard.querySelector('[data-action=\'quick-edit\']')?.click();
      }
      break;
    }
  });

  function navigateCards(direction, cards) {
    if (!selectedCard) {
      selectedCard = cards[0];
    } else {
      const currentIndex = Array.from(cards).indexOf(selectedCard);
      let newIndex;

      switch(direction) {
      case 'ArrowRight':
        newIndex = Math.min(currentIndex + 1, cards.length - 1);
        break;
      case 'ArrowLeft':
        newIndex = Math.max(currentIndex - 1, 0);
        break;
      case 'ArrowDown':
        newIndex = Math.min(currentIndex + 5, cards.length - 1); // Assume 5 cards per row
        break;
      case 'ArrowUp':
        newIndex = Math.max(currentIndex - 5, 0);
        break;
      }

      selectedCard = cards[newIndex];
    }

    // Update visual selection
    cards.forEach((card) => card.classList.remove('keyboard-selected'));
    selectedCard.classList.add('keyboard-selected');
    selectedCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
}

function setupFocusManagement() {
  // Auto-focus first input in modals
  const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      mutation.addedNodes.forEach(function(node) {
        if (node.nodeType === Node.ELEMENT_NODE) {
          const modal = node.querySelector ? node.querySelector('[role=\'dialog\']') : null;
          if (modal || (node.getAttribute && node.getAttribute('role') === 'dialog')) {
            setTimeout(() => {
              const firstInput = (modal || node).querySelector('input:not([type=\'hidden\']), textarea, select');
              if (firstInput) {
                firstInput.focus();
              }
            }, 100);
          }
        }
      });
    });
  });

  observer.observe(document.body, { childList: true, subtree: true });

  // Add keyboard selection styles
  const style = document.createElement('style');
  style.textContent = `
        .keyboard-selected {
            outline: 2px solid #3B82F6;
            outline-offset: 2px;
        }

        /* Focus visible styles */
        .focus\\:ring-2:focus {
            outline: none;
        }

        /* Skip links for screen readers */
        .skip-link {
            position: absolute;
            top: -40px;
            left: 6px;
            background: #000;
            color: #fff;
            padding: 8px;
            z-index: 1000;
            text-decoration: none;
            border-radius: 4px;
        }

        .skip-link:focus {
            top: 6px;
        }
    `;
  document.head.appendChild(style);
}