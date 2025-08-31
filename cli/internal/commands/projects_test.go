package commands

import (
	"bytes"
	"testing"

	"github.com/spf13/viper"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestProjectsListCommand(t *testing.T) {
	// Setup viper for testing
	viper.Reset()
	viper.Set("server.url", "http://localhost:8000")
	viper.Set("output.format", "table")

	cmd := NewProjectsCommand()

	// Get the list subcommand
	listCmd, _, err := cmd.Find([]string{"list"})
	require.NoError(t, err)

	// Capture output
	buf := new(bytes.Buffer)
	listCmd.SetOut(buf)
	listCmd.SetErr(buf)

	// Execute command
	err = listCmd.Execute()
	assert.NoError(t, err)

	output := buf.String()
	// Should contain table headers or mock project data
	assert.Contains(t, output, "ID")
	assert.Contains(t, output, "NAME")
	assert.Contains(t, output, "STATUS")
}

func TestProjectsShowCommand(t *testing.T) {
	viper.Reset()
	viper.Set("server.url", "http://localhost:8000")
	viper.Set("output.format", "table")

	cmd := NewProjectsCommand()

	// Set args for show command
	cmd.SetArgs([]string{"show", "1"})

	buf := new(bytes.Buffer)
	cmd.SetOut(buf)
	cmd.SetErr(buf)

	err := cmd.Execute()
	assert.NoError(t, err)

	output := buf.String()
	// Should show project details
	assert.Contains(t, output, "Project Details")
}
