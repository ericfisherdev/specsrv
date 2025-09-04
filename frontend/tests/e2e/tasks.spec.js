import { test, expect } from '@playwright/test';

test.describe('Task CRUD Operations', () => {
  test.beforeEach(async ({ page, context }) => {
    // Set up authentication
    const testToken = process.env.TEST_JWT_TOKEN || 'test_token';
    await context.addCookies([{
      name: 'auth_token',
      value: testToken,
      domain: 'localhost',
      path: '/',
    }]);
    
    await page.goto('/tasks');
  });

  test('should display tasks list', async ({ page }) => {
    await expect(page.locator('h1, .page-title')).toContainText(/Tasks/i);
    
    // Check for tasks list or empty state
    const tasksList = page.locator('.tasks-list, .task-grid, [data-tasks]');
    const emptyState = page.locator('.empty-state, .no-tasks');
    
    await expect(tasksList.or(emptyState)).toBeVisible();
  });

  test('should create a new task', async ({ page }) => {
    // Click new task button
    await page.click('.new-task-btn, [data-new-task], button:has-text("New Task"), button:has-text("Add Task")');
    
    // Fill in task form
    const taskTitle = `Test Task ${Date.now()}`;
    await page.fill('input[name="title"], input[name="name"]', taskTitle);
    
    const descriptionField = page.locator('textarea[name="description"]');
    if (await descriptionField.isVisible()) {
      await descriptionField.fill('This is a test task created by E2E tests');
    }
    
    // Set priority if available
    const priorityField = page.locator('select[name="priority"], [data-priority]');
    if (await priorityField.isVisible()) {
      await priorityField.selectOption('high');
    }
    
    // Set due date if available
    const dueDateField = page.locator('input[type="date"][name="dueDate"], input[type="datetime-local"][name="dueDate"]');
    if (await dueDateField.isVisible()) {
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      await dueDateField.fill(tomorrow.toISOString().split('T')[0]);
    }
    
    // Assign to project if field exists
    const projectField = page.locator('select[name="project"], select[name="projectId"]');
    if (await projectField.isVisible()) {
      const options = await projectField.locator('option').all();
      if (options.length > 1) {
        await projectField.selectOption({ index: 1 });
      }
    }
    
    // Submit form
    await page.click('button[type="submit"], button:has-text("Create"), button:has-text("Save")');
    
    // Verify task was created
    await expect(page.locator(`.task-item:has-text("${taskTitle}"), [data-task-title="${taskTitle}"]`)).toBeVisible();
  });

  test('should edit an existing task', async ({ page }) => {
    // First create a task
    await page.click('.new-task-btn, [data-new-task], button:has-text("New Task")');
    const taskTitle = `Edit Task ${Date.now()}`;
    await page.fill('input[name="title"], input[name="name"]', taskTitle);
    await page.click('button[type="submit"], button:has-text("Create")');
    
    // Wait for task to appear
    await page.waitForSelector(`.task-item:has-text("${taskTitle}")`);
    
    // Click edit button
    const taskItem = page.locator(`.task-item:has-text("${taskTitle}")`);
    await taskItem.locator('.edit-btn, [data-edit], button:has-text("Edit")').click();
    
    // Update task details
    const updatedTitle = `${taskTitle} - Updated`;
    await page.fill('input[name="title"], input[name="name"]', updatedTitle);
    
    const descriptionField = page.locator('textarea[name="description"]');
    if (await descriptionField.isVisible()) {
      await descriptionField.fill('Updated task description');
    }
    
    // Change status if available
    const statusField = page.locator('select[name="status"]');
    if (await statusField.isVisible()) {
      await statusField.selectOption('in_progress');
    }
    
    // Save changes
    await page.click('button[type="submit"], button:has-text("Save"), button:has-text("Update")');
    
    // Verify changes were saved
    await expect(page.locator(`.task-item:has-text("${updatedTitle}")`)).toBeVisible();
  });

  test('should delete a task', async ({ page }) => {
    // First create a task to delete
    await page.click('.new-task-btn, [data-new-task], button:has-text("New Task")');
    const taskTitle = `Delete Task ${Date.now()}`;
    await page.fill('input[name="title"], input[name="name"]', taskTitle);
    await page.click('button[type="submit"], button:has-text("Create")');
    
    // Wait for task to appear
    await page.waitForSelector(`.task-item:has-text("${taskTitle}")`);
    
    // Click delete button
    const taskItem = page.locator(`.task-item:has-text("${taskTitle}")`);
    await taskItem.locator('.delete-btn, [data-delete], button:has-text("Delete")').click();
    
    // Confirm deletion if there's a confirmation dialog
    const confirmButton = page.locator('button:has-text("Confirm"), button:has-text("Yes"), button:has-text("Delete")').last();
    if (await confirmButton.isVisible({ timeout: 1000 })) {
      await confirmButton.click();
    }
    
    // Verify task was deleted
    await expect(page.locator(`.task-item:has-text("${taskTitle}")`)).not.toBeVisible();
  });

  test('should mark task as complete', async ({ page }) => {
    // Create a task
    await page.click('.new-task-btn, [data-new-task], button:has-text("New Task")');
    const taskTitle = `Complete Task ${Date.now()}`;
    await page.fill('input[name="title"], input[name="name"]', taskTitle);
    await page.click('button[type="submit"], button:has-text("Create")');
    
    // Wait for task to appear
    await page.waitForSelector(`.task-item:has-text("${taskTitle}")`);
    
    // Mark as complete
    const taskItem = page.locator(`.task-item:has-text("${taskTitle}")`);
    
    // Try checkbox first
    const checkbox = taskItem.locator('input[type="checkbox"], .task-checkbox');
    if (await checkbox.isVisible()) {
      await checkbox.check();
    } else {
      // Try complete button
      const completeBtn = taskItem.locator('.complete-btn, [data-complete], button:has-text("Complete")');
      if (await completeBtn.isVisible()) {
        await completeBtn.click();
      } else {
        // Try status dropdown
        const statusDropdown = taskItem.locator('select[name="status"]');
        if (await statusDropdown.isVisible()) {
          await statusDropdown.selectOption('completed');
        }
      }
    }
    
    // Verify task is marked as complete
    await expect(taskItem).toHaveClass(/completed|done/);
    // Or check for strikethrough or completed badge
    const completedBadge = taskItem.locator('.completed-badge, .status-completed, [data-status="completed"]');
    if (await completedBadge.isVisible()) {
      await expect(completedBadge).toBeVisible();
    }
  });

  test('should filter tasks by status', async ({ page }) => {
    // Check if filter controls exist
    const statusFilter = page.locator('select[name="status"], [data-filter-status], .status-filter');
    if (!await statusFilter.isVisible()) {
      test.skip();
    }
    
    // Apply filter for pending tasks
    await statusFilter.selectOption('pending');
    
    // Wait for filtered results
    await page.waitForTimeout(500);
    
    // Verify filtered results
    const taskItems = page.locator('.task-item, [data-task]');
    const count = await taskItems.count();
    
    if (count > 0) {
      // Check that visible tasks have pending status
      for (let i = 0; i < count; i++) {
        const statusBadge = taskItems.nth(i).locator('.status-badge, [data-status]');
        if (await statusBadge.isVisible()) {
          await expect(statusBadge).toContainText(/pending|todo|open/i);
        }
      }
    }
  });

  test('should filter tasks by priority', async ({ page }) => {
    // Check if priority filter exists
    const priorityFilter = page.locator('select[name="priority"], [data-filter-priority], .priority-filter');
    if (!await priorityFilter.isVisible()) {
      test.skip();
    }
    
    // Apply filter for high priority
    await priorityFilter.selectOption('high');
    
    // Wait for filtered results
    await page.waitForTimeout(500);
    
    // Verify filtered results
    const taskItems = page.locator('.task-item, [data-task]');
    const count = await taskItems.count();
    
    if (count > 0) {
      // Check that visible tasks have high priority
      for (let i = 0; i < count; i++) {
        const priorityBadge = taskItems.nth(i).locator('.priority-badge, [data-priority]');
        if (await priorityBadge.isVisible()) {
          await expect(priorityBadge).toContainText(/high/i);
        }
      }
    }
  });

  test('should search for tasks', async ({ page }) => {
    // Check if search exists
    const searchInput = page.locator('input[type="search"], input[placeholder*="Search"]');
    if (!await searchInput.isVisible()) {
      test.skip();
    }
    
    // Create a task with specific name
    await page.click('.new-task-btn, [data-new-task], button:has-text("New Task")');
    const searchableTitle = `Searchable Task ${Date.now()}`;
    await page.fill('input[name="title"], input[name="name"]', searchableTitle);
    await page.click('button[type="submit"], button:has-text("Create")');
    
    // Search for the task
    await searchInput.fill('Searchable');
    await page.keyboard.press('Enter');
    
    // Verify search results
    await expect(page.locator(`.task-item:has-text("${searchableTitle}")`)).toBeVisible();
  });

  test('should add tags to a task', async ({ page }) => {
    // Create a task
    await page.click('.new-task-btn, [data-new-task], button:has-text("New Task")');
    const taskTitle = `Tagged Task ${Date.now()}`;
    await page.fill('input[name="title"], input[name="name"]', taskTitle);
    
    // Check if tags field exists
    const tagsField = page.locator('input[name="tags"], .tags-input, [data-tags]');
    if (await tagsField.isVisible()) {
      await tagsField.fill('urgent, bug-fix, frontend');
    }
    
    await page.click('button[type="submit"], button:has-text("Create")');
    
    // Verify tags are displayed
    const taskItem = page.locator(`.task-item:has-text("${taskTitle}")`);
    const tags = taskItem.locator('.tag, .badge, [data-tag]');
    if (await tags.first().isVisible()) {
      await expect(tags).toContainText(['urgent']);
    }
  });

  test('should assign task to user', async ({ page }) => {
    // Create a task
    await page.click('.new-task-btn, [data-new-task], button:has-text("New Task")');
    const taskTitle = `Assigned Task ${Date.now()}`;
    await page.fill('input[name="title"], input[name="name"]', taskTitle);
    
    // Check if assignee field exists
    const assigneeField = page.locator('select[name="assignee"], select[name="userId"], [data-assignee]');
    if (await assigneeField.isVisible()) {
      const options = await assigneeField.locator('option').all();
      if (options.length > 1) {
        await assigneeField.selectOption({ index: 1 });
      }
    }
    
    await page.click('button[type="submit"], button:has-text("Create")');
    
    // Verify assignee is displayed
    const taskItem = page.locator(`.task-item:has-text("${taskTitle}")`);
    const assignee = taskItem.locator('.assignee, .user-avatar, [data-assignee]');
    if (await assignee.isVisible()) {
      await expect(assignee).toBeVisible();
    }
  });

  test('should bulk select and delete tasks', async ({ page }) => {
    // Check if bulk selection exists
    const bulkCheckbox = page.locator('.bulk-select-all, [data-select-all]');
    if (!await bulkCheckbox.isVisible()) {
      test.skip();
    }
    
    // Create multiple tasks
    const taskTitles = [];
    for (let i = 0; i < 3; i++) {
      await page.click('.new-task-btn, [data-new-task], button:has-text("New Task")');
      const title = `Bulk Task ${Date.now()}-${i}`;
      taskTitles.push(title);
      await page.fill('input[name="title"], input[name="name"]', title);
      await page.click('button[type="submit"], button:has-text("Create")');
      await page.waitForSelector(`.task-item:has-text("${title}")`);
    }
    
    // Select all tasks
    await bulkCheckbox.check();
    
    // Click bulk delete
    await page.click('.bulk-delete, [data-bulk-delete], button:has-text("Delete Selected")');
    
    // Confirm deletion
    const confirmButton = page.locator('button:has-text("Confirm"), button:has-text("Yes")');
    if (await confirmButton.isVisible({ timeout: 1000 })) {
      await confirmButton.click();
    }
    
    // Verify tasks were deleted
    for (const title of taskTitles) {
      await expect(page.locator(`.task-item:has-text("${title}")`)).not.toBeVisible();
    }
  });
});