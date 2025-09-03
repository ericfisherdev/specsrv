/**
 * FileUpload Component
 * Handles drag-and-drop file uploads with progress tracking
 */
export class FileUpload {
  constructor(container, options = {}) {
    this.container = container;
    this.options = {
      uploadUrl: '/api/v1/files/upload',
      deleteUrl: '/api/v1/files',
      downloadUrl: '/api/v1/files/download',
      acceptedTypes: 'image/*,.pdf,.md,.txt',
      maxFileSize: 10 * 1024 * 1024, // 10MB
      projectId: null,
      taskId: null,
      ...options
    };

    this.state = {
      files: [],
      errors: [],
      uploading: false,
      uploadProgress: 0,
      uploadStatus: 'Preparing upload...',
      currentFiles: [],
      uploadSpeed: 0,
      uploadETA: 0,
      uploadStartTime: 0,
      totalBytes: 0,
      uploadedBytes: 0,
      isDragging: false
    };

    this.init();
  }

  init() {
    this.render();
    this.bindEvents();
  }

  render() {
    this.container.innerHTML = `
            <div
                class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors file-upload-area"
                data-dragging="false"
            >
                ${this.renderUploadArea()}
                ${this.renderUploadProgress()}
                ${this.renderFileList()}
                ${this.renderErrors()}
            </div>
        `;
  }

  renderUploadArea() {
    return `
            <div class="upload-area" style="display: ${!this.state.uploading && this.state.files.length === 0 ? 'block' : 'none'}">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <div class="mt-4">
                    <label for="file-upload-${this.generateId()}" class="cursor-pointer">
                        <span class="mt-2 block text-sm font-medium text-gray-900">
                            Drop files here or click to browse
                        </span>
                        <input
                            id="file-upload-${this.generateId()}"
                            name="files[]"
                            type="file"
                            class="sr-only file-input"
                            multiple
                            accept="${this.options.acceptedTypes}"
                        >
                    </label>
                    <p class="mt-1 text-xs text-gray-500">
                        PNG, JPG, PDF, MD, TXT up to ${this.formatFileSize(this.options.maxFileSize)}
                    </p>
                </div>
            </div>
        `;
  }

  renderUploadProgress() {
    return `
            <div class="upload-progress" style="display: ${this.state.uploading ? 'block' : 'none'}">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-900 upload-status">${this.state.uploadStatus}</span>
                        </div>
                        <span class="text-xs text-gray-500 upload-progress-text">${this.state.uploadProgress}%</span>
                    </div>

                    <!-- Overall Progress Bar -->
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300 progress-bar" style="width: ${this.state.uploadProgress}%"></div>
                    </div>

                    <!-- Individual File Progress -->
                    <div class="current-files space-y-2" style="display: ${this.state.currentFiles.length > 0 ? 'block' : 'none'}">
                        ${this.state.currentFiles.map(file => this.renderCurrentFile(file)).join('')}
                    </div>

                    <!-- Upload Speed and ETA -->
                    <div class="upload-stats flex justify-between text-xs text-gray-500" style="display: ${this.state.uploadSpeed > 0 ? 'flex' : 'none'}">
                        <span>Speed: ${this.formatSpeed(this.state.uploadSpeed)}</span>
                        <span style="display: ${this.state.uploadETA > 0 ? 'inline' : 'none'}">ETA: ${this.formatTime(this.state.uploadETA)}</span>
                    </div>
                </div>
            </div>
        `;
  }

  renderCurrentFile(file) {
    return `
            <div class="flex items-center space-x-3 p-2 bg-gray-50 rounded">
                <div class="flex-shrink-0">
                    <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">${file.name}</p>
                    <div class="flex items-center space-x-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-1">
                            <div class="bg-blue-600 h-1 rounded-full transition-all duration-300" style="width: ${file.progress || 0}%"></div>
                        </div>
                        <span class="text-xs text-gray-500">${this.formatFileSize(file.size)}</span>
                    </div>
                </div>
                <div class="flex-shrink-0">
                    ${file.progress === 100 ? '<span class="text-green-500"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg></span>' : ''}
                    ${file.error ? '<span class="text-red-500"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg></span>' : ''}
                </div>
            </div>
        `;
  }

  renderFileList() {
    if (this.state.files.length === 0 || this.state.uploading) {
      return '';
    }

    return `
            <div class="mt-4 space-y-2 uploaded-files">
                <h4 class="text-sm font-medium text-gray-900 text-left">Uploaded Files:</h4>
                ${this.state.files.map(file => this.renderUploadedFile(file)).join('')}
            </div>
        `;
  }

  renderUploadedFile(file) {
    return `
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-3">
                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-left">
                        <p class="text-sm font-medium text-gray-900">${file.original_name || file.filename}</p>
                        <p class="text-xs text-gray-500">${this.formatFileSize(file.size)}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <a
                        href="${this.options.downloadUrl}/${file.filename}"
                        class="text-blue-600 hover:text-blue-500 text-sm"
                        download
                    >
                        Download
                    </a>
                    <button
                        class="text-red-600 hover:text-red-500 text-sm delete-file-btn"
                        data-filename="${file.filename}"
                    >
                        Delete
                    </button>
                </div>
            </div>
        `;
  }

  renderErrors() {
    if (this.state.errors.length === 0) {
      return '';
    }

    return `
            <div class="mt-4 space-y-2 upload-errors">
${this.state.errors.map(error => '<div class="p-3 bg-red-50 border border-red-200 rounded-lg"><div class="flex"><svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg><div class="ml-3"><p class="text-sm text-red-800"><span class="font-medium">' + error.filename + '</span>: <span>' + error.error + '</span></p></div></div></div>').join('')}
            </div>
        `;
  }

  bindEvents() {
    const uploadArea = this.container.querySelector('.file-upload-area');
    const fileInput = this.container.querySelector('.file-input');

    // Drag and drop events
    uploadArea.addEventListener('dragover', (e) => {
      e.preventDefault();
      this.state.isDragging = true;
      uploadArea.classList.add('border-blue-500', 'bg-blue-50');
    });

    uploadArea.addEventListener('dragleave', (e) => {
      e.preventDefault();
      this.state.isDragging = false;
      uploadArea.classList.remove('border-blue-500', 'bg-blue-50');
    });

    uploadArea.addEventListener('drop', (e) => {
      e.preventDefault();
      this.state.isDragging = false;
      uploadArea.classList.remove('border-blue-500', 'bg-blue-50');

      const files = Array.from(e.dataTransfer.files);
      this.uploadFiles(files);
    });

    // File input change
    if (fileInput) {
      fileInput.addEventListener('change', (e) => {
        const files = Array.from(e.target.files);
        this.uploadFiles(files);
      });
    }

    // Delete file buttons (event delegation)
    this.container.addEventListener('click', (e) => {
      if (e.target.classList.contains('delete-file-btn')) {
        const filename = e.target.dataset.filename;
        this.removeFile(filename);
      }
    });
  }

  async uploadFiles(files) {
    if (!files || files.length === 0) {return;}

    // Validate files
    const validFiles = [];
    const errors = [];

    files.forEach(file => {
      if (file.size > this.options.maxFileSize) {
        errors.push({
          filename: file.name,
          error: `File too large (max ${this.formatFileSize(this.options.maxFileSize)})`
        });
      } else {
        validFiles.push(file);
      }
    });

    if (errors.length > 0) {
      this.state.errors = errors;
      this.render();
      return;
    }

    this.state.uploading = true;
    this.state.uploadProgress = 0;
    this.state.uploadStatus = 'Preparing upload...';
    this.state.errors = [];
    this.state.uploadStartTime = Date.now();
    this.state.totalBytes = validFiles.reduce((total, file) => total + file.size, 0);
    this.state.uploadedBytes = 0;

    // Initialize currentFiles with progress tracking
    this.state.currentFiles = validFiles.map(file => ({
      name: file.name,
      size: file.size,
      progress: 0,
      error: false
    }));

    this.render();

    try {
      const result = await this.performUpload(validFiles);

      if (result.success && result.uploaded_files) {
        this.state.files.push(...result.uploaded_files);
        this.state.uploadStatus = `Successfully uploaded ${result.uploaded_files.length} file(s)`;

        // Dispatch success event
        this.container.dispatchEvent(new CustomEvent('files-uploaded', {
          detail: { files: result.uploaded_files }
        }));
      }

      if (result.errors) {
        this.state.errors.push(...result.errors);

        // Mark failed files
        result.errors.forEach(error => {
          const file = this.state.currentFiles.find(f => f.name === error.filename);
          if (file) {
            file.error = true;
            file.progress = 0;
          }
        });
      }

      this.state.uploadProgress = 100;
      this.render();

    } catch (error) {
      this.state.errors.push({
        filename: 'Upload',
        error: error.message || 'Upload failed'
      });

      // Mark all files as failed
      this.state.currentFiles.forEach(file => {
        file.error = true;
        file.progress = 0;
      });

      this.render();
    } finally {
      setTimeout(() => {
        this.state.uploading = false;
        this.state.uploadProgress = 0;
        this.state.currentFiles = [];
        this.state.uploadSpeed = 0;
        this.state.uploadETA = 0;
        this.render();
      }, 2000);
    }
  }

  performUpload(files) {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      const formData = new FormData();

      files.forEach(file => {
        formData.append('files[]', file);
      });

      // Add context data
      if (this.options.projectId) {
        formData.append('project_id', this.options.projectId);
      }
      if (this.options.taskId) {
        formData.append('task_id', this.options.taskId);
      }

      // Track upload progress
      xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
          const percentComplete = (e.loaded / e.total) * 100;
          this.state.uploadProgress = Math.round(percentComplete);
          this.state.uploadedBytes = e.loaded;

          // Calculate upload speed and ETA
          const elapsed = (Date.now() - this.state.uploadStartTime) / 1000; // seconds
          if (elapsed > 1) {
            this.state.uploadSpeed = this.state.uploadedBytes / elapsed; // bytes per second
            const remaining = this.state.totalBytes - this.state.uploadedBytes;
            this.state.uploadETA = remaining / this.state.uploadSpeed; // seconds remaining
          }

          // Update individual file progress (simplified)
          const progressPerFile = percentComplete / this.state.currentFiles.length;
          this.state.currentFiles.forEach((file, index) => {
            if (percentComplete >= (index + 1) * progressPerFile) {
              file.progress = 100;
            } else if (percentComplete > index * progressPerFile) {
              file.progress = Math.round(percentComplete - (index * progressPerFile));
            }
          });

          // Update status message
          if (percentComplete < 50) {
            this.state.uploadStatus = 'Uploading files...';
          } else if (percentComplete < 90) {
            this.state.uploadStatus = 'Processing files...';
          } else {
            this.state.uploadStatus = 'Finalizing upload...';
          }

          this.updateProgress();
        }
      });

      xhr.addEventListener('load', () => {
        try {
          const result = JSON.parse(xhr.responseText);
          resolve(result);
        } catch (error) {
          reject(new Error('Invalid server response'));
        }
      });

      xhr.addEventListener('error', () => {
        reject(new Error('Network error occurred'));
      });

      xhr.open('POST', this.options.uploadUrl);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.send(formData);
    });
  }

  updateProgress() {
    const progressBar = this.container.querySelector('.progress-bar');
    const progressText = this.container.querySelector('.upload-progress-text');
    const statusElement = this.container.querySelector('.upload-status');
    const statsElement = this.container.querySelector('.upload-stats');

    if (progressBar) {
      progressBar.style.width = `${this.state.uploadProgress}%`;
    }

    if (progressText) {
      progressText.textContent = `${this.state.uploadProgress}%`;
    }

    if (statusElement) {
      statusElement.textContent = this.state.uploadStatus;
    }

    if (statsElement) {
      statsElement.style.display = this.state.uploadSpeed > 0 ? 'flex' : 'none';
      statsElement.innerHTML = `
                <span>Speed: ${this.formatSpeed(this.state.uploadSpeed)}</span>
                <span style="display: ${this.state.uploadETA > 0 ? 'inline' : 'none'}">ETA: ${this.formatTime(this.state.uploadETA)}</span>
            `;
    }

    // Update current files progress
    const currentFilesContainer = this.container.querySelector('.current-files');
    if (currentFilesContainer) {
      currentFilesContainer.innerHTML = this.state.currentFiles.map(file => this.renderCurrentFile(file)).join('');
    }
  }

  async removeFile(filename) {
    if (!confirm('Are you sure you want to delete this file?')) {
      return;
    }

    try {
      const response = await fetch(`${this.options.deleteUrl}/${filename}`, {
        method: 'DELETE',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json'
        }
      });

      const result = await response.json();

      if (result.success || response.ok) {
        this.state.files = this.state.files.filter(file => file.filename !== filename);
        this.render();

        // Dispatch delete event
        this.container.dispatchEvent(new CustomEvent('file-deleted', {
          detail: { filename }
        }));
      } else {
        alert('Failed to delete file');
      }

    } catch (error) {
      console.error('Failed to delete file:', error);
      alert('Error deleting file');
    }
  }

  // Utility methods
  generateId() {
    return Math.random().toString(36).substr(2, 9);
  }

  formatFileSize(bytes) {
    if (bytes === 0) {return '0 Bytes';}
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  formatSpeed(bytesPerSecond) {
    if (bytesPerSecond === 0) {return '0 B/s';}
    const k = 1024;
    const sizes = ['B/s', 'KB/s', 'MB/s', 'GB/s'];
    const i = Math.floor(Math.log(bytesPerSecond) / Math.log(k));
    return parseFloat((bytesPerSecond / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
  }

  formatTime(seconds) {
    if (seconds < 60) {return Math.round(seconds) + 's';}
    if (seconds < 3600) {return Math.floor(seconds / 60) + 'm ' + Math.round(seconds % 60) + 's';}
    return Math.floor(seconds / 3600) + 'h ' + Math.floor((seconds % 3600) / 60) + 'm';
  }

  // Public methods
  setFiles(files) {
    this.state.files = files;
    this.render();
  }

  getFiles() {
    return this.state.files;
  }

  clearErrors() {
    this.state.errors = [];
    this.render();
  }
}

// Export as default for easy importing
export default FileUpload;