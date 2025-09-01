package models

import (
	"time"

	"github.com/ericfisherdev/specsrv/cli/pkg/enums"
)

// Project represents a project in the system
type Project struct {
	ID          int                 `json:"id"`
	Name        string              `json:"name"`
	Description string              `json:"description"`
	Status      enums.ProjectStatus `json:"status"`
	CreatedAt   time.Time           `json:"created_at"`
	UpdatedAt   time.Time           `json:"updated_at"`
	TaskCount   int                 `json:"task_count,omitempty"`
}

// ProjectCreateRequest represents a request to create a new project
type ProjectCreateRequest struct {
	Name        string              `json:"name" validate:"required,min=1,max=100"`
	Description string              `json:"description" validate:"max=500"`
	Status      enums.ProjectStatus `json:"status" validate:"omitempty"`
}

// ProjectUpdateRequest represents a request to update a project
type ProjectUpdateRequest struct {
	Name        *string              `json:"name,omitempty" validate:"omitempty,min=1,max=100"`
	Description *string              `json:"description,omitempty" validate:"omitempty,max=500"`
	Status      *enums.ProjectStatus `json:"status,omitempty" validate:"omitempty"`
}

// Task represents a task in the system
type Task struct {
	ID          int                `json:"id"`
	ProjectID   int                `json:"project_id"`
	Title       string             `json:"title"`
	Description string             `json:"description"`
	Status      enums.TaskStatus   `json:"status"`
	Priority    enums.TaskPriority `json:"priority"`
	AssigneeID  *int               `json:"assignee_id,omitempty"`
	CreatedAt   time.Time          `json:"created_at"`
	UpdatedAt   time.Time          `json:"updated_at"`
	DueDate     *time.Time         `json:"due_date,omitempty"`
	Tags        []string           `json:"tags,omitempty"`
	Files       []File             `json:"files,omitempty"`
	GitLinks    []GitLink          `json:"git_links,omitempty"`
	AISummary   string             `json:"ai_summary,omitempty"`
}

// TaskCreateRequest represents a request to create a new task
type TaskCreateRequest struct {
	ProjectID   int                `json:"project_id" validate:"required"`
	Title       string             `json:"title" validate:"required,min=1,max=200"`
	Description string             `json:"description" validate:"max=2000"`
	Status      enums.TaskStatus   `json:"status" validate:"omitempty"`
	Priority    enums.TaskPriority `json:"priority" validate:"omitempty"`
	AssigneeID  *int               `json:"assignee_id,omitempty"`
	DueDate     *time.Time         `json:"due_date,omitempty"`
	Tags        []string           `json:"tags,omitempty"`
}

// TaskUpdateRequest represents a request to update a task
type TaskUpdateRequest struct {
	Title       *string             `json:"title,omitempty" validate:"omitempty,min=1,max=200"`
	Description *string             `json:"description,omitempty" validate:"omitempty,max=2000"`
	Status      *enums.TaskStatus   `json:"status,omitempty" validate:"omitempty"`
	Priority    *enums.TaskPriority `json:"priority,omitempty" validate:"omitempty"`
	AssigneeID  *int                `json:"assignee_id,omitempty"`
	DueDate     *time.Time          `json:"due_date,omitempty"`
	Tags        []string            `json:"tags,omitempty"`
	AISummary   *string             `json:"ai_summary,omitempty"`
}

// File represents a file attachment
type File struct {
	ID         int       `json:"id"`
	EntityType string    `json:"entity_type" validate:"oneof=project task"` // "project" or "task"
	EntityID   int       `json:"entity_id"`
	Name       string    `json:"name"`
	Path       string    `json:"path"`
	Size       int64     `json:"size"`
	MimeType   string    `json:"mime_type"`
	CreatedAt  time.Time `json:"created_at"`
}

// FileUploadRequest represents a request to upload a file
type FileUploadRequest struct {
	EntityType string `json:"entity_type" validate:"required,oneof=project task"`
	EntityID   int    `json:"entity_id" validate:"required"`
	Name       string `json:"name" validate:"required"`
}

// GitLink represents a git repository link
type GitLink struct {
	ID        int       `json:"id"`
	TaskID    int       `json:"task_id"`
	Type      string    `json:"type"` // "commit", "pr", "branch"
	URL       string    `json:"url"`
	Reference string    `json:"reference"` // commit hash, PR number, branch name
	Message   string    `json:"message,omitempty"`
	CreatedAt time.Time `json:"created_at"`
}

// GitLinkCreateRequest represents a request to create a git link
type GitLinkCreateRequest struct {
	TaskID    int    `json:"task_id" validate:"required"`
	Type      string `json:"type" validate:"required,oneof=commit pr branch"`
	URL       string `json:"url" validate:"required,url"`
	Reference string `json:"reference" validate:"required"`
	Message   string `json:"message,omitempty"`
}

// User represents a user in the system
type User struct {
	ID        int       `json:"id"`
	Username  string    `json:"username"`
	Email     string    `json:"email"`
	FullName  string    `json:"full_name"`
	CreatedAt time.Time `json:"created_at"`
	UpdatedAt time.Time `json:"updated_at"`
}

// AuthRequest represents an authentication request
type AuthRequest struct {
	Username string `json:"username" validate:"required"`
	Password string `json:"password" validate:"required"`
}

// AuthResponse represents an authentication response
type AuthResponse struct {
	Token     string    `json:"token"`
	ExpiresAt time.Time `json:"expires_at"`
	User      User      `json:"user"`
}

// ListOptions represents common options for list operations
type ListOptions struct {
	Page     int    `json:"page" validate:"omitempty,min=1"`
	PerPage  int    `json:"per_page" validate:"omitempty,min=1,max=200"`
	Sort     string `json:"sort" validate:"omitempty"`
	Order    string `json:"order" validate:"omitempty,oneof=asc desc"`
	Search   string `json:"search"`
	Status   string `json:"status"`
	Priority string `json:"priority"`
}

// ExportOptions represents options for data export
type ExportOptions struct {
	Format     string     `json:"format" validate:"required,oneof=json yaml csv xml"`
	StartDate  *time.Time `json:"start_date,omitempty"`
	EndDate    *time.Time `json:"end_date,omitempty"`
	ProjectIDs []int      `json:"project_ids,omitempty"`
	TaskIDs    []int      `json:"task_ids,omitempty"`
}

// ExportResponse represents an export response
type ExportResponse struct {
	Format   string `json:"format"`
	Content  string `json:"content"`
	Filename string `json:"filename"`
	Size     int64  `json:"size"`
}

// Learning System Models

// AgentInteraction represents an AI agent interaction
type AgentInteraction struct {
	ID              int                    `json:"id"`
	TaskID          int                    `json:"task_id"`
	AgentType       string                 `json:"agent_type"`
	InputContext    map[string]interface{} `json:"input_context"`
	ExecutionSteps  []map[string]interface{} `json:"execution_steps"`
	OutputResult    map[string]interface{} `json:"output_result"`
	SuccessScore    float64                `json:"success_score"`
	PatternHash     string                 `json:"pattern_hash"`
	ErrorLog        []map[string]interface{} `json:"error_log,omitempty"`
	ExecutionTimeMs int                    `json:"execution_time_ms"`
	CreatedAt       time.Time              `json:"created_at"`
}

// InteractionRecordRequest represents a request to record an interaction
type InteractionRecordRequest struct {
	TaskID          int                      `json:"task_id" validate:"required"`
	AgentType       string                   `json:"agent_type" validate:"required"`
	InputContext    map[string]interface{}   `json:"input_context"`
	ExecutionSteps  []map[string]interface{} `json:"execution_steps"`
	OutputResult    map[string]interface{}   `json:"output_result"`
	SuccessScore    float64                  `json:"success_score" validate:"required,min=0,max=1"`
	ExecutionTimeMs int                      `json:"execution_time_ms" validate:"min=0"`
	ErrorLog        []map[string]interface{} `json:"error_log,omitempty"`
}

// InteractionRecordResponse represents a response to recording an interaction
type InteractionRecordResponse struct {
	InteractionID    string `json:"interaction_id"`
	PatternExtracted bool   `json:"pattern_extracted"`
	PatternHash      string `json:"pattern_hash,omitempty"`
}

// KnowledgePattern represents a learned pattern
type KnowledgePattern struct {
	ID                 int                    `json:"id"`
	Name               string                 `json:"name"`
	Type               string                 `json:"type"`
	Description        string                 `json:"description"`
	ConfidenceScore    float64                `json:"confidence_score"`
	UsageCount         int                    `json:"usage_count"`
	LastSuccessfulUse  *time.Time             `json:"last_successful_use,omitempty"`
	Tags               []string               `json:"tags"`
	ContextSignature   map[string]interface{} `json:"context_signature"`
	SolutionTemplate   map[string]interface{} `json:"solution_template"`
	Prerequisites      map[string]interface{} `json:"prerequisites"`
	CreatedAt          time.Time              `json:"created_at"`
	UpdatedAt          time.Time              `json:"updated_at"`
}

// PatternVariation represents a variation of a knowledge pattern
type PatternVariation struct {
	ID                 int                    `json:"id"`
	BasePatternID      int                    `json:"base_pattern_id"`
	ContextDifferences map[string]interface{} `json:"context_differences"`
	Adaptations        map[string]interface{} `json:"adaptations"`
	SuccessRate        float64                `json:"success_rate"`
	UsageCount         int                    `json:"usage_count"`
	CreatedAt          time.Time              `json:"created_at"`
	UpdatedAt          time.Time              `json:"updated_at"`
}

// RecommendationRequest represents a request for solution recommendation
type RecommendationRequest struct {
	TaskContext   map[string]interface{} `json:"task_context"`
	AgentType     string                 `json:"agent_type" validate:"required"`
	MinConfidence float64                `json:"min_confidence" validate:"min=0,max=1"`
}

// LearningRecommendation represents an AI solution recommendation
type LearningRecommendation struct {
	Pattern               KnowledgePattern       `json:"pattern"`
	Confidence            float64                `json:"confidence"`
	AdaptedSolution       map[string]interface{} `json:"adapted_solution"`
	UsageHistory          []InteractionSummary   `json:"usage_history"`
	EstimatedSuccessRate  float64                `json:"estimated_success_rate"`
	Variation             *PatternVariation      `json:"variation,omitempty"`
}

// InteractionSummary represents a summary of an interaction
type InteractionSummary struct {
	Date            string  `json:"date"`
	SuccessScore    float64 `json:"success_score"`
	ExecutionTimeMs int     `json:"execution_time_ms"`
	TaskID          int     `json:"task_id"`
}

// SearchRequest represents a request to search for patterns
type SearchRequest struct {
	AgentType       string                 `json:"agent_type,omitempty"`
	Context         map[string]interface{} `json:"context,omitempty"`
	MinSuccessScore float64                `json:"min_success_score" validate:"min=0,max=1"`
	Limit           int                    `json:"limit" validate:"min=1,max=100"`
}

// SearchResponse represents search results
type SearchResponse struct {
	Patterns    []KnowledgePattern `json:"patterns"`
	TotalFound  int                `json:"total_found"`
	Returned    int                `json:"returned"`
}

// PaginatedPatternsResponse represents paginated patterns
type PaginatedPatternsResponse struct {
	Items      []KnowledgePattern `json:"items"`
	Pagination PaginationInfo     `json:"pagination"`
}

// PaginationInfo represents pagination information
type PaginationInfo struct {
	CurrentPage int `json:"current_page"`
	PerPage     int `json:"per_page"`
	TotalItems  int `json:"total_items"`
	TotalPages  int `json:"total_pages"`
}

// LearningAnalytics represents learning system analytics
type LearningAnalytics struct {
	InteractionMetrics     []InteractionMetric      `json:"interaction_metrics"`
	PatternAnalytics       []PatternAnalytic        `json:"pattern_analytics"`
	VariationStats         *VariationStats          `json:"variation_stats,omitempty"`
	LearningEffectiveness  *LearningEffectiveness   `json:"learning_effectiveness,omitempty"`
}

// InteractionMetric represents interaction metrics by agent type
type InteractionMetric struct {
	AgentType              string  `json:"agent_type"`
	TotalInteractions      int     `json:"total_interactions"`
	AvgSuccessScore        float64 `json:"avg_success_score"`
	AvgExecutionTime       float64 `json:"avg_execution_time"`
	SuccessfulInteractions int     `json:"successful_interactions"`
}

// PatternAnalytic represents pattern analytics by type
type PatternAnalytic struct {
	PatternType    string     `json:"pattern_type"`
	TotalPatterns  int        `json:"total_patterns"`
	AvgConfidence  float64    `json:"avg_confidence"`
	TotalUsage     int        `json:"total_usage"`
	LastUsed       *time.Time `json:"last_used,omitempty"`
}

// VariationStats represents pattern variation statistics
type VariationStats struct {
	TotalVariations  int     `json:"total_variations"`
	AvgSuccessRate   float64 `json:"avg_success_rate"`
	TotalUsage       int     `json:"total_usage"`
	MaxSuccessRate   float64 `json:"max_success_rate"`
	MinSuccessRate   float64 `json:"min_success_rate"`
}

// LearningEffectiveness represents learning system effectiveness metrics
type LearningEffectiveness struct {
	TotalInteractions int     `json:"total_interactions"`
	PatternsLearned   int     `json:"patterns_learned"`
	PatternReuses     int     `json:"pattern_reuses"`
	LearningRate      float64 `json:"learning_rate"`
	ReuseRate         float64 `json:"reuse_rate"`
}

// PatternFeedbackRequest represents feedback on a pattern
type PatternFeedbackRequest struct {
	FeedbackType string  `json:"feedback_type" validate:"required,oneof=success failure partial_success"`
	SuccessScore float64 `json:"success_score" validate:"required,min=0,max=1"`
	Comments     string  `json:"comments,omitempty"`
}

// PatternFeedbackResponse represents the response to pattern feedback
type PatternFeedbackResponse struct {
	FeedbackID string `json:"feedback_id"`
	Message    string `json:"message"`
}
