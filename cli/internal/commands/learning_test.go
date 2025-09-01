package commands

import (
	"bytes"
	"encoding/json"
	"fmt"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"github.com/ericfisherdev/specsrv/cli/internal/client"
	"github.com/ericfisherdev/specsrv/cli/internal/config"
	"github.com/ericfisherdev/specsrv/cli/pkg/models"
	"github.com/spf13/cobra"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestLearningCommands(t *testing.T) {
	tests := []struct {
		name        string
		command     string
		args        []string
		expectError bool
		setupMock   func(*httptest.Server)
	}{
		{
			name:        "Record interaction - missing required flags",
			command:     "record",
			args:        []string{},
			expectError: true,
		},
		{
			name:    "Record interaction - valid input",
			command: "record",
			args: []string{
				"--task-id", "1",
				"--agent-type", "implementation",
				"--success-score", "0.85",
				"--execution-time", "1500",
				"--input-context", `{"task_type": "feature"}`,
				"--execution-steps", `[{"type": "analysis", "outcome": "success"}]`,
				"--output-result", `{"files_modified": 1}`,
			},
			expectError: false,
			setupMock: func(server *httptest.Server) {
				// Mock successful response
			},
		},
		{
			name:        "Recommend solution - missing agent type",
			command:     "recommend",
			args:        []string{},
			expectError: true,
		},
		{
			name:    "Recommend solution - valid input",
			command: "recommend",
			args: []string{
				"--agent-type", "implementation",
				"--task-context", `{"task_type": "feature", "complexity": "simple"}`,
				"--min-confidence", "0.7",
			},
			expectError: false,
			setupMock: func(server *httptest.Server) {
				// Mock successful recommendation response
			},
		},
		{
			name:        "List patterns - basic",
			command:     "patterns",
			args:        []string{},
			expectError: false,
			setupMock: func(server *httptest.Server) {
				// Mock successful patterns list response
			},
		},
		{
			name:    "List patterns - with filters",
			command: "patterns",
			args: []string{
				"--agent-type", "implementation",
				"--min-confidence", "0.8",
				"--limit", "10",
			},
			expectError: false,
			setupMock: func(server *httptest.Server) {
				// Mock filtered patterns response
			},
		},
		{
			name:        "Get analytics - basic",
			command:     "analytics",
			args:        []string{},
			expectError: false,
			setupMock: func(server *httptest.Server) {
				// Mock analytics response
			},
		},
		{
			name:    "Get analytics - with time range",
			command: "analytics",
			args: []string{
				"--range", "7d",
			},
			expectError: false,
			setupMock: func(server *httptest.Server) {
				// Mock analytics response
			},
		},
		{
			name:    "Search interactions",
			command: "search",
			args: []string{
				"--agent-type", "implementation",
				"--context", `{"task_type": "feature"}`,
				"--min-success", "0.7",
				"--limit", "5",
			},
			expectError: false,
			setupMock: func(server *httptest.Server) {
				// Mock search response
			},
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			// Create mock server
			server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
				// Handle different endpoints
				switch {
				case strings.HasSuffix(r.URL.Path, "/record-interaction"):
					handleRecordInteraction(w, r)
				case strings.HasSuffix(r.URL.Path, "/recommend-solution"):
					handleRecommendSolution(w, r)
				case strings.HasSuffix(r.URL.Path, "/patterns"):
					handleGetPatterns(w, r)
				case strings.HasSuffix(r.URL.Path, "/performance"):
					handleGetAnalytics(w, r)
				case strings.HasSuffix(r.URL.Path, "/search"):
					handleSearchInteractions(w, r)
				default:
					w.WriteHeader(http.StatusNotFound)
				}
			}))
			defer server.Close()

			if tt.setupMock != nil {
				tt.setupMock(server)
			}

			// Create test config
			cfg := &config.Config{
				Server: config.ServerConfig{
					URL: server.URL,
				},
				Auth: config.AuthConfig{
					Token: "test-token",
				},
			}

			// Create client
			apiClient := client.NewClient(cfg)

			// Create learning command
			learningCmd := NewLearningCommand()

			// Capture output
			var buf bytes.Buffer
			learningCmd.SetOut(&buf)
			learningCmd.SetErr(&buf)

			// Find the subcommand
			subCmd, _, err := learningCmd.Find([]string{tt.command})
			if err != nil {
				t.Fatalf("Failed to find subcommand %s: %v", tt.command, err)
			}

			// Set args and execute
			subCmd.SetArgs(tt.args)
			err = subCmd.Execute()

			if tt.expectError {
				assert.Error(t, err, "Expected error for command %s with args %v", tt.command, tt.args)
			} else {
				if err != nil {
					t.Logf("Command output: %s", buf.String())
				}
				assert.NoError(t, err, "Expected no error for command %s with args %v", tt.command, tt.args)
			}
		})
	}
}

func TestLearningCommandValidation(t *testing.T) {
	tests := []struct {
		name        string
		command     string
		args        []string
		expectedErr string
	}{
		{
			name:        "Record interaction - invalid success score",
			command:     "record",
			args:        []string{"--task-id", "1", "--agent-type", "test", "--success-score", "1.5"},
			expectedErr: "success score must be between 0 and 1",
		},
		{
			name:        "Record interaction - invalid JSON context",
			command:     "record",
			args:        []string{"--task-id", "1", "--agent-type", "test", "--success-score", "0.8", "--input-context", "invalid-json"},
			expectedErr: "invalid input context JSON",
		},
		{
			name:        "Recommend solution - invalid confidence",
			command:     "recommend",
			args:        []string{"--agent-type", "test", "--min-confidence", "1.5"},
			expectedErr: "min_confidence must be between 0 and 1",
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			learningCmd := NewLearningCommand()
			
			subCmd, _, err := learningCmd.Find([]string{tt.command})
			require.NoError(t, err)
			
			subCmd.SetArgs(tt.args)
			err = subCmd.Execute()
			
			assert.Error(t, err)
			assert.Contains(t, err.Error(), tt.expectedErr)
		})
	}
}

func TestFormatting(t *testing.T) {
	// Test recommendation formatting
	recommendation := models.LearningRecommendation{
		Pattern: models.KnowledgePattern{
			ID:              1,
			Name:            "Test Pattern",
			Type:            "implementation",
			ConfidenceScore: 0.85,
			UsageCount:      5,
		},
		Confidence:           0.85,
		EstimatedSuccessRate: 0.9,
		AdaptedSolution: map[string]interface{}{
			"approach":      "analytical",
			"time_estimate": "moderate",
		},
	}

	var buf bytes.Buffer
	err := formatRecommendationOutput(recommendation, "json")
	assert.NoError(t, err)

	// Test patterns formatting
	patterns := []models.KnowledgePattern{
		{
			ID:              1,
			Name:            "Pattern 1",
			Type:            "implementation",
			ConfidenceScore: 0.9,
			UsageCount:      10,
		},
		{
			ID:              2,
			Name:            "Pattern 2",
			Type:            "analysis",
			ConfidenceScore: 0.8,
			UsageCount:      5,
		},
	}

	err = formatPatternsOutput(patterns, "json")
	assert.NoError(t, err)

	// Test analytics formatting
	analytics := models.LearningAnalytics{
		InteractionMetrics: []models.InteractionMetric{
			{
				AgentType:              "implementation",
				TotalInteractions:      100,
				AvgSuccessScore:        0.85,
				AvgExecutionTime:       1500.0,
				SuccessfulInteractions: 85,
			},
		},
		PatternAnalytics: []models.PatternAnalytic{
			{
				PatternType:   "implementation",
				TotalPatterns: 10,
				AvgConfidence: 0.8,
				TotalUsage:    50,
			},
		},
	}

	err = formatAnalyticsOutput(analytics, "table")
	assert.NoError(t, err)
}

// Mock handlers for different endpoints
func handleRecordInteraction(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		w.WriteHeader(http.StatusMethodNotAllowed)
		return
	}

	response := map[string]interface{}{
		"success": true,
		"data": map[string]interface{}{
			"interaction_id":    "test-interaction-id",
			"pattern_extracted": true,
			"pattern_hash":      "test-hash",
		},
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(response)
}

func handleRecommendSolution(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		w.WriteHeader(http.StatusMethodNotAllowed)
		return
	}

	recommendation := models.LearningRecommendation{
		Pattern: models.KnowledgePattern{
			ID:              1,
			Name:            "Test Pattern",
			Type:            "implementation",
			ConfidenceScore: 0.85,
			UsageCount:      5,
		},
		Confidence:           0.85,
		EstimatedSuccessRate: 0.9,
		AdaptedSolution:      map[string]interface{}{"approach": "analytical"},
		UsageHistory:         []models.InteractionSummary{},
	}

	response := map[string]interface{}{
		"success": true,
		"data":    recommendation,
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(response)
}

func handleGetPatterns(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		w.WriteHeader(http.StatusMethodNotAllowed)
		return
	}

	patterns := []models.KnowledgePattern{
		{
			ID:              1,
			Name:            "Test Pattern 1",
			Type:            "implementation",
			ConfidenceScore: 0.9,
			UsageCount:      10,
		},
		{
			ID:              2,
			Name:            "Test Pattern 2",
			Type:            "analysis",
			ConfidenceScore: 0.8,
			UsageCount:      5,
		},
	}

	response := map[string]interface{}{
		"success": true,
		"data": map[string]interface{}{
			"items": patterns,
			"pagination": map[string]interface{}{
				"current_page": 1,
				"per_page":     20,
				"total_items":  2,
				"total_pages":  1,
			},
		},
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(response)
}

func handleGetAnalytics(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		w.WriteHeader(http.StatusMethodNotAllowed)
		return
	}

	analytics := models.LearningAnalytics{
		InteractionMetrics: []models.InteractionMetric{
			{
				AgentType:              "implementation",
				TotalInteractions:      100,
				AvgSuccessScore:        0.85,
				AvgExecutionTime:       1500.0,
				SuccessfulInteractions: 85,
			},
		},
		PatternAnalytics: []models.PatternAnalytic{
			{
				PatternType:   "implementation",
				TotalPatterns: 10,
				AvgConfidence: 0.8,
				TotalUsage:    50,
			},
		},
		LearningEffectiveness: &models.LearningEffectiveness{
			TotalInteractions: 100,
			PatternsLearned:   10,
			PatternReuses:     40,
			LearningRate:      0.1,
			ReuseRate:         0.4,
		},
	}

	response := map[string]interface{}{
		"success": true,
		"data":    analytics,
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(response)
}

func handleSearchInteractions(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		w.WriteHeader(http.StatusMethodNotAllowed)
		return
	}

	patterns := []models.KnowledgePattern{
		{
			ID:              1,
			Name:            "Matching Pattern",
			Type:            "implementation",
			ConfidenceScore: 0.9,
			UsageCount:      10,
		},
	}

	searchResponse := models.SearchResponse{
		Patterns:    patterns,
		TotalFound:  1,
		Returned:    1,
	}

	response := map[string]interface{}{
		"success": true,
		"data":    searchResponse,
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(response)
}