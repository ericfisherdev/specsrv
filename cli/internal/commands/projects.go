package commands

import (
	"encoding/json"
	"fmt"
	"os"
	"strconv"
	"text/tabwriter"
	"time"

	"github.com/specsrv/specsrv-cli/internal/client"
	"github.com/specsrv/specsrv-cli/internal/config"
	"github.com/specsrv/specsrv-cli/pkg/models"
	"github.com/spf13/cobra"
	"github.com/spf13/viper"
)

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

			_ = client.NewClient(cfg) // apiClient - will be used when connecting to real API

			// Build query parameters
			query := make(map[string]string)
			if status != "" {
				query["status"] = status
			}
			if search != "" {
				query["search"] = search
			}

			// Make API call (placeholder - would connect to real API)
			projects := getMockProjects() // In real implementation, this would be apiClient.GetProjects(query)

			// Format output based on --output flag
			outputFormat := viper.GetString("output")
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

			_ = client.NewClient(cfg) // apiClient - will be used when connecting to real API

			// Make API call (placeholder - would connect to real API)
			project := getMockProject(id) // In real implementation, this would be apiClient.GetProject(id)
			if project == nil {
				return fmt.Errorf("project with ID %d not found", id)
			}

			// Format output
			outputFormat := viper.GetString("output")
			return formatProjectOutput(*project, outputFormat)
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

			_ = client.NewClient(cfg) // apiClient - will be used when connecting to real API

			req := models.ProjectCreateRequest{
				Name:        name,
				Description: description,
				Status:      status,
			}

			// Make API call (placeholder - would connect to real API)
			project := createMockProject(req) // In real implementation, this would be apiClient.CreateProject(req)

			fmt.Printf("✓ Project created successfully (ID: %d)\n", project.ID)

			// Show the created project
			outputFormat := viper.GetString("output")
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

			_ = client.NewClient(cfg) // apiClient - will be used when connecting to real API

			req := models.ProjectUpdateRequest{}
			if name != "" {
				req.Name = &name
			}
			if description != "" {
				req.Description = &description
			}
			if status != "" {
				req.Status = &status
			}

			// Make API call (placeholder - would connect to real API)
			project := updateMockProject(id, req) // In real implementation, this would be apiClient.UpdateProject(id, req)
			if project == nil {
				return fmt.Errorf("project with ID %d not found", id)
			}

			fmt.Printf("✓ Project updated successfully (ID: %d)\n", project.ID)

			// Show the updated project
			outputFormat := viper.GetString("output")
			return formatProjectOutput(*project, outputFormat)
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

			_ = client.NewClient(cfg) // apiClient - will be used when connecting to real API

			// Make API call (placeholder - would connect to real API)
			success := deleteMockProject(id) // In real implementation, this would be apiClient.DeleteProject(id)
			if !success {
				return fmt.Errorf("project with ID %d not found", id)
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
		// Would implement YAML output
		fmt.Println("YAML output not implemented yet")
		return nil
	default: // table
		return formatProjectsTable(projects)
	}
}

func formatProjectOutput(project models.Project, format string) error {
	switch format {
	case "json":
		return json.NewEncoder(os.Stdout).Encode(project)
	case "yaml":
		// Would implement YAML output
		fmt.Println("YAML output not implemented yet")
		return nil
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

// Mock data functions (would be replaced with real API calls)
func getMockProjects() []models.Project {
	return []models.Project{
		{ID: 1, Name: "SpecSrv Development", Description: "Main development project", Status: "active", TaskCount: 15, CreatedAt: parseTime("2024-01-01T00:00:00Z"), UpdatedAt: parseTime("2024-01-15T00:00:00Z")},
		{ID: 2, Name: "CLI Tool", Description: "Command line interface", Status: "active", TaskCount: 8, CreatedAt: parseTime("2024-01-10T00:00:00Z"), UpdatedAt: parseTime("2024-01-20T00:00:00Z")},
		{ID: 3, Name: "Documentation", Description: "Project documentation", Status: "inactive", TaskCount: 3, CreatedAt: parseTime("2024-01-05T00:00:00Z"), UpdatedAt: parseTime("2024-01-12T00:00:00Z")},
	}
}

func getMockProject(id int) *models.Project {
	projects := getMockProjects()
	for _, p := range projects {
		if p.ID == id {
			return &p
		}
	}
	return nil
}

func createMockProject(req models.ProjectCreateRequest) models.Project {
	return models.Project{
		ID:          4,
		Name:        req.Name,
		Description: req.Description,
		Status:      req.Status,
		TaskCount:   0,
		CreatedAt:   parseTime("2024-01-25T00:00:00Z"),
		UpdatedAt:   parseTime("2024-01-25T00:00:00Z"),
	}
}

func updateMockProject(id int, req models.ProjectUpdateRequest) *models.Project {
	project := getMockProject(id)
	if project == nil {
		return nil
	}

	if req.Name != nil {
		project.Name = *req.Name
	}
	if req.Description != nil {
		project.Description = *req.Description
	}
	if req.Status != nil {
		project.Status = *req.Status
	}

	project.UpdatedAt = parseTime("2024-01-26T00:00:00Z")
	return project
}

func deleteMockProject(id int) bool {
	return getMockProject(id) != nil
}

func parseTime(timeStr string) time.Time {
	t, _ := time.Parse(time.RFC3339, timeStr)
	return t
}
