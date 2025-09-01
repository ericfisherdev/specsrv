import ApiService from './ApiService.js';

/**
 * File Service - Handles file operations through the API
 */
export class FileService extends ApiService {
    constructor() {
        super();
        this.basePath = '/files';
    }

    /**
     * Upload files
     * @param {FileList|File[]} files - Files to upload
     * @param {Object} options - Upload options (projectId, taskId, etc.)
     */
    async uploadFiles(files, options = {}) {
        const formData = new FormData();
        
        // Add files to form data
        if (files instanceof FileList || Array.isArray(files)) {
            Array.from(files).forEach(file => {
                formData.append('files[]', file);
            });
        } else {
            formData.append('files[]', files);
        }

        // Add context data
        if (options.projectId) {
            formData.append('project_id', options.projectId);
        }
        if (options.taskId) {
            formData.append('task_id', options.taskId);
        }

        return this.request(`${this.basePath}/upload`, {
            method: 'POST',
            body: formData,
            // Don't set content-type, let browser set it with boundary for multipart
            headers: {}
        });
    }

    /**
     * Get file list
     * @param {Object} filters - Filter options
     */
    async getFiles(filters = {}) {
        const params = new URLSearchParams(filters);
        return this.get(`${this.basePath}?${params}`);
    }

    /**
     * Get file by filename
     * @param {string} filename - File name
     */
    async getFile(filename) {
        return this.get(`${this.basePath}/${filename}`);
    }

    /**
     * Download file
     * @param {string} filename - File name
     * @returns {string} Download URL
     */
    getDownloadUrl(filename) {
        return `${this.baseUrl}${this.basePath}/download/${filename}`;
    }

    /**
     * Download file (triggers browser download)
     * @param {string} filename - File name
     */
    async downloadFile(filename) {
        const url = this.getDownloadUrl(filename);
        const link = document.createElement('a');
        link.href = url;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    /**
     * Preview file content (for text files)
     * @param {string} filename - File name
     */
    async previewFile(filename) {
        return this.get(`${this.basePath}/preview/${filename}`);
    }

    /**
     * Delete file
     * @param {string} filename - File name
     */
    async deleteFile(filename) {
        return this.delete(`${this.basePath}/${filename}`);
    }

    /**
     * Update file metadata
     * @param {string} filename - File name
     * @param {Object} data - Updated data
     */
    async updateFile(filename, data) {
        return this.put(`${this.basePath}/${filename}`, data);
    }

    /**
     * Get files for a specific project
     * @param {number} projectId - Project ID
     */
    async getProjectFiles(projectId) {
        return this.get(`${this.basePath}/project/${projectId}`);
    }

    /**
     * Get files for a specific task
     * @param {number} taskId - Task ID
     */
    async getTaskFiles(taskId) {
        return this.get(`${this.basePath}/task/${taskId}`);
    }

    /**
     * Check if file type is supported for preview
     * @param {string} mimeType - MIME type
     * @returns {boolean}
     */
    static canPreview(mimeType) {
        const supportedTypes = [
            'text/plain',
            'text/markdown',
            'text/html',
            'text/css',
            'text/javascript',
            'application/json'
        ];
        return supportedTypes.includes(mimeType) || mimeType.startsWith('text/');
    }

    /**
     * Get file icon class based on MIME type
     * @param {string} mimeType - MIME type
     * @returns {string} Icon identifier
     */
    static getFileIcon(mimeType) {
        if (mimeType.startsWith('image/')) return 'image';
        if (mimeType === 'application/pdf') return 'pdf';
        if (mimeType.startsWith('text/')) return 'text';
        if (mimeType.startsWith('video/')) return 'video';
        if (mimeType.startsWith('audio/')) return 'audio';
        if (mimeType.includes('zip') || mimeType.includes('archive')) return 'archive';
        if (mimeType.includes('word') || mimeType.includes('document')) return 'document';
        if (mimeType.includes('spreadsheet') || mimeType.includes('excel')) return 'spreadsheet';
        if (mimeType.includes('presentation') || mimeType.includes('powerpoint')) return 'presentation';
        return 'file';
    }

    /**
     * Format file size for display
     * @param {number} bytes - Size in bytes
     * @returns {string} Formatted size
     */
    static formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Validate file before upload
     * @param {File} file - File to validate
     * @param {Object} options - Validation options
     * @returns {Object} Validation result
     */
    static validateFile(file, options = {}) {
        const errors = [];
        const {
            maxSize = 10 * 1024 * 1024, // 10MB default
            allowedTypes = [],
            blockedTypes = ['application/x-msdownload', 'application/x-executable']
        } = options;

        // Size validation
        if (file.size > maxSize) {
            errors.push(`File size (${this.formatFileSize(file.size)}) exceeds maximum allowed (${this.formatFileSize(maxSize)})`);
        }

        // Type validation
        if (allowedTypes.length > 0 && !allowedTypes.includes(file.type)) {
            errors.push(`File type ${file.type} is not allowed`);
        }

        if (blockedTypes.includes(file.type)) {
            errors.push(`File type ${file.type} is not allowed for security reasons`);
        }

        // Name validation
        if (file.name.length > 255) {
            errors.push('File name is too long (max 255 characters)');
        }

        return {
            valid: errors.length === 0,
            errors
        };
    }
}

export default FileService;