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

// NewAuthCommand creates a new auth command
func NewAuthCommand() *cobra.Command {
	cmd := &cobra.Command{
		Use:   "auth",
		Short: "Manage authentication",
		Long:  "Login, logout, and check authentication status with the SpecSrv backend.",
	}

	// Add subcommands
	cmd.AddCommand(newAuthLoginCommand())
	cmd.AddCommand(newAuthLogoutCommand())
	cmd.AddCommand(newAuthStatusCommand())

	return cmd
}

// newAuthLoginCommand creates the auth login subcommand
func newAuthLoginCommand() *cobra.Command {
	var (
		username string
		password string
		token    string
	)

	cmd := &cobra.Command{
		Use:   "login",
		Short: "Login to SpecSrv backend",
		Long:  "Authenticate with the SpecSrv backend using username/password or token.",
		RunE: func(cmd *cobra.Command, args []string) error {
			cfg, err := config.Load()
			if err != nil {
				return fmt.Errorf("failed to load config: %w", err)
			}

			// If token is provided directly, save it
			if token != "" {
				cfg.Auth.Token = token
				cfg.Auth.Method = "token"

				if err := config.Save(cfg); err != nil {
					return fmt.Errorf("failed to save config: %w", err)
				}

				fmt.Println("✓ Token saved successfully")
				return testAuthentication(cfg)
			}

			// Interactive login flow
			if username == "" {
				fmt.Print("Username: ")
				reader := bufio.NewReader(os.Stdin)
				username, _ = reader.ReadString('\n')
				username = strings.TrimSpace(username)
			}

			if password == "" {
				fmt.Print("Password: ")
				bytePassword, err := term.ReadPassword(int(syscall.Stdin))
				if err != nil {
					return fmt.Errorf("failed to read password: %w", err)
				}
				password = string(bytePassword)
				fmt.Println() // New line after password input
			}

			// Create API client and attempt login
			apiClient := client.NewClient(cfg)

			authReq := models.AuthRequest{
				Username: username,
				Password: password,
			}

			// Make real API call to authenticate
			var authResp models.AuthResponse
			err = apiClient.Post("/api/auth/login", authReq, &authResp)
			if err != nil {
				return fmt.Errorf("authentication failed: %w", err)
			}

			// Save the token from the response
			cfg.Auth.Token = authResp.Token
			cfg.Auth.Method = "password"
			apiClient.SetToken(authResp.Token)

			if err := config.Save(cfg); err != nil {
				return fmt.Errorf("failed to save config: %w", err)
			}

			fmt.Printf("\n✓ Successfully logged in as %s\n", username)
			fmt.Printf("✓ Token saved to config file\n")

			return testAuthentication(cfg)
		},
	}

	cmd.Flags().StringVarP(&username, "username", "u", "", "Username for authentication")
	cmd.Flags().StringVarP(&password, "password", "p", "", "Password for authentication")
	cmd.Flags().StringVarP(&token, "token", "t", "", "API token (alternative to username/password)")

	return cmd
}

// newAuthLogoutCommand creates the auth logout subcommand
func newAuthLogoutCommand() *cobra.Command {
	return &cobra.Command{
		Use:   "logout",
		Short: "Logout from SpecSrv backend",
		Long:  "Clear stored authentication credentials.",
		RunE: func(cmd *cobra.Command, args []string) error {
			cfg, err := config.Load()
			if err != nil {
				return fmt.Errorf("failed to load config: %w", err)
			}

			// Clear authentication
			cfg.Auth.Token = ""
			cfg.Auth.Method = ""

			if err := config.Save(cfg); err != nil {
				return fmt.Errorf("failed to save config: %w", err)
			}

			fmt.Println("✓ Successfully logged out")
			fmt.Println("✓ Authentication credentials cleared")

			return nil
		},
	}
}

// newAuthStatusCommand creates the auth status subcommand
func newAuthStatusCommand() *cobra.Command {
	return &cobra.Command{
		Use:   "status",
		Short: "Check authentication status",
		Long:  "Check if you are authenticated with the SpecSrv backend.",
		RunE: func(cmd *cobra.Command, args []string) error {
			cfg, err := config.Load()
			if err != nil {
				return fmt.Errorf("failed to load config: %w", err)
			}

			if cfg.Auth.Token == "" {
				fmt.Println("✗ Not authenticated")
				fmt.Println("\nRun 'specsrv auth login' to authenticate")
				return nil
			}

			fmt.Println("✓ Authenticated")
			fmt.Printf("\nAuthentication method: %s\n", cfg.Auth.Method)
			fmt.Printf("Server URL: %s\n", cfg.Server.URL)

			// Test the authentication
			return testAuthentication(cfg)
		},
	}
}

// testAuthentication tests if the current authentication is valid
func testAuthentication(cfg *config.Config) error {
	fmt.Println("\nTesting authentication...")

	apiClient := client.NewClient(cfg)

	// Try to call a protected endpoint to validate the token
	_, err := apiClient.Me()
	if err != nil {
		return fmt.Errorf("authentication test failed: %w", err)
	}

	fmt.Println("✓ Authentication is valid")
	return nil
}
