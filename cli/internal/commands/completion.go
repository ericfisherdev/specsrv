package commands

import (
	"os"

	"github.com/spf13/cobra"
)

// NewCompletionCommand creates a new completion command
func NewCompletionCommand() *cobra.Command {
	cmd := &cobra.Command{
		Use:   "completion [bash|zsh|fish|powershell]",
		Short: "Generate completion script",
		Long: `To load completions:

Bash:

  $ source <(specsrv completion bash)

  # To load completions for each session, execute once:
  # Linux:
  $ specsrv completion bash > /etc/bash_completion.d/specsrv
  # macOS:
  $ specsrv completion bash > /usr/local/etc/bash_completion.d/specsrv

Zsh:

  # If shell completion is not already enabled in your environment,
  # you will need to enable it.  You can execute the following once:

  $ echo "autoload -U compinit; compinit" >> ~/.zshrc

  # To load completions for each session, execute once:
  $ specsrv completion zsh > "${fpath[1]}/_specsrv"

  # You will need to start a new shell for this setup to take effect.

fish:

  $ specsrv completion fish | source

  # To load completions for each session, execute once:
  $ specsrv completion fish > ~/.config/fish/completions/specsrv.fish

PowerShell:

  PS> specsrv completion powershell | Out-String | Invoke-Expression

  # To load completions for every new session, run:
  PS> specsrv completion powershell > specsrv.ps1
  # and source this file from your PowerShell profile.
`,
		DisableFlagsInUseLine: true,
		ValidArgs:             []string{"bash", "zsh", "fish", "powershell"},
		Args:                  cobra.MatchAll(cobra.ExactArgs(1), cobra.OnlyValidArgs),
		Run: func(cmd *cobra.Command, args []string) {
			switch args[0] {
			case "bash":
				cmd.Root().GenBashCompletion(os.Stdout)
			case "zsh":
				cmd.Root().GenZshCompletion(os.Stdout)
			case "fish":
				cmd.Root().GenFishCompletion(os.Stdout, true)
			case "powershell":
				cmd.Root().GenPowerShellCompletionWithDesc(os.Stdout)
			}
		},
	}

	return cmd
}
