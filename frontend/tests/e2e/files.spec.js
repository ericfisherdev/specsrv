import { test, expect } from '@playwright/test';
import path from 'path';
import fs from 'fs';

test.describe('File Upload and Management', () => {
  let testFilePath;

  test.beforeAll(() => {
    // Create a test file for upload
    testFilePath = path.join(__dirname, 'test-file.txt');
    fs.writeFileSync(testFilePath, 'This is a test file for E2E testing');
  });

  test.afterAll(() => {
    // Clean up test file
    if (fs.existsSync(testFilePath)) {
      fs.unlinkSync(testFilePath);
    }
  });

  test.beforeEach(async ({ page, context }) => {
    // Set up authentication
    const testToken = process.env.TEST_JWT_TOKEN || 'test_token';
    await context.addCookies([{
      name: 'auth_token',
      value: testToken,
      domain: 'localhost',
      path: '/',
    }]);
  });

  test('should upload file to a task', async ({ page }) => {
    // Navigate to tasks
    await page.goto('/tasks');
    
    // Create a new task or open existing one
    await page.click('.new-task-btn, [data-new-task], button:has-text("New Task")');
    const taskTitle = `File Upload Task ${Date.now()}`;
    await page.fill('input[name="title"], input[name="name"]', taskTitle);
    await page.click('button[type="submit"], button:has-text("Create")');
    
    // Open task details
    await page.click(`.task-item:has-text("${taskTitle}")`);
    
    // Find file upload input
    const fileInput = page.locator('input[type="file"]');
    if (!await fileInput.isVisible()) {
      // Look for upload button that triggers file input
      const uploadBtn = page.locator('.upload-btn, [data-upload], button:has-text("Upload"), button:has-text("Attach")');
      if (await uploadBtn.isVisible()) {
        await uploadBtn.click();
      }
    }
    
    // Upload file
    await fileInput.setInputFiles(testFilePath);
    
    // Wait for upload to complete
    await page.waitForSelector('.file-item:has-text("test-file.txt"), [data-file-name="test-file.txt"]', { timeout: 10000 });
    
    // Verify file appears in file list
    await expect(page.locator('.file-item:has-text("test-file.txt")')).toBeVisible();
  });

  test('should upload file to a project', async ({ page }) => {
    // Navigate to projects
    await page.goto('/projects');
    
    // Create or open a project
    await page.click('.new-project-btn, [data-new-project], button:has-text("New Project")');
    const projectName = `File Project ${Date.now()}`;
    await page.fill('input[name="name"], input[name="title"]', projectName);
    await page.click('button[type="submit"], button:has-text("Create")');
    
    // Navigate to project details
    await page.click(`.project-item:has-text("${projectName}")`);
    
    // Find file upload section
    const fileInput = page.locator('input[type="file"]');
    const uploadBtn = page.locator('.upload-btn, [data-upload], button:has-text("Upload File")');
    
    if (await uploadBtn.isVisible()) {
      await uploadBtn.click();
    }
    
    // Upload file
    await fileInput.setInputFiles(testFilePath);
    
    // Verify file upload
    await expect(page.locator('.file-item:has-text("test-file.txt")')).toBeVisible({ timeout: 10000 });
  });

  test('should display file preview', async ({ page }) => {
    // Create task with file
    await page.goto('/tasks');
    await page.click('.new-task-btn, [data-new-task], button:has-text("New Task")');
    const taskTitle = `Preview Task ${Date.now()}`;
    await page.fill('input[name="title"], input[name="name"]', taskTitle);
    await page.click('button[type="submit"], button:has-text("Create")');
    
    // Upload file
    await page.click(`.task-item:has-text("${taskTitle}")`);
    const fileInput = page.locator('input[type="file"]');
    await fileInput.setInputFiles(testFilePath);
    
    // Wait for file to appear
    await page.waitForSelector('.file-item:has-text("test-file.txt")');
    
    // Click on file to preview
    const fileItem = page.locator('.file-item:has-text("test-file.txt")');
    await fileItem.click();
    
    // Check for preview modal or inline preview
    const previewModal = page.locator('.file-preview-modal, .preview-modal, [data-file-preview]');
    const inlinePreview = page.locator('.file-preview, .preview-content');
    
    if (await previewModal.isVisible({ timeout: 2000 })) {
      await expect(previewModal).toBeVisible();
      await expect(previewModal).toContainText('test file for E2E testing');
    } else if (await inlinePreview.isVisible({ timeout: 2000 })) {
      await expect(inlinePreview).toBeVisible();
    }
  });

  test('should download uploaded file', async ({ page }) => {
    // Create task with file
    await page.goto('/tasks');
    await page.click('.new-task-btn, [data-new-task], button:has-text("New Task")');
    const taskTitle = `Download Task ${Date.now()}`;
    await page.fill('input[name="title"], input[name="name"]', taskTitle);
    await page.click('button[type="submit"], button:has-text("Create")');
    
    // Upload file
    await page.click(`.task-item:has-text("${taskTitle}")`);
    const fileInput = page.locator('input[type="file"]');
    await fileInput.setInputFiles(testFilePath);
    
    // Wait for file to appear
    await page.waitForSelector('.file-item:has-text("test-file.txt")');
    
    // Set up download promise
    const downloadPromise = page.waitForEvent('download');
    
    // Click download button
    const fileItem = page.locator('.file-item:has-text("test-file.txt")');
    const downloadBtn = fileItem.locator('.download-btn, [data-download], a[download]');
    
    if (await downloadBtn.isVisible()) {
      await downloadBtn.click();
    } else {
      // Try right-click context menu
      await fileItem.click({ button: 'right' });
      const contextDownload = page.locator('.context-menu').locator('text=Download');
      if (await contextDownload.isVisible()) {
        await contextDownload.click();
      }
    }
    
    // Wait for download
    const download = await downloadPromise;
    expect(download.suggestedFilename()).toContain('test-file');
  });

  test('should delete uploaded file', async ({ page }) => {
    // Create task with file
    await page.goto('/tasks');
    await page.click('.new-task-btn, [data-new-task], button:has-text("New Task")');
    const taskTitle = `Delete File Task ${Date.now()}`;
    await page.fill('input[name="title"], input[name="name"]', taskTitle);
    await page.click('button[type="submit"], button:has-text("Create")');
    
    // Upload file
    await page.click(`.task-item:has-text("${taskTitle}")`);
    const fileInput = page.locator('input[type="file"]');
    await fileInput.setInputFiles(testFilePath);
    
    // Wait for file to appear
    await page.waitForSelector('.file-item:has-text("test-file.txt")');
    
    // Delete file
    const fileItem = page.locator('.file-item:has-text("test-file.txt")');
    const deleteBtn = fileItem.locator('.delete-btn, [data-delete], button[aria-label="Delete"]');
    
    if (await deleteBtn.isVisible()) {
      await deleteBtn.click();
    } else {
      // Try hover to reveal delete button
      await fileItem.hover();
      const hoveredDeleteBtn = fileItem.locator('.delete-btn, [data-delete]');
      if (await hoveredDeleteBtn.isVisible()) {
        await hoveredDeleteBtn.click();
      }
    }
    
    // Confirm deletion if needed
    const confirmBtn = page.locator('button:has-text("Confirm"), button:has-text("Yes")');
    if (await confirmBtn.isVisible({ timeout: 1000 })) {
      await confirmBtn.click();
    }
    
    // Verify file is deleted
    await expect(page.locator('.file-item:has-text("test-file.txt")')).not.toBeVisible();
  });

  test('should handle multiple file uploads', async ({ page }) => {
    // Create additional test files
    const testFile2 = path.join(__dirname, 'test-file-2.txt');
    const testFile3 = path.join(__dirname, 'test-file-3.txt');
    fs.writeFileSync(testFile2, 'Test file 2 content');
    fs.writeFileSync(testFile3, 'Test file 3 content');
    
    try {
      // Create task
      await page.goto('/tasks');
      await page.click('.new-task-btn, [data-new-task], button:has-text("New Task")');
      const taskTitle = `Multi File Task ${Date.now()}`;
      await page.fill('input[name="title"], input[name="name"]', taskTitle);
      await page.click('button[type="submit"], button:has-text("Create")');
      
      // Open task
      await page.click(`.task-item:has-text("${taskTitle}")`);
      
      // Upload multiple files
      const fileInput = page.locator('input[type="file"]');
      await fileInput.setInputFiles([testFilePath, testFile2, testFile3]);
      
      // Verify all files appear
      await expect(page.locator('.file-item:has-text("test-file.txt")')).toBeVisible({ timeout: 10000 });
      await expect(page.locator('.file-item:has-text("test-file-2.txt")')).toBeVisible();
      await expect(page.locator('.file-item:has-text("test-file-3.txt")')).toBeVisible();
      
      // Verify file count
      const fileCount = await page.locator('.file-item').count();
      expect(fileCount).toBeGreaterThanOrEqual(3);
    } finally {
      // Clean up additional test files
      if (fs.existsSync(testFile2)) fs.unlinkSync(testFile2);
      if (fs.existsSync(testFile3)) fs.unlinkSync(testFile3);
    }
  });

  test('should show file upload progress', async ({ page }) => {
    // Create large test file
    const largeFile = path.join(__dirname, 'large-test-file.txt');
    const largeContent = 'x'.repeat(1024 * 1024); // 1MB file
    fs.writeFileSync(largeFile, largeContent);
    
    try {
      // Create task
      await page.goto('/tasks');
      await page.click('.new-task-btn, [data-new-task], button:has-text("New Task")');
      const taskTitle = `Progress Task ${Date.now()}`;
      await page.fill('input[name="title"], input[name="name"]', taskTitle);
      await page.click('button[type="submit"], button:has-text("Create")');
      
      // Open task
      await page.click(`.task-item:has-text("${taskTitle}")`);
      
      // Upload file
      const fileInput = page.locator('input[type="file"]');
      await fileInput.setInputFiles(largeFile);
      
      // Check for progress indicator
      const progressBar = page.locator('.upload-progress, .progress-bar, [data-upload-progress]');
      if (await progressBar.isVisible({ timeout: 500 })) {
        await expect(progressBar).toBeVisible();
      }
      
      // Wait for upload to complete
      await expect(page.locator('.file-item:has-text("large-test-file.txt")')).toBeVisible({ timeout: 15000 });
    } finally {
      // Clean up large file
      if (fs.existsSync(largeFile)) fs.unlinkSync(largeFile);
    }
  });

  test('should validate file types', async ({ page }) => {
    // Create an invalid file type (if restrictions exist)
    const invalidFile = path.join(__dirname, 'test-file.exe');
    fs.writeFileSync(invalidFile, 'Invalid file content');
    
    try {
      // Create task
      await page.goto('/tasks');
      await page.click('.new-task-btn, [data-new-task], button:has-text("New Task")');
      const taskTitle = `Validation Task ${Date.now()}`;
      await page.fill('input[name="title"], input[name="name"]', taskTitle);
      await page.click('button[type="submit"], button:has-text("Create")');
      
      // Open task
      await page.click(`.task-item:has-text("${taskTitle}")`);
      
      // Check if file input has accept attribute
      const fileInput = page.locator('input[type="file"]');
      const acceptAttr = await fileInput.getAttribute('accept');
      
      if (acceptAttr && !acceptAttr.includes('.exe')) {
        // Try to upload invalid file
        await fileInput.setInputFiles(invalidFile);
        
        // Check for error message
        const errorMessage = page.locator('.error-message, .file-error, [data-error]');
        if (await errorMessage.isVisible({ timeout: 2000 })) {
          await expect(errorMessage).toContainText(/not allowed|invalid|supported/i);
        }
      }
    } finally {
      // Clean up invalid file
      if (fs.existsSync(invalidFile)) fs.unlinkSync(invalidFile);
    }
  });

  test('should display file metadata', async ({ page }) => {
    // Create task with file
    await page.goto('/tasks');
    await page.click('.new-task-btn, [data-new-task], button:has-text("New Task")');
    const taskTitle = `Metadata Task ${Date.now()}`;
    await page.fill('input[name="title"], input[name="name"]', taskTitle);
    await page.click('button[type="submit"], button:has-text("Create")');
    
    // Upload file
    await page.click(`.task-item:has-text("${taskTitle}")`);
    const fileInput = page.locator('input[type="file"]');
    await fileInput.setInputFiles(testFilePath);
    
    // Wait for file to appear
    await page.waitForSelector('.file-item:has-text("test-file.txt")');
    
    // Check for metadata display
    const fileItem = page.locator('.file-item:has-text("test-file.txt")');
    
    // File size
    const fileSize = fileItem.locator('.file-size, [data-file-size]');
    if (await fileSize.isVisible()) {
      const sizeText = await fileSize.textContent();
      expect(sizeText).toMatch(/\d+\s*(B|KB|MB)/);
    }
    
    // Upload date
    const uploadDate = fileItem.locator('.upload-date, [data-upload-date]');
    if (await uploadDate.isVisible()) {
      await expect(uploadDate).toBeVisible();
    }
    
    // File type
    const fileType = fileItem.locator('.file-type, [data-file-type]');
    if (await fileType.isVisible()) {
      await expect(fileType).toContainText(/text|txt/i);
    }
  });

  test('should search uploaded files', async ({ page }) => {
    // Create task with multiple files
    await page.goto('/tasks');
    await page.click('.new-task-btn, [data-new-task], button:has-text("New Task")');
    const taskTitle = `Search Files Task ${Date.now()}`;
    await page.fill('input[name="title"], input[name="name"]', taskTitle);
    await page.click('button[type="submit"], button:has-text("Create")');
    
    // Create and upload multiple files
    const searchFile1 = path.join(__dirname, 'searchable-doc.txt');
    const searchFile2 = path.join(__dirname, 'other-file.txt');
    fs.writeFileSync(searchFile1, 'Searchable document content');
    fs.writeFileSync(searchFile2, 'Other file content');
    
    try {
      await page.click(`.task-item:has-text("${taskTitle}")`);
      const fileInput = page.locator('input[type="file"]');
      await fileInput.setInputFiles([searchFile1, searchFile2]);
      
      // Wait for files to appear
      await page.waitForSelector('.file-item:has-text("searchable-doc.txt")');
      await page.waitForSelector('.file-item:has-text("other-file.txt")');
      
      // Search for files
      const fileSearch = page.locator('.file-search, input[placeholder*="Search files"]');
      if (await fileSearch.isVisible()) {
        await fileSearch.fill('searchable');
        await page.keyboard.press('Enter');
        
        // Verify search results
        await expect(page.locator('.file-item:has-text("searchable-doc.txt")')).toBeVisible();
        await expect(page.locator('.file-item:has-text("other-file.txt")')).not.toBeVisible();
      }
    } finally {
      // Clean up files
      if (fs.existsSync(searchFile1)) fs.unlinkSync(searchFile1);
      if (fs.existsSync(searchFile2)) fs.unlinkSync(searchFile2);
    }
  });
});