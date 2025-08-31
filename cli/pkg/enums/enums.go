package enums

import (
	"encoding/json"
	"fmt"
)

// TaskStatus represents the status of a task
type TaskStatus string

const (
	TaskStatusBacklog TaskStatus = "backlog"
	TaskStatusTodo    TaskStatus = "todo"
	TaskStatusWorking TaskStatus = "working"
	TaskStatusReview  TaskStatus = "review"
	TaskStatusDone    TaskStatus = "done"
)

// String returns the string representation of TaskStatus
func (ts TaskStatus) String() string {
	return string(ts)
}

// MarshalJSON implements json.Marshaler
func (ts TaskStatus) MarshalJSON() ([]byte, error) {
	return json.Marshal(string(ts))
}

// UnmarshalJSON implements json.Unmarshaler
func (ts *TaskStatus) UnmarshalJSON(data []byte) error {
	var s string
	if err := json.Unmarshal(data, &s); err != nil {
		return err
	}
	
	switch s {
	case "backlog", "todo", "working", "review", "done":
		*ts = TaskStatus(s)
		return nil
	default:
		return fmt.Errorf("invalid task status: %s", s)
	}
}

// TaskPriority represents the priority of a task
type TaskPriority string

const (
	TaskPriorityLow    TaskPriority = "low"
	TaskPriorityMedium TaskPriority = "medium"
	TaskPriorityHigh   TaskPriority = "high"
	TaskPriorityUrgent TaskPriority = "urgent"
)

// String returns the string representation of TaskPriority
func (tp TaskPriority) String() string {
	return string(tp)
}

// MarshalJSON implements json.Marshaler
func (tp TaskPriority) MarshalJSON() ([]byte, error) {
	return json.Marshal(string(tp))
}

// UnmarshalJSON implements json.Unmarshaler
func (tp *TaskPriority) UnmarshalJSON(data []byte) error {
	var s string
	if err := json.Unmarshal(data, &s); err != nil {
		return err
	}
	
	switch s {
	case "low", "medium", "high", "urgent":
		*tp = TaskPriority(s)
		return nil
	default:
		return fmt.Errorf("invalid task priority: %s", s)
	}
}

// ProjectStatus represents the status of a project
type ProjectStatus string

const (
	ProjectStatusActive   ProjectStatus = "active"
	ProjectStatusInactive ProjectStatus = "inactive"
	ProjectStatusArchived ProjectStatus = "archived"
)

// String returns the string representation of ProjectStatus
func (ps ProjectStatus) String() string {
	return string(ps)
}

// MarshalJSON implements json.Marshaler
func (ps ProjectStatus) MarshalJSON() ([]byte, error) {
	return json.Marshal(string(ps))
}

// UnmarshalJSON implements json.Unmarshaler
func (ps *ProjectStatus) UnmarshalJSON(data []byte) error {
	var s string
	if err := json.Unmarshal(data, &s); err != nil {
		return err
	}
	
	switch s {
	case "active", "inactive", "archived":
		*ps = ProjectStatus(s)
		return nil
	default:
		return fmt.Errorf("invalid project status: %s", s)
	}
}