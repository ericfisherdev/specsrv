package client

import (
	"fmt"
	"net/url"

	"github.com/ericfisherdev/specsrv/cli/pkg/models"
)

// RecordInteraction records an AI agent interaction
func (c *Client) RecordInteraction(req models.InteractionRecordRequest) (*models.InteractionRecordResponse, error) {
	var response models.InteractionRecordResponse
	err := c.Post("/api/learning/record-interaction", req, &response)
	if err != nil {
		return nil, fmt.Errorf("failed to record interaction: %w", err)
	}
	return &response, nil
}

// GetRecommendation gets a solution recommendation based on learned patterns
func (c *Client) GetRecommendation(req models.RecommendationRequest) (*models.LearningRecommendation, error) {
	var response models.LearningRecommendation
	err := c.Post("/api/learning/recommend-solution", req, &response)
	if err != nil {
		return nil, fmt.Errorf("failed to get recommendation: %w", err)
	}
	return &response, nil
}

// GetPatterns retrieves learned patterns with optional filtering
func (c *Client) GetPatterns(params map[string]string) (*models.PaginatedPatternsResponse, error) {
	query := url.Values{}
	for key, value := range params {
		if value != "" {
			query.Set(key, value)
		}
	}

	var response models.PaginatedPatternsResponse
	err := c.GetWithQuery("/api/learning/patterns", query, &response)
	if err != nil {
		return nil, fmt.Errorf("failed to get patterns: %w", err)
	}
	return &response, nil
}

// GetLearningAnalytics retrieves learning system analytics
func (c *Client) GetLearningAnalytics(timeRange string) (*models.LearningAnalytics, error) {
	query := url.Values{}
	if timeRange != "" {
		query.Set("range", timeRange)
	}

	var response models.LearningAnalytics
	err := c.GetWithQuery("/api/learning/analytics/performance", query, &response)
	if err != nil {
		return nil, fmt.Errorf("failed to get learning analytics: %w", err)
	}
	return &response, nil
}

// SearchInteractions searches for similar interactions based on context
func (c *Client) SearchInteractions(req models.SearchRequest) (*models.SearchResponse, error) {
	var response models.SearchResponse
	err := c.Post("/api/learning/interactions/search", req, &response)
	if err != nil {
		return nil, fmt.Errorf("failed to search interactions: %w", err)
	}
	return &response, nil
}

// SubmitPatternFeedback submits feedback on a pattern's performance
func (c *Client) SubmitPatternFeedback(patternID int, req models.PatternFeedbackRequest) (*models.PatternFeedbackResponse, error) {
	path := fmt.Sprintf("/api/learning/patterns/%d/feedback", patternID)
	
	var response models.PatternFeedbackResponse
	err := c.Post(path, req, &response)
	if err != nil {
		return nil, fmt.Errorf("failed to submit pattern feedback: %w", err)
	}
	return &response, nil
}

// CheckLearningSystemHealth performs a health check on the learning system
func (c *Client) CheckLearningSystemHealth() (map[string]interface{}, error) {
	var response map[string]interface{}
	err := c.Get("/api/learning/health", &response)
	if err != nil {
		return nil, fmt.Errorf("learning system health check failed: %w", err)
	}
	return response, nil
}