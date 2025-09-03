/**
 * FileList Component
 * Displays a list of files with download, preview, and delete functionality
 */
export class FileList {
  constructor(container, options = {}) {
    this.container = container;
    this.options = {
      canDelete: false,
      baseUrl: '/api/v1/files',
      ...options
    };
    this.files = [];
    this.previewFile = null;

    this.init();
  }

  init() {
    // Initialize Alpine.js data
    if (this.container && typeof Alpine !== 'undefined') {
      this.container._x_dataStack = [{
        previewFile: null,
        togglePreview: (filename) => this.togglePreview(filename),
        deleteFile: (filename) => this.deleteFile(filename)
      }];
    }
  }

  /**
     * Render the file list
     * @param {Array} files - Array of file objects
     */
  render(files = []) {
    this.files = files;

    if (files.length === 0) {
      this.container.innerHTML = this.renderEmptyState();
      return;
    }

    const html = `
            <div class="space-y-3" x-data="{ previewFile: null }">
                <h4 class="text-sm font-medium text-gray-900">Attached Files (${files.length})</h4>
                <div class="grid gap-3">
                    ${files.map(file => this.renderFileItem(file)).join('')}
                </div>
            </div>
        `;

    this.container.innerHTML = html;
  }

  /**
     * Render a single file item
     * @param {Object} file - File object
     */
  renderFileItem(file) {
    const icon = this.getFileIcon(file.mime_type);
    const canPreview = this.canPreviewFile(file.mime_type);

    return `
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border hover:bg-gray-100 transition-colors">
                <div class="flex items-center space-x-3 min-w-0 flex-1">
                    <!-- File Icon -->
                    <div class="flex-shrink-0">
                        ${icon}
                    </div>

                    <!-- File Info -->
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            ${file.original_name || file.filename}
                        </p>
                        <div class="flex items-center space-x-2 text-xs text-gray-500">
                            <span>${this.formatFileSize(file.size)}</span>
                            <span>•</span>
                            <span>${file.mime_type || 'Unknown type'}</span>
                            ${file.created_at ? '<span>•</span><span>' + this.formatDate(file.created_at) + '</span>' : ''}
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center space-x-2 ml-4">
                    ${canPreview ? this.renderPreviewButton(file) : ''}
                    ${this.renderDownloadButton(file)}
                    ${this.options.canDelete ? this.renderDeleteButton(file) : ''}
                </div>
            </div>

            ${canPreview ? this.renderPreviewPanel(file) : ''}
        `;
  }

  /**
     * Render preview button
     */
  renderPreviewButton(file) {
    return `
            <button
                @click="previewFile = previewFile === \"${file.filename}\" ? null : \"${file.filename}\""
                class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-600 hover:text-blue-500"
                title="Preview file"
            >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Preview
            </button>
        `;
  }

  /**
     * Render download button
     */
  renderDownloadButton(file) {
    return `
            <a
                href="${this.options.baseUrl}/download/${file.filename}"
                class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-600 hover:text-green-500"
                title="Download file"
                download
            >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Download
            </a>
        `;
  }

  /**
     * Render delete button
     */
  renderDeleteButton(file) {
    return `
            <button
                @click="$dispatch(\"file-delete\", { filename: \"${file.filename}\" })"
                class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-600 hover:text-red-500"
                data-filename="${file.filename || file.original_name}"
                title="Delete file"
            >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete
            </button>
        `;
  }

  /**
     * Render preview panel
     */
  renderPreviewPanel(file) {
    return `
            <div
                x-show="previewFile === \"${file.filename}\""
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm"
                x-cloak
            >
                <div class="flex items-center justify-between mb-3">
                    <h5 class="text-sm font-medium text-gray-900">${file.original_name || file.filename}</h5>
                    <button
                        @click="previewFile = null"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div
                    id="preview-content-${file.filename}"
                    class="prose prose-sm max-w-none"
                >
                    <div class="flex items-center justify-center py-8">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="ml-2 text-sm text-gray-500">Loading preview...</span>
                    </div>
                </div>
            </div>
        `;
  }

  /**
     * Render empty state
     */
  renderEmptyState() {
    return `
            <div class="text-center py-6 text-gray-500">
                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="mt-2 text-sm">No files attached</p>
            </div>
        `;
  }

  /**
     * Get appropriate file icon based on MIME type
     */
  getFileIcon(mimeType) {
    if (mimeType && mimeType.startsWith('image/')) {
      return `
                <svg class="h-8 w-8 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                </svg>
            `;
    } else if (mimeType === 'application/pdf') {
      return `
                <svg class="h-8 w-8 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                    <path d="M8 8a.5.5 0 01.5-.5H10a.5.5 0 010 1H8.5A.5.5 0 018 8zm0 2.5a.5.5 0 01.5-.5H11a.5.5 0 010 1H8.5a.5.5 0 01-.5-.5zm0 2.5a.5.5 0 01.5-.5H9a.5.5 0 010 1H8.5A.5.5 0 018 13z"/>
                </svg>
            `;
    } else if (mimeType && mimeType.startsWith('text/')) {
      return `
                <svg class="h-8 w-8 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                </svg>
            `;
    } else {
      return `
                <svg class="h-8 w-8 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                </svg>
            `;
    }
  }

  /**
     * Check if file can be previewed
     */
  canPreviewFile(mimeType) {
    return mimeType && (mimeType.startsWith('text/markdown') || mimeType.startsWith('text/plain'));
  }

  /**
     * Format file size
     */
  formatFileSize(bytes) {
    if (!bytes) {return 'Unknown size';}
    return Math.round(bytes / 1024 * 10) / 10 + ' KB';
  }

  /**
     * Format date
     */
  formatDate(dateString) {
    if (!dateString) {return '';}
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }

  /**
     * Toggle file preview
     */
  async togglePreview(filename) {
    const previewContainer = document.getElementById(`preview-content-${filename}`);
    if (!previewContainer) {return;}

    try {
      // Get auth token from options, localStorage, or cookie
      const authToken = this.options.authToken || 
        localStorage.getItem('specsrv-token') || 
        this.getCookieValue('auth-token');
      
      const headers = {
        'Accept': 'text/plain, text/markdown'
      };
      
      if (authToken) {
        headers['Authorization'] = `Bearer ${authToken}`;
      }
      
      const response = await fetch(`${this.options.baseUrl}/preview/${filename}`, {
        headers
      });
      
      if (response.ok) {
        const content = await response.text();
        previewContainer.innerHTML = content;
      } else {
        previewContainer.innerHTML = '<p class="text-red-600">Failed to load preview</p>';
      }
    } catch (error) {
      previewContainer.innerHTML = '<p class="text-red-600">Error loading preview</p>';
    }
  }

  /**
     * Get cookie value by name
     */
  getCookieValue(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
  }

  /**
     * Delete a file
     */
  async deleteFile(filename) {
    if (!confirm('Are you sure you want to delete this file?')) {
      return;
    }

    try {
      const response = await fetch(`${this.options.baseUrl}/${filename}`, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
        },
      });

      if (response.ok) {
        // Remove file from DOM
        const fileElement = document.querySelector(`[data-filename="${filename}"]`);
        if (fileElement) {
          fileElement.remove();
        }

        // Emit custom event
        this.container.dispatchEvent(new CustomEvent('file-deleted', {
          detail: { filename }
        }));
      } else {
        alert('Failed to delete file');
      }
    } catch (error) {
      alert('Error deleting file');
    }
  }
}

// Export as default for easy importing
export default FileList;