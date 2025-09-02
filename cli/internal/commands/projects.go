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
	"github.com/spf13/viper"
	"gopkg.in/yaml.v3"
)

// getOutputFormat resolves the output format from both flag and config
func getOutputFormat() string {
	// First check the output flag
	if format := viper.GetString("output"); format != "" {
		return format
	}
	// Fallback to output.format config key
	return viper.GetString("output.format")
}

// NewProjectsCommand creates a new projects command
func NewProjectsCommand() *cobra.Command {
	cmd := &cobra.Command{
		Use:     "projects",
		Aliases: []string{"project", "proj"},
		Short:   "Manage projects",
		Long:    "Create, read, update, and delete projects in SpecSrv.",
	}

	// Add subcommands
	cmd.AddCommand(newProjectsListCommand())
	cmd.AddCommand(newProjectsShowCommand())
	cmd.AddCommand(newProjectsCreateCommand())
	cmd.AddCommand(newProjectsUpdateCommand())
	cmd.AddCommand(newProjectsDeleteCommand())

	return cmd
}

// newProjectsListCommand creates the projects list subcommand
func newProjectsListCommand() *cobra.Command {
	var (
		status string
		search string
	)

	cmd := &cobra.Command{
		Use:     "list",
		Aliases: []string{"ls"},
		Short:   "List all projects",
		Long:    "List all projects with optional filtering by status or search term.",
		RunE: func(cmd *cobra.Command, args []string) error {
			cfg, err := config.Load()
			if err != nil {
				return fmt.Errorf("failed to load config: %w", err)
			}

			apiClient := client.NewClient(cfg)

			// Build query parameters
			query := make(map[string]string)
			if status != "" {
				query["status"] = status
			}
			if search != "" {
				query["search"] = search
			}

			// Make API call to get projects
			projectsData, err := apiClient.GetProjects(query)
			if err != nil {
				return fmt.Errorf("failed to fetch projects: %w", err)
			}

			// Convert to models.Project for formatting
			projects := make([]models.Project, len(projectsData))
			for i, p := range projectsData {
				projects[i] = convertToProjectModel(p)
			}

			// Format output based on --output flag
			outputFormat := getOutputFormat()
			return formatProjectsOutput(projects, outputFormat)
		},
	}

	cmd.Flags().StringVar(&status, "status", "", "filter by status (active|inactive|archived)")
	cmd.Flags().StringVar(&search, "search", "", "search projects by name or description")

	return cmd
}

// newProjectsShowCommand creates the projects show subcommand
func newProjectsShowCommand() *cobra.Command {
	return &cobra.Command{
		Use:     "show <id>",
		Aliases: []string{"get"},
		Short:   "Show project details",
		Long:    "Display detailed information about a specific project.",
		Args:    cobra.ExactArgs(1),
		RunE: func(cmd *cobra.Command, args []string) error {
			id, err := strconv.Atoi(args[0])
			if err != nil {
				return fmt.Errorf("invalid project ID: %s", args[0])
			}

			cfg, err := config.Load()
			if err != nil {
				return fmt.Errorf("failed to load config: %w", err)
			}

			apiClient := client.NewClient(cfg)

			// Make API call to get project
			projectData, err := apiClient.GetProject(id)
			if err != nil {
				return fmt.Errorf("failed to fetch project %d: %w", id, err)
			}

			project := convertToProjectModel(projectData)

			// Format output
			outputFormat := getOutputFormat()
			return formatProjectOutput(project, outputFormat)
		},
	}
}

// newProjectsCreateCommand creates the projects create subcommand
func newProjectsCreateCommand() *cobra.Command {
	var (
		name        string
		description string
		status      string
	)

	cmd := &cobra.Command{
		Use:   "create",
		Short: "Create a new project",
		Long:  "Create a new project with the specified name, description, and status.",
		RunE: func(cmd *cobra.Command, args []string) error {
			if name == "" {
				return fmt.Errorf("project name is required")
			}

			cfg, err := config.Load()
			if err != nil {
				return fmt.Errorf("failed to load config: %w", err)
			}

			apiClient := client.NewClient(cfg)

			req := models.ProjectCreateRequest{
				Name:        name,
				Description: description,
				Status:      enums.ProjectStatus(status),
			}

			// Make API call to create project
			projectData, err := apiClient.CreateProject(req)
			if err != nil {
				return fmt.Errorf("failed to create project: %w", err)
			}

			project := convertToProjectModel(projectData)

			fmt.Printf("✓ Project created successfully (ID: %d)\n", project.ID)

			// Show the created project
			outputFormat := getOutputFormat()
			return formatProjectOutput(project, outputFormat)
		},
	}

	cmd.Flags().StringVarP(&name, "name", "n", "", "project name (required)")
	cmd.Flags().StringVarP(&description, "description", "d", "", "project description")
	cmd.Flags().StringVar(&status, "status", "active", "project status (active|inactive|archived)")
	cmd.MarkFlagRequired("name")

	return cmd
}

// newProjectsUpdateCommand creates the projects update subcommand
func newProjectsUpdateCommand() *cobra.Command {
	var (
		name        string
		description string
		status      string
	)

	cmd := &cobra.Command{
		Use:   "update <id>",
		Short: "Update an existing project",
		Long:  "Update an existing project's name, description, or status.",
		Args:  cobra.ExactArgs(1),
		RunE: func(cmd *cobra.Command, args []string) error {
			id, err := strconv.Atoi(args[0])
			if err != nil {
				return fmt.Errorf("invalid project ID: %s", args[0])
			}

			cfg, err := config.Load()
			if err != nil {
				return fmt.Errorf("failed to load config: %w", err)
			}

			apiClient := client.NewClient(cfg)

			req := models.ProjectUpdateRequest{}
			if name != "" {
				req.Name = &name
			}
			if description != "" {
				req.Description = &description
			}
			if status != "" {
				statusEnum := enums.ProjectStatus(status)
				req.Status = &statusEnum
			}

			// Make API call to update project
			projectData, err := apiClient.UpdateProject(id, req)
			if err != nil {
				return fmt.Errorf("failed to update project %d: %w", id, err)
			}

			project := convertToProjectModel(projectData)

			fmt.Printf("✓ Project updated successfully (ID: %d)\n", project.ID)

			// Show the updated project
			outputFormat := getOutputFormat()
			return formatProjectOutput(project, outputFormat)
		},
	}

	cmd.Flags().StringVarP(&name, "name", "n", "", "project name")
	cmd.Flags().StringVarP(&description, "description", "d", "", "project description")
	cmd.Flags().StringVar(&status, "status", "", "project status (active|inactive|archived)")

	return cmd
}

// newProjectsDeleteCommand creates the projects delete subcommand
func newProjectsDeleteCommand() *cobra.Command {
	var force bool

	cmd := &cobra.Command{
		Use:   "delete <id>",
		Short: "Delete a project",
		Long:  "Delete a project and all its associated tasks (use --force to skip confirmation).",
		Args:  cobra.ExactArgs(1),
		RunE: func(cmd *cobra.Command, args []string) error {
			id, err := strconv.Atoi(args[0])
			if err != nil {
				return fmt.Errorf("invalid project ID: %s", args[0])
			}

			if !force {
				fmt.Printf("Are you sure you want to delete project %d? (y/N): ", id)
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

			// Make API call to delete project
			err = apiClient.DeleteProject(id)
			if err != nil {
				return fmt.Errorf("failed to delete project %d: %w", id, err)
			}

			fmt.Printf("✓ Project %d deleted successfully\n", id)
			return nil
		},
	}

	cmd.Flags().BoolVar(&force, "force", false, "skip confirmation prompt")

	return cmd
}

// Formatting functions
func formatProjectsOutput(projects []models.Project, format string) error {
	switch format {
	case "json":
		return json.NewEncoder(os.Stdout).Encode(projects)
	case "yaml":
		return yaml.NewEncoder(os.Stdout).Encode(projects)
	default: // table
		return formatProjectsTable(projects)
	}
}

func formatProjectOutput(project models.Project, format string) error {
	switch format {
	case "json":
		return json.NewEncoder(os.Stdout).Encode(project)
	case "yaml":
		return yaml.NewEncoder(os.Stdout).Encode(project)
	default: // table
		return formatProjectTable(project)
	}
}

func formatProjectsTable(projects []models.Project) error {
	w := tabwriter.NewWriter(os.Stdout, 0, 0, 2, ' ', 0)
	fmt.Fprintln(w, "ID\tNAME\tSTATUS\tTASKS\tCREATED")
	for _, p := range projects {
		fmt.Fprintf(w, "%d\t%s\t%s\t%d\t%s\n",
			p.ID, p.Name, p.Status, p.TaskCount, p.CreatedAt.Format("2006-01-02"))
	}
	return w.Flush()
}

func formatProjectTable(project models.Project) error {
	w := tabwriter.NewWriter(os.Stdout, 0, 0, 2, ' ', 0)
	fmt.Fprintf(w, "ID:\t%d\n", project.ID)
	fmt.Fprintf(w, "Name:\t%s\n", project.Name)
	fmt.Fprintf(w, "Description:\t%s\n", project.Description)
	fmt.Fprintf(w, "Status:\t%s\n", project.Status)
	fmt.Fprintf(w, "Tasks:\t%d\n", project.TaskCount)
	fmt.Fprintf(w, "Created:\t%s\n", project.CreatedAt.Format("2006-01-02 15:04:05"))
	fmt.Fprintf(w, "Updated:\t%s\n", project.UpdatedAt.Format("2006-01-02 15:04:05"))
	return w.Flush()
}

// convertToProjectModel converts API response data to Project model
func convertToProjectModel(data map[string]interface{}) models.Project {
	project := models.Project{}
	
	if id, ok := data["id"].(float64); ok {
		project.ID = int(id)
	}
	if name, ok := data["name"].(string); ok {
		project.Name = name
	}
	if description, ok := data["description"].(string); ok {
		project.Description = description
	}
	if status, ok := data["status"].(string); ok {
		project.Status = enums.ProjectStatus(status)
	}
	if taskCount, ok := data["taskCount"].(float64); ok {
		project.TaskCount = int(taskCount)
	} else if taskCount, ok := data["task_count"].(float64); ok {
		project.TaskCount = int(taskCount)
	}
	
	// Parse timestamps
	if createdAt, ok := data["createdAt"].(string); ok {
		project.CreatedAt = parseTime(createdAt)
	} else if createdAt, ok := data["created_at"].(string); ok {
		project.CreatedAt = parseTime(createdAt)
	}
	if updatedAt, ok := data["updatedAt"].(string); ok {
		project.UpdatedAt = parseTime(updatedAt)
	} else if updatedAt, ok := data["updated_at"].(string); ok {
		project.UpdatedAt = parseTime(updatedAt)
	}
	
	return project
}

func parseTime(timeStr string) time.Time {
	t, _ := time.Parse(time.RFC3339, timeStr)
	return t
}
