package commands

import (
	"os"
	"testing"

	"github.com/ericfisherdev/specsrv/cli/internal/config"
	"github.com/stretchr/testify/assert"
)

func TestIsFirstRun(t *testing.T) {
	// Create temporary home directory
	tmpDir := t.TempDir()
	os.Setenv("HOME", tmpDir)
	defer os.Unsetenv("HOME")

	// Should be first run when no config exists
	assert.True(t, IsFirstRun())

	// Create config directory and file
	cfg := config.InitConfig()
	err := config.Save(cfg)
	assert.NoError(t, err)

	// Should not be first run when config exists
	assert.False(t, IsFirstRun())
}

func TestSetupCommand(t *testing.T) {
	cmd := NewSetupCommand()

	assert.Equal(t, "setup", cmd.Use)
	assert.Contains(t, cmd.Short, "interactive setup")
	assert.NotNil(t, cmd.RunE)
}
