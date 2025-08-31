package models

import "time"

// Project represents a project in the system
type Project struct {
	ID          int       `json:"id"`
	Name        string    `json:"name"`
	Description string    `json:"description"`
	Status      string    `json:"status"`
	CreatedAt   time.Time `json:"created_at"`
	UpdatedAt   time.Time `json:"updated_at"`
	TaskCount   int       `json:"task_count,omitempty"`
}

// ProjectCreateRequest represents a request to create a new project
type ProjectCreateRequest struct {
	Name        string `json:"name" validate:"required,min=1,max=100"`
	Description string `json:"description" validate:"max=500"`
	Status      string `json:"status" validate:"omitempty,oneof=active inactive archived"`
}

// ProjectUpdateRequest represents a request to update a project
type ProjectUpdateRequest struct {
	Name        *string `json:"name,omitempty" validate:"omitempty,min=1,max=100"`
	Description *string `json:"description,omitempty" validate:"omitempty,max=500"`
	Status      *string `json:"status,omitempty" validate:"omitempty,oneof=active inactive archived"`
}

// Task represents a task in the system
type Task struct {
	ID          int        `json:"id"`
	ProjectID   int        `json:"project_id"`
	Title       string     `json:"title"`
	Description string     `json:"description"`
	Status      string     `json:"status"`
	Priority    string     `json:"priority"`
	AssigneeID  *int       `json:"assignee_id,omitempty"`
	CreatedAt   time.Time  `json:"created_at"`
	UpdatedAt   time.Time  `json:"updated_at"`
	DueDate     *time.Time `json:"due_date,omitempty"`
	Tags        []string   `json:"tags,omitempty"`
	Files       []File     `json:"files,omitempty"`
	GitLinks    []GitLink  `json:"git_links,omitempty"`
	AISummary   string     `json:"ai_summary,omitempty"`
}

// TaskCreateRequest represents a request to create a new task
type TaskCreateRequest struct {
	ProjectID   int        `json:"project_id" validate:"required"`
	Title       string     `json:"title" validate:"required,min=1,max=200"`
	Description string     `json:"description" validate:"max=2000"`
	Status      string     `json:"status" validate:"omitempty,oneof=backlog todo working review done"`
	Priority    string     `json:"priority" validate:"omitempty,oneof=low medium high urgent"`
	AssigneeID  *int       `json:"assignee_id,omitempty"`
	DueDate     *time.Time `json:"due_date,omitempty"`
	Tags        []string   `json:"tags,omitempty"`
}

// TaskUpdateRequest represents a request to update a task
type TaskUpdateRequest struct {
	Title       *string    `json:"title,omitempty" validate:"omitempty,min=1,max=200"`
	Description *string    `json:"description,omitempty" validate:"omitempty,max=2000"`
	Status      *string    `json:"status,omitempty" validate:"omitempty,oneof=backlog todo working review done"`
	Priority    *string    `json:"priority,omitempty" validate:"omitempty,oneof=low medium high urgent"`
	AssigneeID  *int       `json:"assignee_id,omitempty"`
	DueDate     *time.Time `json:"due_date,omitempty"`
	Tags        []string   `json:"tags,omitempty"`
	AISummary   *string    `json:"ai_summary,omitempty"`
}

// File represents a file attachment
type File struct {
	ID         int       `json:"id"`
	EntityType string    `json:"entity_type"` // "project" or "task"
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
	Token     string `json:"token"`
	ExpiresAt string `json:"expires_at"`
	User      User   `json:"user"`
}

// ListOptions represents common options for list operations
type ListOptions struct {
	Page     int    `json:"page"`
	PerPage  int    `json:"per_page"`
	Sort     string `json:"sort"`
	Order    string `json:"order"`
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
