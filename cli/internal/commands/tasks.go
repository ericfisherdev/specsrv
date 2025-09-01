package commands

import (
	"encoding/json"
	"fmt"
	"os"
	"strconv"
	"text/tabwriter"
	"time"

	"github.com/ericfisherdev/specsrv/cli/internal/client"
	"github.com/ericfisherdev/specsrv/cli/internal/config"
	"github.com/ericfisherdev/specsrv/cli/pkg/enums"
	"github.com/ericfisherdev/specsrv/cli/pkg/models"
	"github.com/spf13/cobra"
	"gopkg.in/yaml.v3"
)

// NewTasksCommand creates a new tasks command
func NewTasksCommand() *cobra.Command {
	cmd := &cobra.Command{
		Use:     "tasks",
		Aliases: []string{"task", "t"},
		Short:   "Manage tasks",
		Long:    "Create, read, update, and delete tasks in SpecSrv.",
	}

	// Add subcommands
	cmd.AddCommand(newTasksListCommand())
	cmd.AddCommand(newTasksShowCommand())
	cmd.AddCommand(newTasksCreateCommand())
	cmd.AddCommand(newTasksUpdateCommand())
	cmd.AddCommand(newTasksDeleteCommand())

	return cmd
}

// newTasksListCommand creates the tasks list subcommand
func newTasksListCommand() *cobra.Command {
	var (
		projectID int
		status    string
		priority  string
		search    string
	)

	cmd := &cobra.Command{
		Use:     "list",
		Aliases: []string{"ls"},
		Short:   "List all tasks",
		Long:    "List all tasks with optional filtering by project, status, priority, or search term.",
		RunE: func(cmd *cobra.Command, args []string) error {
			cfg, err := config.Load()
			if err != nil {
				return fmt.Errorf("failed to load config: %w", err)
			}

			_ = client.NewClient(cfg) // apiClient - will be used when connecting to real API

			// Build query parameters
			query := make(map[string]string)
			if projectID > 0 {
				query["project_id"] = strconv.Itoa(projectID)
			}
			if status != "" {
				query["status"] = status
			}
			if priority != "" {
				query["priority"] = priority
			}
			if search != "" {
				query["search"] = search
			}

			// Make API call (placeholder - would connect to real API)
			tasks := getMockTasks(projectID) // In real implementation, this would be apiClient.GetTasks(query)

			// Format output based on --output flag
			outputFormat := getOutputFormat()
			return formatTasksOutput(tasks, outputFormat)
		},
	}

	cmd.Flags().IntVar(&projectID, "project", 0, "filter by project ID")
	cmd.Flags().StringVar(&status, "status", "", "filter by status (backlog|todo|working|review|done)")
	cmd.Flags().StringVar(&priority, "priority", "", "filter by priority (low|medium|high|urgent)")
	cmd.Flags().StringVar(&search, "search", "", "search tasks by title or description")

	return cmd
}

// newTasksShowCommand creates the tasks show subcommand
func newTasksShowCommand() *cobra.Command {
	return &cobra.Command{
		Use:     "show <id>",
		Aliases: []string{"get"},
		Short:   "Show task details",
		Long:    "Display detailed information about a specific task.",
		Args:    cobra.ExactArgs(1),
		RunE: func(cmd *cobra.Command, args []string) error {
			id, err := strconv.Atoi(args[0])
			if err != nil {
				return fmt.Errorf("invalid task ID: %s", args[0])
			}

			cfg, err := config.Load()
			if err != nil {
				return fmt.Errorf("failed to load config: %w", err)
			}

			_ = client.NewClient(cfg) // apiClient - will be used when connecting to real API

			// Make API call (placeholder - would connect to real API)
			task := getMockTask(id) // In real implementation, this would be apiClient.GetTask(id)
			if task == nil {
				return fmt.Errorf("task with ID %d not found", id)
			}

			// Format output
			outputFormat := getOutputFormat()
			return formatTaskOutput(*task, outputFormat)
		},
	}
}

// newTasksCreateCommand creates the tasks create subcommand
func newTasksCreateCommand() *cobra.Command {
	var (
		projectID   int
		title       string
		description string
		status      string
		priority    string
		tags        []string
	)

	cmd := &cobra.Command{
		Use:   "create",
		Short: "Create a new task",
		Long:  "Create a new task with the specified title, description, status, and priority.",
		RunE: func(cmd *cobra.Command, args []string) error {
			if title == "" {
				return fmt.Errorf("task title is required")
			}
			if projectID == 0 {
				return fmt.Errorf("project ID is required")
			}

			cfg, err := config.Load()
			if err != nil {
				return fmt.Errorf("failed to load config: %w", err)
			}

			_ = client.NewClient(cfg) // apiClient - will be used when connecting to real API

			req := models.TaskCreateRequest{
				ProjectID:   projectID,
				Title:       title,
				Description: description,
				Status:      enums.TaskStatus(status),
				Priority:    enums.TaskPriority(priority),
				Tags:        tags,
			}

			// Make API call (placeholder - would connect to real API)
			task := createMockTask(req) // In real implementation, this would be apiClient.CreateTask(req)

			fmt.Printf("✓ Task created successfully (ID: %d)\n", task.ID)

			// Show the created task
			outputFormat := getOutputFormat()
			return formatTaskOutput(task, outputFormat)
		},
	}

	cmd.Flags().IntVarP(&projectID, "project", "p", 0, "project ID (required)")
	cmd.Flags().StringVarP(&title, "title", "t", "", "task title (required)")
	cmd.Flags().StringVarP(&description, "description", "d", "", "task description")
	cmd.Flags().StringVar(&status, "status", "todo", "task status (backlog|todo|working|review|done)")
	cmd.Flags().StringVar(&priority, "priority", "medium", "task priority (low|medium|high|urgent)")
	cmd.Flags().StringSliceVar(&tags, "tags", []string{}, "task tags (comma-separated)")
	cmd.MarkFlagRequired("title")
	cmd.MarkFlagRequired("project")

	return cmd
}

// newTasksUpdateCommand creates the tasks update subcommand
func newTasksUpdateCommand() *cobra.Command {
	var (
		title       string
		description string
		status      string
		priority    string
		tags        []string
	)

	cmd := &cobra.Command{
		Use:   "update <id>",
		Short: "Update an existing task",
		Long:  "Update an existing task's title, description, status, or priority.",
		Args:  cobra.ExactArgs(1),
		RunE: func(cmd *cobra.Command, args []string) error {
			id, err := strconv.Atoi(args[0])
			if err != nil {
				return fmt.Errorf("invalid task ID: %s", args[0])
			}

			cfg, err := config.Load()
			if err != nil {
				return fmt.Errorf("failed to load config: %w", err)
			}

			_ = client.NewClient(cfg) // apiClient - will be used when connecting to real API

			req := models.TaskUpdateRequest{}
			if title != "" {
				req.Title = &title
			}
			if description != "" {
				req.Description = &description
			}
			if status != "" {
				statusEnum := enums.TaskStatus(status)
				req.Status = &statusEnum
			}
			if priority != "" {
				priorityEnum := enums.TaskPriority(priority)
				req.Priority = &priorityEnum
			}
			if len(tags) > 0 {
				req.Tags = tags
			}

			// Make API call (placeholder - would connect to real API)
			task := updateMockTask(id, req) // In real implementation, this would be apiClient.UpdateTask(id, req)
			if task == nil {
				return fmt.Errorf("task with ID %d not found", id)
			}

			fmt.Printf("✓ Task updated successfully (ID: %d)\n", task.ID)

			// Show the updated task
			outputFormat := getOutputFormat()
			return formatTaskOutput(*task, outputFormat)
		},
	}

	cmd.Flags().StringVarP(&title, "title", "t", "", "task title")
	cmd.Flags().StringVarP(&description, "description", "d", "", "task description")
	cmd.Flags().StringVar(&status, "status", "", "task status (backlog|todo|working|review|done)")
	cmd.Flags().StringVar(&priority, "priority", "", "task priority (low|medium|high|urgent)")
	cmd.Flags().StringSliceVar(&tags, "tags", []string{}, "task tags (comma-separated)")

	return cmd
}

// newTasksDeleteCommand creates the tasks delete subcommand
func newTasksDeleteCommand() *cobra.Command {
	var force bool

	cmd := &cobra.Command{
		Use:   "delete <id>",
		Short: "Delete a task",
		Long:  "Delete a task and all its associated files (use --force to skip confirmation).",
		Args:  cobra.ExactArgs(1),
		RunE: func(cmd *cobra.Command, args []string) error {
			id, err := strconv.Atoi(args[0])
			if err != nil {
				return fmt.Errorf("invalid task ID: %s", args[0])
			}

			if !force {
				fmt.Printf("Are you sure you want to delete task %d? (y/N): ", id)
				var response string
				fmt.Scanln(&response)
				if response != "y" && response != "Y" {
					fmt.Println("Delete cancelled.")
					return nil
				}
			}

			cfg, err := config.Load()
			if err != nil {
				return fmt.Errorf("failed to load config: %w", err)
			}

			_ = client.NewClient(cfg) // apiClient - will be used when connecting to real API

			// Make API call (placeholder - would connect to real API)
			success := deleteMockTask(id) // In real implementation, this would be apiClient.DeleteTask(id)
			if !success {
				return fmt.Errorf("task with ID %d not found", id)
			}

			fmt.Printf("✓ Task %d deleted successfully\n", id)
			return nil
		},
	}

	cmd.Flags().BoolVar(&force, "force", false, "skip confirmation prompt")

	return cmd
}

// Formatting functions
func formatTasksOutput(tasks []models.Task, format string) error {
	switch format {
	case "json":
		return json.NewEncoder(os.Stdout).Encode(tasks)
	case "yaml":
		return yaml.NewEncoder(os.Stdout).Encode(tasks)
	default: // table
		return formatTasksTable(tasks)
	}
}

func formatTaskOutput(task models.Task, format string) error {
	switch format {
	case "json":
		return json.NewEncoder(os.Stdout).Encode(task)
	case "yaml":
		return yaml.NewEncoder(os.Stdout).Encode(task)
	default: // table
		return formatTaskTable(task)
	}
}

func formatTasksTable(tasks []models.Task) error {
	w := tabwriter.NewWriter(os.Stdout, 0, 0, 2, ' ', 0)
	fmt.Fprintln(w, "ID\tTITLE\tSTATUS\tPRIORITY\tPROJECT\tCREATED")
	for _, t := range tasks {
		fmt.Fprintf(w, "%d\t%s\t%s\t%s\t%d\t%s\n",
			t.ID, truncateString(t.Title, 40), t.Status, t.Priority, t.ProjectID, t.CreatedAt.Format("2006-01-02"))
	}
	return w.Flush()
}

func formatTaskTable(task models.Task) error {
	w := tabwriter.NewWriter(os.Stdout, 0, 0, 2, ' ', 0)
	fmt.Fprintf(w, "ID:\t%d\n", task.ID)
	fmt.Fprintf(w, "Project ID:\t%d\n", task.ProjectID)
	fmt.Fprintf(w, "Title:\t%s\n", task.Title)
	fmt.Fprintf(w, "Description:\t%s\n", task.Description)
	fmt.Fprintf(w, "Status:\t%s\n", task.Status)
	fmt.Fprintf(w, "Priority:\t%s\n", task.Priority)
	if len(task.Tags) > 0 {
		fmt.Fprintf(w, "Tags:\t%v\n", task.Tags)
	}
	fmt.Fprintf(w, "Created:\t%s\n", task.CreatedAt.Format("2006-01-02 15:04:05"))
	fmt.Fprintf(w, "Updated:\t%s\n", task.UpdatedAt.Format("2006-01-02 15:04:05"))
	return w.Flush()
}

// Mock data functions (would be replaced with real API calls)
var mockTaskID = 100

func getMockTasks(projectID int) []models.Task {
	allTasks := []models.Task{
		{ID: 1, ProjectID: 1, Title: "Fix authentication error handling in UserApiController", Description: "The login endpoint should return more specific error messages for failed authentication attempts. Currently it returns generic errors that don't help users understand what went wrong.", Status: "todo", Priority: "medium", Tags: []string{"nitpick", "api", "authentication"}, CreatedAt: parseTime("2024-01-01T10:00:00Z"), UpdatedAt: parseTime("2024-01-01T10:00:00Z")},
		{ID: 2, ProjectID: 1, Title: "Add input validation to project creation endpoint", Description: "The project creation API should validate all input fields before attempting to create the project. Missing validation could lead to database errors.", Status: "todo", Priority: "high", Tags: []string{"nitpick", "api", "validation"}, CreatedAt: parseTime("2024-01-01T10:30:00Z"), UpdatedAt: parseTime("2024-01-01T10:30:00Z")},
		{ID: 3, ProjectID: 1, Title: "Improve error messages in task management", Description: "Task creation and update operations should provide more descriptive error messages when validation fails.", Status: "backlog", Priority: "low", Tags: []string{"nitpick", "tasks", "ux"}, CreatedAt: parseTime("2024-01-01T11:00:00Z"), UpdatedAt: parseTime("2024-01-01T11:00:00Z")},
		{ID: 4, ProjectID: 2, Title: "Add CLI help text for all commands", Description: "Every CLI command should have comprehensive help text that explains usage, flags, and examples.", Status: "working", Priority: "medium", Tags: []string{"nitpick", "cli", "documentation"}, CreatedAt: parseTime("2024-01-02T09:00:00Z"), UpdatedAt: parseTime("2024-01-02T09:00:00Z")},
		{ID: 5, ProjectID: 2, Title: "Implement config file validation", Description: "The CLI should validate configuration files on startup and provide clear error messages for invalid configurations.", Status: "todo", Priority: "high", Tags: []string{"nitpick", "cli", "config"}, CreatedAt: parseTime("2024-01-02T09:30:00Z"), UpdatedAt: parseTime("2024-01-02T09:30:00Z")},
	}

	if projectID > 0 {
		var filtered []models.Task
		for _, task := range allTasks {
			if task.ProjectID == projectID {
				filtered = append(filtered, task)
			}
		}
		return filtered
	}

	return allTasks
}

func getMockTask(id int) *models.Task {
	tasks := getMockTasks(0)
	for i := range tasks {
		if tasks[i].ID == id {
			return &tasks[i]
		}
	}
	return nil
}

func createMockTask(req models.TaskCreateRequest) models.Task {
	mockTaskID++
	return models.Task{
		ID:          mockTaskID,
		ProjectID:   req.ProjectID,
		Title:       req.Title,
		Description: req.Description,
		Status:      req.Status,
		Priority:    req.Priority,
		Tags:        req.Tags,
		CreatedAt:   time.Now(),
		UpdatedAt:   time.Now(),
	}
}

func updateMockTask(id int, req models.TaskUpdateRequest) *models.Task {
	task := getMockTask(id)
	if task == nil {
		return nil
	}

	if req.Title != nil {
		task.Title = *req.Title
	}
	if req.Description != nil {
		task.Description = *req.Description
	}
	if req.Status != nil {
		task.Status = *req.Status
	}
	if req.Priority != nil {
		task.Priority = *req.Priority
	}
	if len(req.Tags) > 0 {
		task.Tags = req.Tags
	}

	task.UpdatedAt = time.Now()
	return task
}

func deleteMockTask(id int) bool {
	return getMockTask(id) != nil
}

func truncateString(s string, maxLen int) string {
	if len(s) <= maxLen {
		return s
	}
	return s[:maxLen-3] + "..."
}