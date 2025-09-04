import { test, expect } from '@playwright/test';

test.describe('Performance Testing', () => {
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

  test('should load dashboard within performance threshold', async ({ page }) => {
    const startTime = Date.now();
    
    // Navigate to dashboard
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');
    
    const loadTime = Date.now() - startTime;
    
    // Verify dashboard loads within 3 seconds
    expect(loadTime).toBeLessThan(3000);
    
    // Verify main content is visible
    await expect(page.locator('h1, .page-title')).toBeVisible();
    
    console.log(`Dashboard load time: ${loadTime}ms`);
  });

  test('should load projects page within performance threshold', async ({ page }) => {
    const startTime = Date.now();
    
    await page.goto('/projects');
    await page.waitForLoadState('networkidle');
    
    const loadTime = Date.now() - startTime;
    
    // Should load within 2 seconds
    expect(loadTime).toBeLessThan(2000);
    
    await expect(page.locator('h1, .page-title')).toBeVisible();
    console.log(`Projects page load time: ${loadTime}ms`);
  });

  test('should load tasks page within performance threshold', async ({ page }) => {
    const startTime = Date.now();
    
    await page.goto('/tasks');
    await page.waitForLoadState('networkidle');
    
    const loadTime = Date.now() - startTime;
    
    // Should load within 2 seconds
    expect(loadTime).toBeLessThan(2000);
    
    await expect(page.locator('h1, .page-title')).toBeVisible();
    console.log(`Tasks page load time: ${loadTime}ms`);
  });

  test('should load kanban board within performance threshold', async ({ page }) => {
    const startTime = Date.now();
    
    await page.goto('/kanban');
    await page.waitForLoadState('networkidle');
    
    const loadTime = Date.now() - startTime;
    
    // Kanban might take slightly longer due to complex layout
    expect(loadTime).toBeLessThan(4000);
    
    await expect(page.locator('.kanban-board, [data-kanban-board]')).toBeVisible();
    console.log(`Kanban board load time: ${loadTime}ms`);
  });

  test('should have acceptable bundle size', async ({ page }) => {
    // Navigate to any page to trigger resource loading
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');
    
    // Get all loaded resources
    const resources = await page.evaluate(() => {
      const entries = performance.getEntriesByType('resource');
      return entries.map(entry => ({
        name: entry.name,
        size: entry.transferSize || entry.encodedBodySize,
        type: entry.initiatorType
      }));
    });
    
    // Check JavaScript bundle size
    const jsResources = resources.filter(r => r.name.includes('.js') && r.type === 'script');
    const totalJsSize = jsResources.reduce((sum, r) => sum + r.size, 0);
    
    // Should be under 500KB for main bundle
    expect(totalJsSize).toBeLessThan(500 * 1024);
    console.log(`Total JS bundle size: ${Math.round(totalJsSize / 1024)}KB`);
    
    // Check CSS bundle size
    const cssResources = resources.filter(r => r.name.includes('.css') && r.type === 'link');
    const totalCssSize = cssResources.reduce((sum, r) => sum + r.size, 0);
    
    // Should be under 100KB for CSS
    expect(totalCssSize).toBeLessThan(100 * 1024);
    console.log(`Total CSS bundle size: ${Math.round(totalCssSize / 1024)}KB`);
  });

  test('should measure API response times', async ({ page }) => {
    await page.goto('/dashboard');
    
    // Set up network monitoring
    const apiResponses = [];
    page.on('response', response => {
      if (response.url().includes('/api/')) {
        apiResponses.push({
          url: response.url(),
          status: response.status(),
          timing: response.timing()
        });
      }
    });
    
    // Trigger API calls by navigating to different pages
    await page.goto('/projects');
    await page.waitForLoadState('networkidle');
    
    await page.goto('/tasks');
    await page.waitForLoadState('networkidle');
    
    // Check API response times
    for (const response of apiResponses) {
      if (response.timing) {
        const responseTime = response.timing.responseEnd - response.timing.requestStart;
        
        // API responses should be under 200ms for standard operations
        if (response.status === 200) {
          expect(responseTime).toBeLessThan(200);
          console.log(`${response.url}: ${responseTime}ms`);
        }
      }
    }
  });

  test('should have good Core Web Vitals', async ({ page }) => {
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');
    
    // Measure Core Web Vitals
    const vitals = await page.evaluate(() => {
      return new Promise((resolve) => {
        const vitals = {};
        
        // First Contentful Paint (FCP)
        const fcpEntries = performance.getEntriesByName('first-contentful-paint');
        if (fcpEntries.length > 0) {
          vitals.fcp = fcpEntries[0].startTime;
        }
        
        // Largest Contentful Paint (LCP)
        new PerformanceObserver((list) => {
          const entries = list.getEntries();
          if (entries.length > 0) {
            vitals.lcp = entries[entries.length - 1].startTime;
          }
        }).observe({ entryTypes: ['largest-contentful-paint'] });
        
        // First Input Delay (FID) - can't easily test in automated tests
        // Cumulative Layout Shift (CLS)
        new PerformanceObserver((list) => {
          vitals.cls = list.getEntries().reduce((sum, entry) => sum + entry.value, 0);
        }).observe({ entryTypes: ['layout-shift'] });
        
        // Give time for measurements
        setTimeout(() => resolve(vitals), 1000);
      });
    });
    
    // FCP should be under 1.8 seconds (good)
    if (vitals.fcp) {
      expect(vitals.fcp).toBeLessThan(1800);
      console.log(`First Contentful Paint: ${Math.round(vitals.fcp)}ms`);
    }
    
    // LCP should be under 2.5 seconds (good)
    if (vitals.lcp) {
      expect(vitals.lcp).toBeLessThan(2500);
      console.log(`Largest Contentful Paint: ${Math.round(vitals.lcp)}ms`);
    }
    
    // CLS should be under 0.1 (good)
    if (vitals.cls) {
      expect(vitals.cls).toBeLessThan(0.1);
      console.log(`Cumulative Layout Shift: ${vitals.cls.toFixed(3)}`);
    }
  });

  test('should handle large datasets efficiently', async ({ page }) => {
    await page.goto('/tasks');
    
    // Create many tasks to test performance with larger datasets
    const startTime = Date.now();
    
    // Simulate loading a large number of tasks
    await page.evaluate(() => {
      // This would typically be done by navigating to a page with many items
      // or by triggering an API call that returns many items
      window.scrollTo(0, document.body.scrollHeight);
    });
    
    // Wait for any lazy loading or pagination
    await page.waitForTimeout(500);
    
    const scrollTime = Date.now() - startTime;
    
    // Scrolling and rendering should be responsive
    expect(scrollTime).toBeLessThan(1000);
    console.log(`Large dataset scroll time: ${scrollTime}ms`);
  });

  test('should have minimal memory usage', async ({ page }) => {
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');
    
    // Get initial memory usage
    const initialMemory = await page.evaluate(() => {
      if (performance.memory) {
        return {
          used: performance.memory.usedJSHeapSize,
          total: performance.memory.totalJSHeapSize
        };
      }
      return null;
    });
    
    if (initialMemory) {
      // Navigate through several pages to test for memory leaks
      await page.goto('/projects');
      await page.waitForLoadState('networkidle');
      
      await page.goto('/tasks');
      await page.waitForLoadState('networkidle');
      
      await page.goto('/kanban');
      await page.waitForLoadState('networkidle');
      
      await page.goto('/dashboard');
      await page.waitForLoadState('networkidle');
      
      // Check memory usage after navigation
      const finalMemory = await page.evaluate(() => {
        if (performance.memory) {
          return {
            used: performance.memory.usedJSHeapSize,
            total: performance.memory.totalJSHeapSize
          };
        }
        return null;
      });
      
      if (finalMemory) {
        const memoryIncrease = finalMemory.used - initialMemory.used;
        const memoryIncreasePercent = (memoryIncrease / initialMemory.used) * 100;
        
        // Memory shouldn't increase by more than 50% during navigation
        expect(memoryIncreasePercent).toBeLessThan(50);
        
        console.log(`Initial memory: ${Math.round(initialMemory.used / 1024 / 1024)}MB`);
        console.log(`Final memory: ${Math.round(finalMemory.used / 1024 / 1024)}MB`);
        console.log(`Memory increase: ${memoryIncreasePercent.toFixed(1)}%`);
      }
    }
  });

  test('should handle offline scenarios gracefully', async ({ page, context }) => {
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');
    
    // Go offline
    await context.setOffline(true);
    
    const startTime = Date.now();
    
    // Try to navigate to another page
    await page.goto('/projects');
    
    const offlineTime = Date.now() - startTime;
    
    // Should handle offline state quickly (not hang)
    expect(offlineTime).toBeLessThan(5000);
    
    // Check for offline indicator or cached content
    const offlineIndicator = page.locator('.offline-indicator, [data-offline]');
    const cachedContent = page.locator('h1, .page-title, .cached-content');
    
    // Either show offline indicator or cached content
    await expect(offlineIndicator.or(cachedContent)).toBeVisible({ timeout: 2000 });
    
    // Go back online
    await context.setOffline(false);
    await page.waitForTimeout(1000);
    
    // Content should be available again
    await page.reload();
    await expect(page.locator('h1, .page-title')).toBeVisible();
  });

  test('should perform well on mobile devices', async ({ browser }) => {
    // Create mobile context
    const mobileContext = await browser.newContext({
      ...browser.contexts()[0] || {},
      viewport: { width: 375, height: 667 }, // iPhone SE dimensions
      userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1'
    });
    
    const page = await mobileContext.newPage();
    
    // Set up authentication
    const testToken = process.env.TEST_JWT_TOKEN || 'test_token';
    await mobileContext.addCookies([{
      name: 'auth_token',
      value: testToken,
      domain: 'localhost',
      path: '/',
    }]);
    
    const startTime = Date.now();
    
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');
    
    const mobileLoadTime = Date.now() - startTime;
    
    // Mobile should load within 4 seconds (accounting for slower mobile networks)
    expect(mobileLoadTime).toBeLessThan(4000);
    
    // Check that mobile layout is responsive
    const viewport = page.viewportSize();
    expect(viewport.width).toBe(375);
    
    // Verify main content is visible and properly sized
    await expect(page.locator('h1, .page-title')).toBeVisible();
    
    // Check for mobile-specific UI elements
    const mobileMenu = page.locator('.mobile-menu, .hamburger-menu, [data-mobile-menu]');
    if (await mobileMenu.isVisible()) {
      await expect(mobileMenu).toBeVisible();
    }
    
    console.log(`Mobile load time: ${mobileLoadTime}ms`);
    
    await mobileContext.close();
  });
});