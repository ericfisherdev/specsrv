import { test, expect } from '@playwright/test';

test.describe('Authentication Flow', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('should display login page for unauthenticated users', async ({ page }) => {
    await page.goto('/login');
    
    await expect(page.locator('h1')).toContainText('Login');
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
    await expect(page.locator('a[href="/register"]')).toBeVisible();
  });

  test('should show validation errors for invalid credentials', async ({ page }) => {
    await page.goto('/login');
    
    await page.fill('input[name="email"]', 'invalid@example.com');
    await page.fill('input[name="password"]', 'wrongpassword');
    await page.click('button[type="submit"]');
    
    await expect(page.locator('.error-message, .alert-danger')).toBeVisible();
  });

  test('should successfully login with valid credentials', async ({ page }) => {
    await page.goto('/login');
    
    // Using test credentials - these should be configured in test environment
    await page.fill('input[name="email"]', process.env.TEST_USER_EMAIL || 'test@example.com');
    await page.fill('input[name="password"]', process.env.TEST_USER_PASSWORD || 'password123');
    await page.click('button[type="submit"]');
    
    // Should redirect to dashboard after successful login
    await expect(page).toHaveURL(/\/dashboard/);
    await expect(page.locator('h1, .page-title')).toContainText(/Dashboard/i);
  });

  test('should display user profile information after login', async ({ page, context }) => {
    // Set up authentication state (if using JWT, set in localStorage/cookies)
    const testToken = process.env.TEST_JWT_TOKEN;
    if (testToken) {
      await context.addCookies([{
        name: 'auth_token',
        value: testToken,
        domain: 'localhost',
        path: '/',
      }]);
    }
    
    await page.goto('/dashboard');
    
    // Check for user menu or profile section
    await expect(page.locator('.user-menu, .user-profile, [data-user-menu]')).toBeVisible();
  });

  test('should successfully logout', async ({ page, context }) => {
    // Set up authentication state
    const testToken = process.env.TEST_JWT_TOKEN;
    if (testToken) {
      await context.addCookies([{
        name: 'auth_token',
        value: testToken,
        domain: 'localhost',
        path: '/',
      }]);
    }
    
    await page.goto('/dashboard');
    
    // Find and click logout button
    await page.click('.logout-btn, [data-logout], a[href="/logout"]');
    
    // Should redirect to login page
    await expect(page).toHaveURL(/\/login/);
  });

  test('should register a new user', async ({ page }) => {
    await page.goto('/register');
    
    await expect(page.locator('h1')).toContainText('Register');
    
    // Generate unique test email
    const timestamp = Date.now();
    const testEmail = `test${timestamp}@example.com`;
    
    await page.fill('input[name="email"]', testEmail);
    await page.fill('input[name="password"]', 'TestPassword123!');
    await page.fill('input[name="password_confirm"], input[name="confirmPassword"]', 'TestPassword123!');
    
    // Fill any additional required fields
    const nameField = page.locator('input[name="name"], input[name="username"]');
    if (await nameField.isVisible()) {
      await nameField.fill(`TestUser${timestamp}`);
    }
    
    await page.click('button[type="submit"]');
    
    // Should either redirect to dashboard or show success message
    await expect(page).toHaveURL(/(\/dashboard|\/login)/);
  });

  test('should handle session expiration gracefully', async ({ page, context }) => {
    // Set up expired token
    await context.addCookies([{
      name: 'auth_token',
      value: 'expired_token',
      domain: 'localhost',
      path: '/',
    }]);
    
    await page.goto('/dashboard');
    
    // Should redirect to login when token is invalid
    await expect(page).toHaveURL(/\/login/);
  });

  test('should persist authentication across page reloads', async ({ page, context }) => {
    // Login first
    await page.goto('/login');
    await page.fill('input[name="email"]', process.env.TEST_USER_EMAIL || 'test@example.com');
    await page.fill('input[name="password"]', process.env.TEST_USER_PASSWORD || 'password123');
    await page.click('button[type="submit"]');
    
    await expect(page).toHaveURL(/\/dashboard/);
    
    // Reload page
    await page.reload();
    
    // Should still be on dashboard
    await expect(page).toHaveURL(/\/dashboard/);
    await expect(page.locator('h1, .page-title')).toContainText(/Dashboard/i);
  });
});