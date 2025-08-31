package commands

import (
	"fmt"

	"github.com/spf13/cobra"
)

var (
	version   = "dev"
	buildDate = "unknown"
	gitCommit = "unknown"
)

// NewVersionCommand creates a new version command
func NewVersionCommand() *cobra.Command {
	return &cobra.Command{
		Use:   "version",
		Short: "Print the version information",
		Long:  "Print the version information for the SpecSrv CLI tool.",
		Run: func(cmd *cobra.Command, args []string) {
			fmt.Printf("SpecSrv CLI version %s\n", version)
			fmt.Printf("Built: %s\n", buildDate)
			fmt.Printf("Commit: %s\n", gitCommit)
		},
	}
}
