package commands

import (
	"encoding/json"
	"fmt"
	"os"
	"strconv"
	"text/tabwriter"

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

			apiClient := client.NewClient(cfg)

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

			// Make API call to get tasks
			tasksData, err := apiClient.GetTasks(query)
			if err != nil {
				return fmt.Errorf("failed to fetch tasks: %w", err)
			}

			// Convert to models.Task for formatting
			tasks := make([]models.Task, len(tasksData))
			for i, t := range tasksData {
				tasks[i] = convertToTaskModel(t)
			}

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

			apiClient := client.NewClient(cfg)

			// Make API call to get task
			taskData, err := apiClient.GetTask(id)
			if err != nil {
				return fmt.Errorf("failed to fetch task %d: %w", id, err)
			}

			task := convertToTaskModel(taskData)

			// Format output
			outputFormat := getOutputFormat()
			return formatTaskOutput(task, outputFormat)
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

			apiClient := client.NewClient(cfg)

			req := models.TaskCreateRequest{
				ProjectID:   projectID,
				Title:       title,
				Description: description,
				Status:      enums.TaskStatus(status),
				Priority:    enums.TaskPriority(priority),
				Tags:        tags,
			}

			// Make API call to create task
			taskData, err := apiClient.CreateTask(req)
			if err != nil {
				return fmt.Errorf("failed to create task: %w", err)
			}

			task := convertToTaskModel(taskData)

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

			apiClient := client.NewClient(cfg)

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

			// Make API call to update task
			taskData, err := apiClient.UpdateTask(id, req)
			if err != nil {
				return fmt.Errorf("failed to update task %d: %w", id, err)
			}

			task := convertToTaskModel(taskData)

			fmt.Printf("✓ Task updated successfully (ID: %d)\n", task.ID)

			// Show the updated task
			outputFormat := getOutputFormat()
			return formatTaskOutput(task, outputFormat)
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

			apiClient := client.NewClient(cfg)

			// Make API call to delete task
			err = apiClient.DeleteTask(id)
			if err != nil {
				return fmt.Errorf("failed to delete task %d: %w", id, err)
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

// convertToTaskModel converts API response data to Task model
func convertToTaskModel(data map[string]interface{}) models.Task {
	task := models.Task{}

	if id, ok := data["id"].(float64); ok {
		task.ID = int(id)
	}
	if projectID, ok := data["projectId"].(float64); ok {
		task.ProjectID = int(projectID)
	} else if projectID, ok := data["project_id"].(float64); ok {
		task.ProjectID = int(projectID)
	}
	if title, ok := data["title"].(string); ok {
		task.Title = title
	}
	if description, ok := data["description"].(string); ok {
		task.Description = description
	}
	if status, ok := data["status"].(string); ok {
		task.Status = enums.TaskStatus(status)
	}
	if priority, ok := data["priority"].(string); ok {
		task.Priority = enums.TaskPriority(priority)
	}

	// Handle tags array
	if tagsInterface, ok := data["tags"]; ok {
		if tagsArray, ok := tagsInterface.([]interface{}); ok {
			tags := make([]string, len(tagsArray))
			for i, tag := range tagsArray {
				if tagStr, ok := tag.(string); ok {
					tags[i] = tagStr
				}
			}
			task.Tags = tags
		}
	}

	// Parse timestamps
	if createdAt, ok := data["createdAt"].(string); ok {
		task.CreatedAt = parseTime(createdAt)
	} else if createdAt, ok := data["created_at"].(string); ok {
		task.CreatedAt = parseTime(createdAt)
	}
	if updatedAt, ok := data["updatedAt"].(string); ok {
		task.UpdatedAt = parseTime(updatedAt)
	} else if updatedAt, ok := data["updated_at"].(string); ok {
		task.UpdatedAt = parseTime(updatedAt)
	}

	return task
}

func truncateString(s string, maxLen int) string {
	if len(s) <= maxLen {
		return s
	}
	return s[:maxLen-3] + "..."
}
