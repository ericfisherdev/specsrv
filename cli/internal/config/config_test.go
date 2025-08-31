package config

import (
	"os"
	"path/filepath"
	"testing"

	"github.com/spf13/viper"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestInitConfig(t *testing.T) {
	cfg := InitConfig()

	assert.Equal(t, "http://localhost:8000", cfg.Server.URL)
	assert.Equal(t, 30, cfg.Server.Timeout)
	assert.Equal(t, "table", cfg.Output.Format)
	assert.True(t, cfg.Output.Color)
	assert.False(t, cfg.Verbose)
	assert.NotNil(t, cfg.Server.Headers)
}

func TestSaveAndLoad(t *testing.T) {
	// Create temporary config directory
	tmpDir := t.TempDir()
	os.Setenv("HOME", tmpDir)
	defer os.Unsetenv("HOME")

	// Create test config
	testConfig := &Config{
		Server: ServerConfig{
			URL:     "http://test-server:8080",
			Timeout: 60,
			Headers: map[string]string{"Test": "Header"},
		},
		Output: OutputConfig{
			Format: "json",
			Color:  false,
		},
		Auth: AuthConfig{
			Token:  "test-token",
			Method: "token",
		},
		Verbose: true,
	}

	// Save config
	err := Save(testConfig)
	require.NoError(t, err)

	// Verify config file exists
	configDir, err := GetConfigDir()
	require.NoError(t, err)
	configPath := filepath.Join(configDir, "config.yaml")
	assert.FileExists(t, configPath)

	// Reset viper state
	viper.Reset()

	// Load config
	loadedConfig, err := Load()
	require.NoError(t, err)

	assert.Equal(t, testConfig.Server.URL, loadedConfig.Server.URL)
	assert.Equal(t, testConfig.Server.Timeout, loadedConfig.Server.Timeout)
	assert.Equal(t, testConfig.Output.Format, loadedConfig.Output.Format)
	assert.Equal(t, testConfig.Output.Color, loadedConfig.Output.Color)
	assert.Equal(t, testConfig.Auth.Token, loadedConfig.Auth.Token)
	assert.Equal(t, testConfig.Auth.Method, loadedConfig.Auth.Method)
	assert.Equal(t, testConfig.Verbose, loadedConfig.Verbose)
}

func TestGetConfigDir(t *testing.T) {
	tmpDir := t.TempDir()
	os.Setenv("HOME", tmpDir)
	defer os.Unsetenv("HOME")

	configDir, err := GetConfigDir()
	require.NoError(t, err)

	expectedPath := filepath.Join(tmpDir, ".specsrv")
	assert.Equal(t, expectedPath, configDir)
	assert.DirExists(t, configDir)
}

func TestLoadProfiles(t *testing.T) {
	tmpDir := t.TempDir()
	os.Setenv("HOME", tmpDir)
	defer os.Unsetenv("HOME")

	// First load should create default profiles
	profiles, err := LoadProfiles()
	require.NoError(t, err)
	assert.Empty(t, profiles.Active)
	assert.Empty(t, profiles.Profiles)

	// Add a profile and save
	testProfile := Profile{
		Name: "test",
		Config: Config{
			Server: ServerConfig{URL: "http://test:8000"},
		},
	}
	profiles.Profiles = append(profiles.Profiles, testProfile)
	profiles.Active = "test"

	err = SaveProfiles(profiles)
	require.NoError(t, err)

	// Load again and verify
	loadedProfiles, err := LoadProfiles()
	require.NoError(t, err)
	assert.Equal(t, "test", loadedProfiles.Active)
	assert.Len(t, loadedProfiles.Profiles, 1)
	assert.Equal(t, "test", loadedProfiles.Profiles[0].Name)
	assert.Equal(t, "http://test:8000", loadedProfiles.Profiles[0].Config.Server.URL)
}
