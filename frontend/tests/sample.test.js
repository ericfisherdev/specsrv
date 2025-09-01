// Sample test to verify Jest setup is working correctly

describe('Testing Framework Setup', () => {
  test('should have access to DOM', () => {
    const element = document.createElement('div');
    element.textContent = 'Hello, World!';
    document.body.appendChild(element);
    
    expect(document.body.children.length).toBe(1);
    expect(element.textContent).toBe('Hello, World!');
  });
  
  test('should have access to test utilities', () => {
    expect(global.testUtils).toBeDefined();
    expect(typeof global.testUtils.createElement).toBe('function');
    expect(typeof global.testUtils.waitFor).toBe('function');
    expect(typeof global.testUtils.eventually).toBe('function');
  });
  
  test('should mock localStorage', () => {
    localStorage.setItem('test', 'value');
    expect(localStorage.setItem).toHaveBeenCalledWith('test', 'value');
    
    localStorage.getItem.mockReturnValue('value');
    expect(localStorage.getItem('test')).toBe('value');
  });
  
  test('should mock fetch', () => {
    const mockResponse = { ok: true, json: jest.fn().mockResolvedValue({ data: 'test' }) };
    fetch.mockResolvedValue(mockResponse);
    
    expect(fetch).toBeDefined();
    expect(typeof fetch).toBe('function');
  });
  
  test('should have HTMX mocked', () => {
    expect(global.htmx).toBeDefined();
    expect(typeof global.htmx.ajax).toBe('function');
    expect(typeof global.htmx.trigger).toBe('function');
  });
  
  test('should have AlpineJS mocked', () => {
    expect(global.Alpine).toBeDefined();
    expect(typeof global.Alpine.start).toBe('function');
    expect(typeof global.Alpine.data).toBe('function');
  });
  
  test('should have GSAP mocked', () => {
    expect(global.gsap).toBeDefined();
    expect(typeof global.gsap.to).toBe('function');
    expect(typeof global.gsap.timeline).toBe('function');
  });
  
  test('testUtils.createElement should work', () => {
    const button = global.testUtils.createElement('button', {
      className: 'btn btn-primary',
      type: 'button',
      innerHTML: 'Click me'
    });
    
    expect(button.tagName).toBe('BUTTON');
    expect(button.className).toBe('btn btn-primary');
    expect(button.type).toBe('button');
    expect(button.innerHTML).toBe('Click me');
  });
  
  test('cleanup should work after each test', () => {
    // This test verifies that DOM is cleaned up between tests
    expect(document.body.children.length).toBe(0);
  });
});