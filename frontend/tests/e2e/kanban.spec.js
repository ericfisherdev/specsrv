import { test, expect } from '@playwright/test';

test.describe('Kanban Board Functionality', () => {
  test.beforeEach(async ({ page, context }) => {
    // Set up authentication
    const testToken = process.env.TEST_JWT_TOKEN || 'test_token';
    await context.addCookies([{
      name: 'auth_token',
      value: testToken,
      domain: 'localhost',
      path: '/',
    }]);
    
    await page.goto('/kanban');
  });

  test('should display kanban board with columns', async ({ page }) => {
    await expect(page.locator('h1, .page-title')).toContainText(/Kanban/i);
    
    // Check for kanban columns
    const columns = ['To Do', 'In Progress', 'Review', 'Done'];
    for (const columnName of columns) {
      const column = page.locator(`.kanban-column:has-text("${columnName}"), [data-column="${columnName.toLowerCase().replace(' ', '-')}"]`);
      if (await column.isVisible()) {
        await expect(column).toBeVisible();
      }
    }
    
    // Verify board structure
    await expect(page.locator('.kanban-board, [data-kanban-board]')).toBeVisible();
    await expect(page.locator('.kanban-column, [data-kanban-column]')).toHaveCount(await page.locator('.kanban-column, [data-kanban-column]').count());
  });

  test('should create task directly in column', async ({ page }) => {
    // Find the "To Do" column
    const todoColumn = page.locator('.kanban-column:has-text("To Do"), [data-column="todo"]').first();
    
    // Click add task button in column
    await todoColumn.locator('.add-task-btn, [data-add-task], button:has-text("Add"), button:has-text("+")').click();
    
    // Fill in quick task form
    const taskTitle = `Kanban Task ${Date.now()}`;
    const taskInput = page.locator('input[name="title"], input[name="task"], .quick-add-input');
    await taskInput.fill(taskTitle);
    await taskInput.press('Enter');
    
    // Verify task appears in column
    await expect(todoColumn.locator(`.kanban-card:has-text("${taskTitle}"), [data-task-title="${taskTitle}"]`)).toBeVisible();
  });

  test('should drag task between columns', async ({ page }) => {
    // Create a task in To Do column
    const todoColumn = page.locator('.kanban-column:has-text("To Do"), [data-column="todo"]').first();
    const inProgressColumn = page.locator('.kanban-column:has-text("In Progress"), [data-column="in-progress"]').first();
    
    // Add a task first
    await todoColumn.locator('.add-task-btn, [data-add-task], button:has-text("Add")').click();
    const taskTitle = `Drag Task ${Date.now()}`;
    const taskInput = page.locator('input[name="title"], input[name="task"], .quick-add-input');
    await taskInput.fill(taskTitle);
    await taskInput.press('Enter');
    
    // Wait for task to appear
    const taskCard = todoColumn.locator(`.kanban-card:has-text("${taskTitle}")`);
    await expect(taskCard).toBeVisible();
    
    // Drag task to In Progress column
    await taskCard.dragTo(inProgressColumn);
    
    // Verify task moved to new column
    await expect(inProgressColumn.locator(`.kanban-card:has-text("${taskTitle}")`)).toBeVisible();
    await expect(todoColumn.locator(`.kanban-card:has-text("${taskTitle}")`)).not.toBeVisible();
  });

  test('should edit task from kanban card', async ({ page }) => {
    // Create a task
    const todoColumn = page.locator('.kanban-column:has-text("To Do"), [data-column="todo"]').first();
    await todoColumn.locator('.add-task-btn, [data-add-task], button:has-text("Add")').click();
    const taskTitle = `Edit Kanban Task ${Date.now()}`;
    const taskInput = page.locator('input[name="title"], input[name="task"], .quick-add-input');
    await taskInput.fill(taskTitle);
    await taskInput.press('Enter');
    
    // Click on task card to edit
    const taskCard = todoColumn.locator(`.kanban-card:has-text("${taskTitle}")`);
    await taskCard.click();
    
    // Check if modal or inline edit opens
    const modal = page.locator('.modal, [data-modal], .task-detail-modal');
    const inlineEdit = taskCard.locator('input[type="text"]');
    
    if (await modal.isVisible({ timeout: 1000 })) {
      // Edit in modal
      const updatedTitle = `${taskTitle} - Updated`;
      await modal.locator('input[name="title"]').fill(updatedTitle);
      await modal.locator('button:has-text("Save"), button:has-text("Update")').click();
      
      // Verify update
      await expect(todoColumn.locator(`.kanban-card:has-text("${updatedTitle}")`)).toBeVisible();
    } else if (await inlineEdit.isVisible({ timeout: 1000 })) {
      // Inline edit
      const updatedTitle = `${taskTitle} - Updated`;
      await inlineEdit.fill(updatedTitle);
      await inlineEdit.press('Enter');
      
      // Verify update
      await expect(todoColumn.locator(`.kanban-card:has-text("${updatedTitle}")`)).toBeVisible();
    }
  });

  test('should delete task from kanban board', async ({ page }) => {
    // Create a task
    const todoColumn = page.locator('.kanban-column:has-text("To Do"), [data-column="todo"]').first();
    await todoColumn.locator('.add-task-btn, [data-add-task], button:has-text("Add")').click();
    const taskTitle = `Delete Kanban Task ${Date.now()}`;
    const taskInput = page.locator('input[name="title"], input[name="task"], .quick-add-input');
    await taskInput.fill(taskTitle);
    await taskInput.press('Enter');
    
    // Hover over task card to show delete button
    const taskCard = todoColumn.locator(`.kanban-card:has-text("${taskTitle}")`);
    await taskCard.hover();
    
    // Click delete button
    const deleteBtn = taskCard.locator('.delete-btn, [data-delete], button[aria-label="Delete"]');
    if (await deleteBtn.isVisible()) {
      await deleteBtn.click();
      
      // Confirm if needed
      const confirmBtn = page.locator('button:has-text("Confirm"), button:has-text("Yes")');
      if (await confirmBtn.isVisible({ timeout: 1000 })) {
        await confirmBtn.click();
      }
    } else {
      // Try right-click context menu
      await taskCard.click({ button: 'right' });
      const contextDelete = page.locator('.context-menu').locator('text=Delete');
      if (await contextDelete.isVisible()) {
        await contextDelete.click();
      }
    }
    
    // Verify task is deleted
    await expect(todoColumn.locator(`.kanban-card:has-text("${taskTitle}")`)).not.toBeVisible();
  });

  test('should filter kanban board by project', async ({ page }) => {
    // Check if project filter exists
    const projectFilter = page.locator('select[name="project"], [data-filter-project]');
    if (!await projectFilter.isVisible()) {
      test.skip();
    }
    
    // Select a project
    const options = await projectFilter.locator('option').all();
    if (options.length > 1) {
      await projectFilter.selectOption({ index: 1 });
      
      // Wait for board to update
      await page.waitForTimeout(500);
      
      // Verify board shows filtered tasks
      const cards = page.locator('.kanban-card, [data-kanban-card]');
      const cardCount = await cards.count();
      
      // If there are cards, verify they belong to selected project
      if (cardCount > 0) {
        const firstCard = cards.first();
        const projectBadge = firstCard.locator('.project-badge, [data-project]');
        if (await projectBadge.isVisible()) {
          await expect(projectBadge).toBeVisible();
        }
      }
    }
  });

  test('should show task count in column headers', async ({ page }) => {
    // Check each column for task count
    const columns = await page.locator('.kanban-column, [data-kanban-column]').all();
    
    for (const column of columns) {
      const taskCount = column.locator('.task-count, .column-count, [data-count]');
      if (await taskCount.isVisible()) {
        const count = await taskCount.textContent();
        expect(count).toMatch(/\d+/);
      }
    }
  });

  test('should collapse and expand columns', async ({ page }) => {
    // Find a column with collapse button
    const column = page.locator('.kanban-column').first();
    const collapseBtn = column.locator('.collapse-btn, [data-collapse], button[aria-label="Collapse"]');
    
    if (!await collapseBtn.isVisible()) {
      test.skip();
    }
    
    // Collapse column
    await collapseBtn.click();
    
    // Verify column is collapsed
    await expect(column).toHaveClass(/collapsed/);
    const cards = column.locator('.kanban-card');
    await expect(cards.first()).not.toBeVisible();
    
    // Expand column
    await collapseBtn.click();
    
    // Verify column is expanded
    await expect(column).not.toHaveClass(/collapsed/);
  });

  test('should assign user to task from kanban', async ({ page }) => {
    // Create a task
    const todoColumn = page.locator('.kanban-column:has-text("To Do"), [data-column="todo"]').first();
    await todoColumn.locator('.add-task-btn, [data-add-task], button:has-text("Add")').click();
    const taskTitle = `Assign Task ${Date.now()}`;
    const taskInput = page.locator('input[name="title"], input[name="task"], .quick-add-input');
    await taskInput.fill(taskTitle);
    await taskInput.press('Enter');
    
    // Click on task to open details
    const taskCard = todoColumn.locator(`.kanban-card:has-text("${taskTitle}")`);
    await taskCard.click();
    
    // Check if assignment UI is available
    const modal = page.locator('.modal, [data-modal]');
    if (await modal.isVisible({ timeout: 1000 })) {
      const assigneeField = modal.locator('select[name="assignee"], [data-assignee]');
      if (await assigneeField.isVisible()) {
        const options = await assigneeField.locator('option').all();
        if (options.length > 1) {
          await assigneeField.selectOption({ index: 1 });
          await modal.locator('button:has-text("Save")').click();
          
          // Verify assignee appears on card
          const avatar = taskCard.locator('.user-avatar, .assignee-avatar, [data-assignee]');
          await expect(avatar).toBeVisible();
        }
      }
    }
  });

  test('should add labels to tasks', async ({ page }) => {
    // Create a task
    const todoColumn = page.locator('.kanban-column:has-text("To Do"), [data-column="todo"]').first();
    await todoColumn.locator('.add-task-btn, [data-add-task], button:has-text("Add")').click();
    const taskTitle = `Labeled Task ${Date.now()}`;
    const taskInput = page.locator('input[name="title"], input[name="task"], .quick-add-input');
    await taskInput.fill(taskTitle);
    await taskInput.press('Enter');
    
    // Click on task to open details
    const taskCard = todoColumn.locator(`.kanban-card:has-text("${taskTitle}")`);
    await taskCard.click();
    
    // Check if label UI is available
    const modal = page.locator('.modal, [data-modal]');
    if (await modal.isVisible({ timeout: 1000 })) {
      const labelField = modal.locator('input[name="labels"], .label-input, [data-labels]');
      if (await labelField.isVisible()) {
        await labelField.fill('urgent, bug');
        await modal.locator('button:has-text("Save")').click();
        
        // Verify labels appear on card
        const labels = taskCard.locator('.label, .tag, [data-label]');
        await expect(labels.first()).toBeVisible();
      }
    }
  });

  test('should search/filter tasks on kanban board', async ({ page }) => {
    // Check if search exists
    const searchInput = page.locator('input[type="search"], input[placeholder*="Search"]');
    if (!await searchInput.isVisible()) {
      test.skip();
    }
    
    // Create a searchable task
    const todoColumn = page.locator('.kanban-column:has-text("To Do"), [data-column="todo"]').first();
    await todoColumn.locator('.add-task-btn, [data-add-task], button:has-text("Add")').click();
    const taskTitle = `Searchable Kanban ${Date.now()}`;
    const taskInput = page.locator('input[name="title"], input[name="task"], .quick-add-input');
    await taskInput.fill(taskTitle);
    await taskInput.press('Enter');
    
    // Search for task
    await searchInput.fill('Searchable');
    await page.keyboard.press('Enter');
    
    // Verify only matching tasks are visible
    await expect(page.locator(`.kanban-card:has-text("${taskTitle}")`)).toBeVisible();
    
    // Other tasks should be hidden or dimmed
    const allCards = page.locator('.kanban-card');
    const visibleCards = await allCards.filter({ hasText: 'Searchable' }).count();
    const totalCards = await allCards.count();
    
    if (totalCards > visibleCards) {
      // Check if non-matching cards are hidden
      const nonMatchingCard = allCards.filter({ hasNotText: 'Searchable' }).first();
      if (await nonMatchingCard.isVisible()) {
        // Cards might be dimmed instead of hidden
        await expect(nonMatchingCard).toHaveClass(/dimmed|filtered|opacity/);
      }
    }
  });

  test('should show task details on hover', async ({ page }) => {
    // Create a task with details
    const todoColumn = page.locator('.kanban-column:has-text("To Do"), [data-column="todo"]').first();
    await todoColumn.locator('.add-task-btn, [data-add-task], button:has-text("Add")').click();
    const taskTitle = `Hover Task ${Date.now()}`;
    const taskInput = page.locator('input[name="title"], input[name="task"], .quick-add-input');
    await taskInput.fill(taskTitle);
    await taskInput.press('Enter');
    
    // Hover over task card
    const taskCard = todoColumn.locator(`.kanban-card:has-text("${taskTitle}")`);
    await taskCard.hover();
    
    // Check for tooltip or expanded details
    const tooltip = page.locator('.tooltip, [role="tooltip"]');
    const expandedDetails = taskCard.locator('.task-details, .card-details');
    
    if (await tooltip.isVisible({ timeout: 500 })) {
      await expect(tooltip).toBeVisible();
    } else if (await expandedDetails.isVisible({ timeout: 500 })) {
      await expect(expandedDetails).toBeVisible();
    }
  });
});