import ApiService from './ApiService.js';

/**
 * Git Service - Handles Git repository operations through the API
 */
export class GitService extends ApiService {
  constructor() {
    super();
    this.basePath = '/git';
  }

  /**
     * Get git links
     * @param {Object} options - Query options
     */
  async getGitLinks(options = {}) {
    const params = new URLSearchParams(options);
    return this.get(`${this.basePath}/links?${params}`);
  }

  /**
     * Create git link
     * @param {Object} gitLinkData - Git link data
     */
  async createGitLink(gitLinkData) {
    return this.post(`${this.basePath}/links`, gitLinkData);
  }

  /**
     * Get git link by ID
     * @param {number} linkId - Git link ID
     */
  async getGitLink(linkId) {
    return this.get(`${this.basePath}/links/${linkId}`);
  }

  /**
     * Update git link
     * @param {number} linkId - Git link ID
     * @param {Object} gitLinkData - Updated git link data
     */
  async updateGitLink(linkId, gitLinkData) {
    return this.put(`${this.basePath}/links/${linkId}`, gitLinkData);
  }

  /**
     * Delete git link
     * @param {number} linkId - Git link ID
     */
  async deleteGitLink(linkId) {
    return this.delete(`${this.basePath}/links/${linkId}`);
  }

  /**
     * Get git links for a project
     * @param {number} projectId - Project ID
     */
  async getProjectGitLinks(projectId) {
    return this.get(`${this.basePath}/links/project/${projectId}`);
  }

  /**
     * Get git links for a task
     * @param {number} taskId - Task ID
     */
  async getTaskGitLinks(taskId) {
    return this.get(`${this.basePath}/links/task/${taskId}`);
  }

  /**
     * Clone repository
     * @param {Object} cloneData - Clone configuration
     */
  async cloneRepository(cloneData) {
    return this.post(`${this.basePath}/clone`, cloneData);
  }

  /**
     * Get repository status
     * @param {number} linkId - Git link ID
     */
  async getRepositoryStatus(linkId) {
    return this.get(`${this.basePath}/links/${linkId}/status`);
  }

  /**
     * Pull latest changes
     * @param {number} linkId - Git link ID
     */
  async pullChanges(linkId) {
    return this.post(`${this.basePath}/links/${linkId}/pull`);
  }

  /**
     * Push changes
     * @param {number} linkId - Git link ID
     * @param {Object} pushData - Push configuration
     */
  async pushChanges(linkId, pushData = {}) {
    return this.post(`${this.basePath}/links/${linkId}/push`, pushData);
  }

  /**
     * Get commit history
     * @param {number} linkId - Git link ID
     * @param {Object} options - Query options
     */
  async getCommitHistory(linkId, options = {}) {
    const params = new URLSearchParams(options);
    return this.get(`${this.basePath}/links/${linkId}/commits?${params}`);
  }

  /**
     * Get commit details
     * @param {number} linkId - Git link ID
     * @param {string} commitHash - Commit hash
     */
  async getCommit(linkId, commitHash) {
    return this.get(`${this.basePath}/links/${linkId}/commits/${commitHash}`);
  }

  /**
     * Get branches
     * @param {number} linkId - Git link ID
     */
  async getBranches(linkId) {
    return this.get(`${this.basePath}/links/${linkId}/branches`);
  }

  /**
     * Create branch
     * @param {number} linkId - Git link ID
     * @param {Object} branchData - Branch creation data
     */
  async createBranch(linkId, branchData) {
    return this.post(`${this.basePath}/links/${linkId}/branches`, branchData);
  }

  /**
     * Switch branch
     * @param {number} linkId - Git link ID
     * @param {string} branchName - Branch name to switch to
     */
  async switchBranch(linkId, branchName) {
    return this.post(`${this.basePath}/links/${linkId}/branches/${branchName}/checkout`);
  }

  /**
     * Delete branch
     * @param {number} linkId - Git link ID
     * @param {string} branchName - Branch name to delete
     */
  async deleteBranch(linkId, branchName) {
    return this.delete(`${this.basePath}/links/${linkId}/branches/${branchName}`);
  }

  /**
     * Get tags
     * @param {number} linkId - Git link ID
     */
  async getTags(linkId) {
    return this.get(`${this.basePath}/links/${linkId}/tags`);
  }

  /**
     * Create tag
     * @param {number} linkId - Git link ID
     * @param {Object} tagData - Tag creation data
     */
  async createTag(linkId, tagData) {
    return this.post(`${this.basePath}/links/${linkId}/tags`, tagData);
  }

  /**
     * Delete tag
     * @param {number} linkId - Git link ID
     * @param {string} tagName - Tag name to delete
     */
  async deleteTag(linkId, tagName) {
    return this.delete(`${this.basePath}/links/${linkId}/tags/${tagName}`);
  }

  /**
     * Get file content at specific commit
     * @param {number} linkId - Git link ID
     * @param {string} filePath - File path in repository
     * @param {string} commitHash - Commit hash (optional, defaults to HEAD)
     */
  async getFileContent(linkId, filePath, commitHash = 'HEAD') {
    const params = new URLSearchParams({
      path: filePath,
      commit: commitHash
    });
    return this.get(`${this.basePath}/links/${linkId}/file?${params}`);
  }

  /**
     * Get file history
     * @param {number} linkId - Git link ID
     * @param {string} filePath - File path in repository
     */
  async getFileHistory(linkId, filePath) {
    const params = new URLSearchParams({ path: filePath });
    return this.get(`${this.basePath}/links/${linkId}/file/history?${params}`);
  }

  /**
     * Get diff between commits
     * @param {number} linkId - Git link ID
     * @param {string} fromCommit - Source commit hash
     * @param {string} toCommit - Target commit hash
     */
  async getDiff(linkId, fromCommit, toCommit) {
    return this.get(`${this.basePath}/links/${linkId}/diff/${fromCommit}/${toCommit}`);
  }

  /**
     * Get repository statistics
     * @param {number} linkId - Git link ID
     */
  async getRepositoryStats(linkId) {
    return this.get(`${this.basePath}/links/${linkId}/stats`);
  }

  /**
     * Search commits
     * @param {number} linkId - Git link ID
     * @param {string} query - Search query
     * @param {Object} options - Search options
     */
  async searchCommits(linkId, query, options = {}) {
    const params = new URLSearchParams({
      q: query,
      ...options
    });
    return this.get(`${this.basePath}/links/${linkId}/search/commits?${params}`);
  }

  /**
     * Get contributors
     * @param {number} linkId - Git link ID
     */
  async getContributors(linkId) {
    return this.get(`${this.basePath}/links/${linkId}/contributors`);
  }

  /**
     * Get blame information for file
     * @param {number} linkId - Git link ID
     * @param {string} filePath - File path in repository
     * @param {string} commitHash - Commit hash (optional, defaults to HEAD)
     */
  async getBlame(linkId, filePath, commitHash = 'HEAD') {
    const params = new URLSearchParams({
      path: filePath,
      commit: commitHash
    });
    return this.get(`${this.basePath}/links/${linkId}/blame?${params}`);
  }

  /**
     * Validate Git URL
     * @param {string} url - Git repository URL
     */
  async validateGitUrl(url) {
    return this.post(`${this.basePath}/validate-url`, { url });
  }

  /**
     * Test Git connection
     * @param {Object} connectionData - Connection test data
     */
  async testConnection(connectionData) {
    return this.post(`${this.basePath}/test-connection`, connectionData);
  }

  /**
     * Get supported Git providers
     */
  async getSupportedProviders() {
    return this.get(`${this.basePath}/providers`);
  }

  /**
     * Configure webhook
     * @param {number} linkId - Git link ID
     * @param {Object} webhookData - Webhook configuration
     */
  async configureWebhook(linkId, webhookData) {
    return this.post(`${this.basePath}/links/${linkId}/webhook`, webhookData);
  }

  /**
     * Get webhook status
     * @param {number} linkId - Git link ID
     */
  async getWebhookStatus(linkId) {
    return this.get(`${this.basePath}/links/${linkId}/webhook`);
  }

  /**
     * Delete webhook
     * @param {number} linkId - Git link ID
     */
  async deleteWebhook(linkId) {
    return this.delete(`${this.basePath}/links/${linkId}/webhook`);
  }

  /**
     * Sync repository with remote
     * @param {number} linkId - Git link ID
     */
  async syncRepository(linkId) {
    return this.post(`${this.basePath}/links/${linkId}/sync`);
  }

  /**
     * Get sync status
     * @param {number} linkId - Git link ID
     */
  async getSyncStatus(linkId) {
    return this.get(`${this.basePath}/links/${linkId}/sync-status`);
  }

  /**
     * Parse Git URL to extract provider and repository information
     * @param {string} url - Git repository URL
     * @returns {Object|null} Parsed URL information or null if invalid
     */
  static parseGitUrl(url) {
    const patterns = [
      // HTTPS URLs
      /^https?:\/\/([^\/]+)\/([^\/]+)\/([^\/]+?)(\.git)?$/,
      // SSH URLs
      /^git@([^:]+):([^\/]+)\/([^\/]+?)(\.git)?$/,
      // GitHub shorthand
      /^([^\/]+)\/([^\/]+)$/
    ];

    for (const pattern of patterns) {
      const match = url.match(pattern);
      if (match) {
        return {
          provider: match[1] || 'github.com',
          owner: match[2] || match[1],
          repo: match[3] || match[2],
          isSSH: url.startsWith('git@'),
          original: url
        };
      }
    }

    return null;
  }

  /**
     * Format commit message for display
     * @param {string} message - Commit message
     * @param {number} maxLength - Maximum length
     * @returns {string} Formatted message
     */
  static formatCommitMessage(message, maxLength = 72) {
    const lines = message.split('\n');
    const subject = lines[0];

    if (subject.length <= maxLength) {
      return subject;
    }

    return subject.substring(0, maxLength - 3) + '...';
  }

  /**
     * Get commit URL for external viewing
     * @param {Object} gitLink - Git link object
     * @param {string} commitHash - Commit hash
     * @returns {string|null} External commit URL
     */
  static getCommitUrl(gitLink, commitHash) {
    if (!gitLink || !commitHash) {return null;}

    const parsed = this.parseGitUrl(gitLink.url);
    if (!parsed) {return null;}

    const provider = parsed.provider.toLowerCase();

    if (provider.includes('github')) {
      return `https://${parsed.provider}/${parsed.owner}/${parsed.repo}/commit/${commitHash}`;
    } else if (provider.includes('gitlab')) {
      return `https://${parsed.provider}/${parsed.owner}/${parsed.repo}/-/commit/${commitHash}`;
    } else if (provider.includes('bitbucket')) {
      return `https://${parsed.provider}/${parsed.owner}/${parsed.repo}/commits/${commitHash}`;
    }

    return null;
  }
}

export default GitService;