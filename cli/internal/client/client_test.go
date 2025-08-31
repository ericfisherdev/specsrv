package client

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"github.com/ericfisherdev/specsrv/cli/internal/config"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestNewClient(t *testing.T) {
	cfg := &config.Config{
		Server: config.ServerConfig{
			URL:     "http://localhost:8000",
			Timeout: 30,
			Headers: map[string]string{"Custom": "Header"},
		},
		Auth: config.AuthConfig{
			Token: "test-token",
		},
	}

	client := NewClient(cfg)

	assert.Equal(t, "http://localhost:8000", client.baseURL)
	assert.Equal(t, "test-token", client.token)
	assert.Equal(t, "Header", client.headers["Custom"])
	assert.Equal(t, 30*time.Second, client.httpClient.Timeout)
}

func TestClient_SetToken(t *testing.T) {
	client := &Client{}
	client.SetToken("new-token")
	assert.Equal(t, "new-token", client.token)
}

func TestClient_HealthCheck(t *testing.T) {
	tests := []struct {
		name          string
		statusCode    int
		responseBody  string
		expectedError bool
	}{
		{
			name:          "successful health check",
			statusCode:    200,
			responseBody:  `{"status": "ok"}`,
			expectedError: false,
		},
		{
			name:          "failed health check",
			statusCode:    500,
			responseBody:  `{"error": "service unavailable"}`,
			expectedError: true,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
				assert.Equal(t, "/health", r.URL.Path)
				w.WriteHeader(tt.statusCode)
				w.Write([]byte(tt.responseBody))
			}))
			defer server.Close()

			cfg := &config.Config{
				Server: config.ServerConfig{URL: server.URL},
			}
			client := NewClient(cfg)

			err := client.HealthCheck()
			if tt.expectedError {
				assert.Error(t, err)
			} else {
				assert.NoError(t, err)
			}
		})
	}
}

func TestClient_Get(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		assert.Equal(t, "GET", r.Method)
		assert.Equal(t, "/api/test", r.URL.Path)
		assert.Equal(t, "Bearer test-token", r.Header.Get("Authorization"))

		response := map[string]interface{}{
			"data": "test-data",
		}
		json.NewEncoder(w).Encode(response)
	}))
	defer server.Close()

	cfg := &config.Config{
		Server: config.ServerConfig{URL: server.URL},
		Auth:   config.AuthConfig{Token: "test-token"},
	}
	client := NewClient(cfg)

	var result map[string]interface{}
	err := client.Get("/api/test", &result)

	require.NoError(t, err)
	assert.Equal(t, "test-data", result["data"])
}

func TestClient_Post(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		assert.Equal(t, "POST", r.Method)
		assert.Equal(t, "application/json", r.Header.Get("Content-Type"))

		var body map[string]interface{}
		json.NewDecoder(r.Body).Decode(&body)
		assert.Equal(t, "test", body["field"])

		response := map[string]interface{}{
			"id": 123,
		}
		json.NewEncoder(w).Encode(response)
	}))
	defer server.Close()

	cfg := &config.Config{
		Server: config.ServerConfig{URL: server.URL},
	}
	client := NewClient(cfg)

	requestBody := map[string]interface{}{
		"field": "test",
	}
	var result map[string]interface{}
	err := client.Post("/api/test", requestBody, &result)

	require.NoError(t, err)
	assert.Equal(t, float64(123), result["id"])
}
