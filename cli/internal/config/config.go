package config

import (
	"fmt"
	"os"
	"path/filepath"

	"github.com/spf13/viper"
)

// Config represents the CLI configuration
type Config struct {
	Server  ServerConfig `mapstructure:"server"`
	Output  OutputConfig `mapstructure:"output"`
	Auth    AuthConfig   `mapstructure:"auth"`
	Verbose bool         `mapstructure:"verbose"`
}

// ServerConfig represents server connection configuration
type ServerConfig struct {
	URL     string            `mapstructure:"url"`
	Timeout int               `mapstructure:"timeout"`
	Headers map[string]string `mapstructure:"headers"`
}

// OutputConfig represents output formatting configuration
type OutputConfig struct {
	Format string `mapstructure:"format"`
	Color  bool   `mapstructure:"color"`
}

// AuthConfig represents authentication configuration
type AuthConfig struct {
	Token  string `mapstructure:"token"`
	Method string `mapstructure:"method"`
}

// Profile represents a configuration profile
type Profile struct {
	Name   string `mapstructure:"name"`
	Config Config `mapstructure:"config"`
}

// Profiles represents multiple configuration profiles
type Profiles struct {
	Active   string    `mapstructure:"active"`
	Profiles []Profile `mapstructure:"profiles"`
}

// Load loads the configuration from file and environment
func Load() (*Config, error) {
	var cfg Config

	if err := viper.Unmarshal(&cfg); err != nil {
		return nil, fmt.Errorf("failed to unmarshal config: %w", err)
	}

	return &cfg, nil
}

// Save saves the configuration to file
func Save(cfg *Config) error {
	configDir, err := GetConfigDir()
	if err != nil {
		return fmt.Errorf("failed to get config directory: %w", err)
	}

	configPath := filepath.Join(configDir, "config.yaml")

	viper.Set("server", cfg.Server)
	viper.Set("output", cfg.Output)
	viper.Set("auth", cfg.Auth)
	viper.Set("verbose", cfg.Verbose)

	if err := viper.WriteConfigAs(configPath); err != nil {
		return fmt.Errorf("failed to write config file: %w", err)
	}

	// Set secure file permissions to protect tokens
	if err := os.Chmod(configPath, 0600); err != nil {
		return fmt.Errorf("failed to set secure permissions on config file: %w", err)
	}

	return nil
}

// GetConfigDir returns the configuration directory path
func GetConfigDir() (string, error) {
	home, err := os.UserHomeDir()
	if err != nil {
		return "", err
	}

	configDir := filepath.Join(home, ".specsrv")

	if err := os.MkdirAll(configDir, 0700); err != nil {
		return "", err
	}

	return configDir, nil
}

// InitConfig initializes configuration with defaults
func InitConfig() *Config {
	return &Config{
		Server: ServerConfig{
			URL:     "http://localhost:8000",
			Timeout: 30,
			Headers: make(map[string]string),
		},
		Output: OutputConfig{
			Format: "table",
			Color:  true,
		},
		Auth: AuthConfig{
			Method: "token",
		},
		Verbose: false,
	}
}

// LoadProfiles loads all configuration profiles
func LoadProfiles() (*Profiles, error) {
	configDir, err := GetConfigDir()
	if err != nil {
		return nil, err
	}

	profilesPath := filepath.Join(configDir, "profiles.yaml")

	if _, err := os.Stat(profilesPath); os.IsNotExist(err) {
		return &Profiles{
			Active:   "default",
			Profiles: []Profile{},
		}, nil
	}

	profileViper := viper.New()
	profileViper.SetConfigFile(profilesPath)

	if err := profileViper.ReadInConfig(); err != nil {
		return nil, fmt.Errorf("failed to read profiles config: %w", err)
	}

	var profiles Profiles
	if err := profileViper.Unmarshal(&profiles); err != nil {
		return nil, fmt.Errorf("failed to unmarshal profiles: %w", err)
	}

	return &profiles, nil
}

// SaveProfiles saves configuration profiles
func SaveProfiles(profiles *Profiles) error {
	configDir, err := GetConfigDir()
	if err != nil {
		return err
	}

	profilesPath := filepath.Join(configDir, "profiles.yaml")

	profileViper := viper.New()
	profileViper.Set("active", profiles.Active)
	profileViper.Set("profiles", profiles.Profiles)

	if err := profileViper.WriteConfigAs(profilesPath); err != nil {
		return fmt.Errorf("failed to write profiles config: %w", err)
	}

	// Set restrictive permissions (owner read/write only)
	if err := os.Chmod(profilesPath, 0o600); err != nil {
		return fmt.Errorf("failed to set secure permissions on profiles config: %w", err)
	}

	return nil
}
