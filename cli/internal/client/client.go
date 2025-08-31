package client

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"time"

	"github.com/specsrv/specsrv-cli/internal/config"
)

// Client represents the HTTP client for API communication
type Client struct {
	baseURL    string
	httpClient *http.Client
	token      string
	headers    map[string]string
}

// Response represents a generic API response
type Response struct {
	Status     int         `json:"status"`
	Message    string      `json:"message"`
	Data       interface{} `json:"data,omitempty"`
	Error      string      `json:"error,omitempty"`
	Pagination *Pagination `json:"pagination,omitempty"`
}

// Pagination represents pagination information
type Pagination struct {
	Page       int `json:"page"`
	PerPage    int `json:"per_page"`
	Total      int `json:"total"`
	TotalPages int `json:"total_pages"`
}

// ErrorResponse represents an API error response
type ErrorResponse struct {
	Status  int    `json:"status"`
	Message string `json:"message"`
	Error   string `json:"error"`
}

// NewClient creates a new API client
func NewClient(cfg *config.Config) *Client {
	timeout := time.Duration(cfg.Server.Timeout) * time.Second
	if timeout == 0 {
		timeout = 30 * time.Second
	}

	return &Client{
		baseURL: cfg.Server.URL,
		httpClient: &http.Client{
			Timeout: timeout,
		},
		token:   cfg.Auth.Token,
		headers: cfg.Server.Headers,
	}
}

// SetToken sets the authentication token
func (c *Client) SetToken(token string) {
	c.token = token
}

// buildURL constructs a full URL with the base URL and path
func (c *Client) buildURL(path string) string {
	baseURL, err := url.Parse(c.baseURL)
	if err != nil {
		return c.baseURL + path
	}

	pathURL, err := url.Parse(path)
	if err != nil {
		return c.baseURL + path
	}

	return baseURL.ResolveReference(pathURL).String()
}

// newRequest creates a new HTTP request with common headers
func (c *Client) newRequest(method, path string, body interface{}) (*http.Request, error) {
	url := c.buildURL(path)

	var buf io.Reader
	if body != nil {
		jsonBody, err := json.Marshal(body)
		if err != nil {
			return nil, fmt.Errorf("failed to marshal request body: %w", err)
		}
		buf = bytes.NewBuffer(jsonBody)
	}

	req, err := http.NewRequest(method, url, buf)
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	// Set common headers
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Accept", "application/json")
	req.Header.Set("User-Agent", "specsrv-cli/1.0.0")

	// Set authentication header
	if c.token != "" {
		req.Header.Set("Authorization", "Bearer "+c.token)
	}

	// Set custom headers
	for key, value := range c.headers {
		req.Header.Set(key, value)
	}

	return req, nil
}

// doRequest executes an HTTP request and returns the response
func (c *Client) doRequest(req *http.Request) (*http.Response, error) {
	resp, err := c.httpClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("request failed: %w", err)
	}

	return resp, nil
}

// parseResponse parses the HTTP response body
func (c *Client) parseResponse(resp *http.Response, result interface{}) error {
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return fmt.Errorf("failed to read response body: %w", err)
	}

	if resp.StatusCode >= 400 {
		var errorResp ErrorResponse
		if err := json.Unmarshal(body, &errorResp); err != nil {
			return fmt.Errorf("HTTP %d: %s", resp.StatusCode, string(body))
		}
		return fmt.Errorf("API error (%d): %s", errorResp.Status, errorResp.Message)
	}

	if result != nil {
		if err := json.Unmarshal(body, result); err != nil {
			return fmt.Errorf("failed to parse response: %w", err)
		}
	}

	return nil
}

// Get performs a GET request
func (c *Client) Get(path string, result interface{}) error {
	req, err := c.newRequest("GET", path, nil)
	if err != nil {
		return err
	}

	resp, err := c.doRequest(req)
	if err != nil {
		return err
	}

	return c.parseResponse(resp, result)
}

// Post performs a POST request
func (c *Client) Post(path string, body interface{}, result interface{}) error {
	req, err := c.newRequest("POST", path, body)
	if err != nil {
		return err
	}

	resp, err := c.doRequest(req)
	if err != nil {
		return err
	}

	return c.parseResponse(resp, result)
}

// Put performs a PUT request
func (c *Client) Put(path string, body interface{}, result interface{}) error {
	req, err := c.newRequest("PUT", path, body)
	if err != nil {
		return err
	}

	resp, err := c.doRequest(req)
	if err != nil {
		return err
	}

	return c.parseResponse(resp, result)
}

// Delete performs a DELETE request
func (c *Client) Delete(path string, result interface{}) error {
	req, err := c.newRequest("DELETE", path, nil)
	if err != nil {
		return err
	}

	resp, err := c.doRequest(req)
	if err != nil {
		return err
	}

	return c.parseResponse(resp, result)
}

// Patch performs a PATCH request
func (c *Client) Patch(path string, body interface{}, result interface{}) error {
	req, err := c.newRequest("PATCH", path, body)
	if err != nil {
		return err
	}

	resp, err := c.doRequest(req)
	if err != nil {
		return err
	}

	return c.parseResponse(resp, result)
}

// HealthCheck checks if the API server is healthy
func (c *Client) HealthCheck() error {
	var response Response
	return c.Get("/health", &response)
}

// GetWithQuery performs a GET request with query parameters
func (c *Client) GetWithQuery(path string, query url.Values, result interface{}) error {
	if len(query) > 0 {
		path += "?" + query.Encode()
	}
	return c.Get(path, result)
}
