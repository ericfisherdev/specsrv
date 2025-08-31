package specsrv

import (
	"fmt"
	"os"
	"path/filepath"
	"strings"

	"github.com/ericfisherdev/specsrv/cli/internal/commands"
	"github.com/spf13/cobra"
	"github.com/spf13/viper"
)

var (
	cfgFile   string
	skipSetup bool

	rootCmd = &cobra.Command{
		Use:   "specsrv",
		Short: "SpecSrv CLI - Task management for developers and AI agents",
		Long: `SpecSrv is a comprehensive task management system designed for developers
and AI agents. This CLI provides full CRUD operations, file management,
and seamless integration with development workflows.

The CLI connects to a containerized SpecSrv backend via REST API calls,
allowing you to manage projects, tasks, and files from the command line.`,
		SilenceUsage:  true,
		SilenceErrors: true,
		PersistentPreRunE: func(cmd *cobra.Command, args []string) error {
			// Skip setup check for certain commands
			if cmd.Name() == "setup" || cmd.Name() == "version" || cmd.Name() == "help" || cmd.Name() == "completion" {
				return nil
			}

			// Skip if --skip-setup flag is set
			if skipSetup {
				return nil
			}

			// Check if this is first run and no args provided (just "specsrv")
			if commands.IsFirstRun() && len(args) == 0 && cmd.Name() == "specsrv" && len(os.Args) == 1 {
				return commands.RunInteractiveSetup()
			}

			return nil
		},
		Run: func(cmd *cobra.Command, args []string) {
			// If no subcommand is provided and it's first run, run setup
			if commands.IsFirstRun() && !skipSetup {
				if err := commands.RunInteractiveSetup(); err != nil {
					fmt.Fprintf(os.Stderr, "Setup failed: %v\n", err)
					os.Exit(1)
				}
			} else {
				// Otherwise show help
				cmd.Help()
			}
		},
	}
)

// Execute runs the root command
func Execute() {
	if err := rootCmd.Execute(); err != nil {
		fmt.Fprintf(os.Stderr, "Error: %v\n", err)
		os.Exit(1)
	}
}

func init() {
	cobra.OnInitialize(initConfig)

	// Global flags
	rootCmd.PersistentFlags().StringVar(&cfgFile, "config", "", "config file (default is $HOME/.specsrv/config.yaml)")
	rootCmd.PersistentFlags().BoolP("verbose", "v", false, "verbose output")
	rootCmd.PersistentFlags().StringP("output", "o", "table", "output format (table|json|yaml)")
	rootCmd.PersistentFlags().StringP("server", "s", "", "SpecSrv server URL (overrides config)")
	rootCmd.PersistentFlags().BoolVar(&skipSetup, "skip-setup", false, "skip interactive setup check")

	// Bind flags to viper
	viper.BindPFlag("verbose", rootCmd.PersistentFlags().Lookup("verbose"))
	viper.BindPFlag("output.format", rootCmd.PersistentFlags().Lookup("output"))
	viper.BindPFlag("server.url", rootCmd.PersistentFlags().Lookup("server"))

	// Add subcommands
	addSubcommands()
}

func addSubcommands() {
	// Import commands package
	commands := getCommands()

	// Add subcommands
	rootCmd.AddCommand(commands.version)
	rootCmd.AddCommand(commands.setup)
	rootCmd.AddCommand(commands.auth)
	rootCmd.AddCommand(commands.projects)
	rootCmd.AddCommand(commands.completion)
	// We'll add more subcommands here as we create them
	// rootCmd.AddCommand(commands.tasks)
	// rootCmd.AddCommand(commands.files)
	// rootCmd.AddCommand(commands.config)
}

// commandSet holds all available commands
type commandSet struct {
	version    *cobra.Command
	projects   *cobra.Command
	auth       *cobra.Command
	setup      *cobra.Command
	completion *cobra.Command
}

// getCommands returns all available commands
func getCommands() *commandSet {
	return &commandSet{
		version:    commands.NewVersionCommand(),
		projects:   commands.NewProjectsCommand(),
		auth:       commands.NewAuthCommand(),
		setup:      commands.NewSetupCommand(),
		completion: commands.NewCompletionCommand(),
	}
}

func initConfig() {
	if cfgFile != "" {
		viper.SetConfigFile(cfgFile)
	} else {
		home, err := os.UserHomeDir()
		cobra.CheckErr(err)

		configDir := filepath.Join(home, ".specsrv")
		if err := os.MkdirAll(configDir, 0755); err != nil {
			fmt.Fprintf(os.Stderr, "Warning: Could not create config directory: %v\n", err)
		}

		viper.AddConfigPath(configDir)
		viper.SetConfigType("yaml")
		viper.SetConfigName("config")
	}

	viper.SetEnvPrefix("SPECSRV")
	viper.SetEnvKeyReplacer(strings.NewReplacer(".", "_"))
	viper.AutomaticEnv()

	// Set default values
	viper.SetDefault("server.url", "http://localhost:8000")
	viper.SetDefault("output.format", "table")
	viper.SetDefault("verbose", false)

	if err := viper.ReadInConfig(); err == nil && viper.GetBool("verbose") {
		fmt.Fprintf(os.Stderr, "Using config file: %s\n", viper.ConfigFileUsed())
	}
}
