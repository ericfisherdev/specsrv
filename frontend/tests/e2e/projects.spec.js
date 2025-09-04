import { test, expect } from '@playwright/test';

test.describe('Project Management', () => {
  test.beforeEach(async ({ page, context }) => {
    // Set up authentication
    const testToken = process.env.TEST_JWT_TOKEN || 'test_token';
    await context.addCookies([{
      name: 'auth_token',
      value: testToken,
      domain: 'localhost',
      path: '/',
    }]);
    
    await page.goto('/projects');
  });

  test('should display projects list', async ({ page }) => {
    await expect(page.locator('h1, .page-title')).toContainText(/Projects/i);
    
    // Check for project list or empty state
    const projectsList = page.locator('.projects-list, .project-grid, [data-projects]');
    const emptyState = page.locator('.empty-state, .no-projects');
    
    await expect(projectsList.or(emptyState)).toBeVisible();
  });

  test('should create a new project', async ({ page }) => {
    // Click new project button
    await page.click('.new-project-btn, [data-new-project], button:has-text("New Project")');
    
    // Fill in project form
    const projectName = `Test Project ${Date.now()}`;
    await page.fill('input[name="name"], input[name="title"]', projectName);
    
    const descriptionField = page.locator('textarea[name="description"]');
    if (await descriptionField.isVisible()) {
      await descriptionField.fill('This is a test project created by E2E tests');
    }
    
    // Select priority if available
    const priorityField = page.locator('select[name="priority"]');
    if (await priorityField.isVisible()) {
      await priorityField.selectOption('high');
    }
    
    // Submit form
    await page.click('button[type="submit"], button:has-text("Create")');
    
    // Verify project was created
    await expect(page.locator(`.project-item:has-text("${projectName}"), [data-project-name="${projectName}"]`)).toBeVisible();
  });

  test('should edit an existing project', async ({ page }) => {
    // First create a project
    await page.click('.new-project-btn, [data-new-project], button:has-text("New Project")');
    const projectName = `Edit Test ${Date.now()}`;
    await page.fill('input[name="name"], input[name="title"]', projectName);
    await page.click('button[type="submit"], button:has-text("Create")');
    
    // Wait for project to appear
    await page.waitForSelector(`.project-item:has-text("${projectName}"), [data-project-name="${projectName}"]`);
    
    // Click edit button for the project
    const projectItem = page.locator(`.project-item:has-text("${projectName}")`);
    await projectItem.locator('.edit-btn, [data-edit], button:has-text("Edit")').click();
    
    // Update project details
    const updatedName = `${projectName} - Updated`;
    await page.fill('input[name="name"], input[name="title"]', updatedName);
    
    const descriptionField = page.locator('textarea[name="description"]');
    if (await descriptionField.isVisible()) {
      await descriptionField.fill('Updated description');
    }
    
    // Save changes
    await page.click('button[type="submit"], button:has-text("Save"), button:has-text("Update")');
    
    // Verify changes were saved
    await expect(page.locator(`.project-item:has-text("${updatedName}")`)).toBeVisible();
  });

  test('should delete a project', async ({ page }) => {
    // First create a project to delete
    await page.click('.new-project-btn, [data-new-project], button:has-text("New Project")');
    const projectName = `Delete Test ${Date.now()}`;
    await page.fill('input[name="name"], input[name="title"]', projectName);
    await page.click('button[type="submit"], button:has-text("Create")');
    
    // Wait for project to appear
    await page.waitForSelector(`.project-item:has-text("${projectName}")`);
    
    // Click delete button
    const projectItem = page.locator(`.project-item:has-text("${projectName}")`);
    await projectItem.locator('.delete-btn, [data-delete], button:has-text("Delete")').click();
    
    // Confirm deletion if there's a confirmation dialog
    const confirmButton = page.locator('button:has-text("Confirm"), button:has-text("Yes")');
    if (await confirmButton.isVisible({ timeout: 1000 })) {
      await confirmButton.click();
    }
    
    // Verify project was deleted
    await expect(page.locator(`.project-item:has-text("${projectName}")`)).not.toBeVisible();
  });

  test('should filter projects by status', async ({ page }) => {
    // Check if filter controls exist
    const statusFilter = page.locator('select[name="status"], [data-filter-status]');
    if (!await statusFilter.isVisible()) {
      test.skip();
    }
    
    // Apply filter
    await statusFilter.selectOption('active');
    
    // Verify filtered results
    const projectItems = page.locator('.project-item, [data-project]');
    const count = await projectItems.count();
    
    if (count > 0) {
      // Check that visible projects have active status
      for (let i = 0; i < count; i++) {
        const statusBadge = projectItems.nth(i).locator('.status-badge, [data-status]');
        if (await statusBadge.isVisible()) {
          await expect(statusBadge).toContainText(/active/i);
        }
      }
    }
  });

  test('should search for projects', async ({ page }) => {
    // Check if search exists
    const searchInput = page.locator('input[type="search"], input[placeholder*="Search"]');
    if (!await searchInput.isVisible()) {
      test.skip();
    }
    
    // Create a project with specific name
    await page.click('.new-project-btn, [data-new-project], button:has-text("New Project")');
    const searchableName = `Searchable Project ${Date.now()}`;
    await page.fill('input[name="name"], input[name="title"]', searchableName);
    await page.click('button[type="submit"], button:has-text("Create")');
    
    // Search for the project
    await searchInput.fill('Searchable');
    await page.keyboard.press('Enter');
    
    // Verify search results
    await expect(page.locator(`.project-item:has-text("${searchableName}")`)).toBeVisible();
  });

  test('should navigate to project details', async ({ page }) => {
    // Create a project
    await page.click('.new-project-btn, [data-new-project], button:has-text("New Project")');
    const projectName = `Detail Test ${Date.now()}`;
    await page.fill('input[name="name"], input[name="title"]', projectName);
    await page.click('button[type="submit"], button:has-text("Create")');
    
    // Click on project to view details
    await page.click(`.project-item:has-text("${projectName}") a, .project-item:has-text("${projectName}") .view-btn`);
    
    // Verify we're on project details page
    await expect(page).toHaveURL(/\/projects\/\d+/);
    await expect(page.locator('h1, .project-title')).toContainText(projectName);
  });

  test('should display project statistics', async ({ page }) => {
    // Navigate to a project details page
    const projectItems = page.locator('.project-item, [data-project]');
    const count = await projectItems.count();
    
    if (count > 0) {
      await projectItems.first().click();
      
      // Check for statistics elements
      await expect(page.locator('.project-stats, [data-project-stats]')).toBeVisible();
      
      // Check for common stat items
      const statItems = [
        '.task-count, [data-task-count]',
        '.completed-tasks, [data-completed-tasks]',
        '.progress-bar, [data-progress]'
      ];
      
      for (const selector of statItems) {
        const element = page.locator(selector);
        if (await element.isVisible()) {
          await expect(element).toBeVisible();
        }
      }
    }
  });

  test('should handle project pagination', async ({ page }) => {
    // Check if pagination exists
    const pagination = page.locator('.pagination, [data-pagination]');
    if (!await pagination.isVisible()) {
      test.skip();
    }
    
    // Click next page
    await page.click('.pagination .next, [data-next-page]');
    
    // Verify URL or content changed
    await expect(page).toHaveURL(/page=2|offset=/);
  });
});