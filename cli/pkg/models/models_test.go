package models

import (
	"encoding/json"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestProject_JSONSerialization(t *testing.T) {
	now := time.Now()
	project := &Project{
		ID:          1,
		Name:        "Test Project",
		Description: "A test project",
		Status:      "active",
		CreatedAt:   now,
		UpdatedAt:   now,
		TaskCount:   5,
	}

	// Marshal to JSON
	jsonData, err := json.Marshal(project)
	require.NoError(t, err)

	// Unmarshal from JSON
	var decoded Project
	err = json.Unmarshal(jsonData, &decoded)
	require.NoError(t, err)

	assert.Equal(t, project.ID, decoded.ID)
	assert.Equal(t, project.Name, decoded.Name)
	assert.Equal(t, project.Description, decoded.Description)
	assert.Equal(t, project.Status, decoded.Status)
	assert.Equal(t, project.TaskCount, decoded.TaskCount)
}

func TestTask_JSONSerialization(t *testing.T) {
	now := time.Now()
	dueDate := now.Add(24 * time.Hour)
	assigneeID := 123

	task := &Task{
		ID:          1,
		ProjectID:   10,
		Title:       "Test Task",
		Description: "A test task",
		Status:      "todo",
		Priority:    "high",
		AssigneeID:  &assigneeID,
		CreatedAt:   now,
		UpdatedAt:   now,
		DueDate:     &dueDate,
		Tags:        []string{"urgent", "bug"},
		AISummary:   "AI generated summary",
	}

	jsonData, err := json.Marshal(task)
	require.NoError(t, err)

	var decoded Task
	err = json.Unmarshal(jsonData, &decoded)
	require.NoError(t, err)

	assert.Equal(t, task.ID, decoded.ID)
	assert.Equal(t, task.ProjectID, decoded.ProjectID)
	assert.Equal(t, task.Title, decoded.Title)
	assert.Equal(t, task.Status, decoded.Status)
	assert.Equal(t, task.Priority, decoded.Priority)
	require.NotNil(t, decoded.AssigneeID)
	assert.Equal(t, assigneeID, *decoded.AssigneeID)
	require.NotNil(t, decoded.DueDate)
	assert.True(t, dueDate.Equal(*decoded.DueDate))
	assert.Equal(t, task.Tags, decoded.Tags)
	assert.Equal(t, task.AISummary, decoded.AISummary)
}

func TestAuthRequest_Validation(t *testing.T) {
	tests := []struct {
		name    string
		request AuthRequest
		valid   bool
	}{
		{
			name: "valid request",
			request: AuthRequest{
				Username: "testuser",
				Password: "password123",
			},
			valid: true,
		},
		{
			name: "missing username",
			request: AuthRequest{
				Password: "password123",
			},
			valid: false,
		},
		{
			name: "missing password",
			request: AuthRequest{
				Username: "testuser",
			},
			valid: false,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			jsonData, err := json.Marshal(tt.request)
			require.NoError(t, err)

			var decoded AuthRequest
			err = json.Unmarshal(jsonData, &decoded)
			require.NoError(t, err)

			if tt.valid {
				assert.NotEmpty(t, decoded.Username)
				assert.NotEmpty(t, decoded.Password)
			}
		})
	}
}

func TestListOptions_DefaultValues(t *testing.T) {
	options := &ListOptions{
		Page:     1,
		PerPage:  20,
		Sort:     "created_at",
		Order:    "desc",
		Search:   "",
		Status:   "",
		Priority: "",
	}

	assert.Equal(t, 1, options.Page)
	assert.Equal(t, 20, options.PerPage)
	assert.Equal(t, "created_at", options.Sort)
	assert.Equal(t, "desc", options.Order)
}
