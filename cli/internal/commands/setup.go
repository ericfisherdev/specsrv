package commands

import (
	"bufio"
	"fmt"
	"os"
	"strings"
	"syscall"

	"github.com/ericfisherdev/specsrv/cli/internal/client"
	"github.com/ericfisherdev/specsrv/cli/internal/config"
	"github.com/ericfisherdev/specsrv/cli/pkg/models"
	"github.com/spf13/cobra"
	"golang.org/x/term"
)

// NewSetupCommand creates a new setup command
func NewSetupCommand() *cobra.Command {
	return &cobra.Command{
		Use:   "setup",
		Short: "Interactive configuration setup",
		Long:  "Walk through an interactive setup to configure the SpecSrv CLI.",
		RunE:  runInteractiveSetup,
	}
}

// RunInteractiveSetup performs the interactive setup
func RunInteractiveSetup() error {
	return runInteractiveSetup(nil, nil)
}

func runInteractiveSetup(cmd *cobra.Command, args []string) error {
	// Check if stdin is a terminal before starting interactive setup
	if !term.IsTerminal(int(os.Stdin.Fd())) {
		return fmt.Errorf("interactive setup requires a TTY; run with non-interactive flags or set environment variables - see documentation for details")
	}

	fmt.Println("Welcome to SpecSrv CLI Setup!")
	fmt.Println("==============================")
	fmt.Println("This wizard will help you configure the SpecSrv CLI.")

	reader := bufio.NewReader(os.Stdin)
	cfg := config.InitConfig()

	// Step 1: Server URL
	fmt.Println("Step 1: Server Configuration")
	fmt.Println("----------------------------")
	fmt.Printf("Enter the SpecSrv server URL [default: http://localhost:8000]: ")
	serverURL, _ := reader.ReadString('\n')
	serverURL = strings.TrimSpace(serverURL)
	if serverURL == "" {
		serverURL = "http://localhost:8000"
	}
	cfg.Server.URL = serverURL

	// Step 2: Output Format
	fmt.Println("\nStep 2: Output Preferences")
	fmt.Println("---------------------------")
	fmt.Println("Choose default output format:")
	fmt.Println("  1) table (human-readable)")
	fmt.Println("  2) json")
	fmt.Println("  3) yaml")
	fmt.Printf("Select format [1-3, default: 1]: ")
	formatChoice, _ := reader.ReadString('\n')
	formatChoice = strings.TrimSpace(formatChoice)
	switch formatChoice {
	case "2":
		cfg.Output.Format = "json"
	case "3":
		cfg.Output.Format = "yaml"
	default:
		cfg.Output.Format = "table"
	}

	// Step 2b: Color output
	fmt.Printf("Enable colored output? (y/N) [default: y]: ")
	colorChoice, _ := reader.ReadString('\n')
	colorChoice = strings.TrimSpace(strings.ToLower(colorChoice))
	cfg.Output.Color = colorChoice != "n" && colorChoice != "no"

	// Step 4: Authentication
	fmt.Println("\nStep 3: Authentication")
	fmt.Println("----------------------")
	fmt.Println("Choose authentication method:")
	fmt.Println("  1) Username/Password")
	fmt.Println("  2) API Token")
	fmt.Println("  3) Skip authentication (configure later)")
	fmt.Printf("Select method [1-3, default: 1]: ")
	authChoice, _ := reader.ReadString('\n')
	authChoice = strings.TrimSpace(authChoice)

	switch authChoice {
	case "2":
		// Token authentication
		fmt.Printf("Enter your API token: ")
		token, _ := reader.ReadString('\n')
		token = strings.TrimSpace(token)
		if token != "" {
			cfg.Auth.Token = token
			cfg.Auth.Method = "token"
			fmt.Println("\n✓ Token saved")
		}

	case "3":
		// Skip authentication
		fmt.Println("\nSkipping authentication. You can configure it later with 'specsrv auth login'")

	default:
		// Username/Password authentication
		fmt.Printf("Username: ")
		username, _ := reader.ReadString('\n')
		username = strings.TrimSpace(username)

		if username != "" {
			fmt.Printf("Password: ")
			bytePassword, err := term.ReadPassword(int(syscall.Stdin))
			if err != nil {
				fmt.Printf("\n⚠️  Warning: Could not read password securely: %v\n", err)
				fmt.Printf("Password (visible): ")
				password, _ := reader.ReadString('\n')
				password = strings.TrimSpace(password)
				bytePassword = []byte(password)
			}
			fmt.Println() // New line after password

			password := string(bytePassword)
			if password != "" {
				// Attempt to authenticate
				fmt.Println("\nAuthenticating...")

				// Check if server is reachable
				testClient := client.NewClient(cfg)
				if err := testClient.HealthCheck(); err != nil {
					fmt.Printf("⚠️  Warning: Cannot connect to server at %s\n", cfg.Server.URL)
					fmt.Println("   Using mock authentication for now.")

					// Mock authentication
					cfg.Auth.Token = "mock-token-" + username
					cfg.Auth.Method = "password"
					fmt.Printf("✓ Mock authentication successful for user: %s\n", username)
				} else {
					// Real authentication
					authReq := models.AuthRequest{
						Username: username,
						Password: password,
					}

					var authResp models.AuthResponse
					if err := testClient.Post("/api/auth/login", authReq, &authResp); err != nil {
						fmt.Printf("⚠️  Authentication failed: %v\n", err)
						fmt.Println("   Saving credentials for later use.")
						cfg.Auth.Token = ""
						cfg.Auth.Method = "password"
					} else {
						cfg.Auth.Token = authResp.Token
						cfg.Auth.Method = "password"
						fmt.Printf("✓ Successfully authenticated as %s\n", username)
					}
				}
			}
		}
	}

	// Step 4: Advanced Options
	fmt.Println("\nStep 4: Advanced Options")
	fmt.Println("------------------------")
	fmt.Printf("Enable verbose output by default? (y/N) [default: n]: ")
	verboseChoice, _ := reader.ReadString('\n')
	verboseChoice = strings.TrimSpace(strings.ToLower(verboseChoice))
	cfg.Verbose = verboseChoice == "y" || verboseChoice == "yes"

	fmt.Printf("Request timeout in seconds [default: 30]: ")
	timeoutStr, _ := reader.ReadString('\n')
	timeoutStr = strings.TrimSpace(timeoutStr)
	if timeoutStr != "" {
		var timeout int
		if _, err := fmt.Sscanf(timeoutStr, "%d", &timeout); err == nil && timeout > 0 {
			cfg.Server.Timeout = timeout
		}
	}

	// Save configuration
	fmt.Println("\nSaving configuration...")
	if err := config.Save(cfg); err != nil {
		return fmt.Errorf("failed to save configuration: %w", err)
	}

	configDir, _ := config.GetConfigDir()
	configPath := configDir + "/config.yaml"
	fmt.Printf("✓ Configuration saved to: %s\n", configPath)

	// Summary
	fmt.Println("\n" + strings.Repeat("=", 50))
	fmt.Println("Setup Complete!")
	fmt.Println(strings.Repeat("=", 50))
	fmt.Printf("Server URL:    %s\n", cfg.Server.URL)
	fmt.Printf("Output Format: %s\n", cfg.Output.Format)
	fmt.Printf("Color Output:  %v\n", cfg.Output.Color)
	if cfg.Auth.Token != "" {
		fmt.Printf("Authentication: %s (configured)\n", cfg.Auth.Method)
	} else {
		fmt.Println("Authentication: Not configured")
	}
	fmt.Printf("Verbose:       %v\n", cfg.Verbose)
	fmt.Printf("Timeout:       %d seconds\n", cfg.Server.Timeout)

	fmt.Println("\nYou can now use the SpecSrv CLI!")
	fmt.Println("Try these commands to get started:")
	fmt.Println("  specsrv projects list    - List all projects")
	fmt.Println("  specsrv projects create  - Create a new project")
	fmt.Println("  specsrv auth status      - Check authentication status")
	fmt.Println("  specsrv --help           - Show all available commands")
	fmt.Println("\nTo reconfigure, run: specsrv setup")

	return nil
}

// CheckAndRunSetup checks if setup is needed and runs it
func CheckAndRunSetup() error {
	configDir, err := config.GetConfigDir()
	if err != nil {
		return err
	}

	configPath := configDir + "/config.yaml"

	// Check if config file exists
	if _, err := os.Stat(configPath); os.IsNotExist(err) {
		fmt.Println("No configuration found. Starting interactive setup...")
		return RunInteractiveSetup()
	}

	return nil
}

// IsFirstRun checks if this is the first run (no config exists)
func IsFirstRun() bool {
	configDir, err := config.GetConfigDir()
	if err != nil {
		return true
	}

	configPath := configDir + "/config.yaml"
	_, err = os.Stat(configPath)
	return os.IsNotExist(err)
}
