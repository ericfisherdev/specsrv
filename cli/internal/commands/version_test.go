package commands

import (
	"bytes"
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestVersionCommand(t *testing.T) {
	// Set test values
	version = "1.0.0"
	buildDate = "2023-01-01"
	gitCommit = "abc123"

	cmd := NewVersionCommand()

	// Capture output
	buf := new(bytes.Buffer)
	cmd.SetOut(buf)
	cmd.SetErr(buf)

	err := cmd.Execute()
	assert.NoError(t, err)

	output := buf.String()
	assert.Contains(t, output, "SpecSrv CLI version 1.0.0")
	assert.Contains(t, output, "Built: 2023-01-01")
	assert.Contains(t, output, "Commit: abc123")
}
