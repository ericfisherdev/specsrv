package commands

import (
	"encoding/json"
	"fmt"
	"os"
	"strconv"
	"text/tabwriter"

	"github.com/ericfisherdev/specsrv/cli/internal/client"
	"github.com/ericfisherdev/specsrv/cli/internal/config"
	"github.com/ericfisherdev/specsrv/cli/pkg/models"
	"github.com/spf13/cobra"
	"gopkg.in/yaml.v3"
)

// ConfigLoader defines interface for loading configuration
type ConfigLoader interface {
	Load() (*config.Config, error)
}

// APIClientFactory defines interface for creating API clients
type APIClientFactory interface {
	NewClient(cfg *config.Config) APIClient
}

// APIClient defines interface for API operations
type APIClient interface {
	RecordInteraction(req models.InteractionRecordRequest) (*models.InteractionRecordResponse, error)
	GetRecommendation(req models.RecommendationRequest) (*models.LearningRecommendation, error)
	GetPatterns(params map[string]string) (*models.PaginatedPatternsResponse, error)
	GetLearningAnalytics(timeRange string) (*models.LearningAnalytics, error)
	SearchInteractions(req models.SearchRequest) (*models.SearchResponse, error)
}

// DefaultConfigLoader implements ConfigLoader using the config package
type DefaultConfigLoader struct{}

func (d *DefaultConfigLoader) Load() (*config.Config, error) {
	return config.Load()
}

// DefaultAPIClientFactory implements APIClientFactory using the client package
type DefaultAPIClientFactory struct{}

func (d *DefaultAPIClientFactory) NewClient(cfg *config.Config) APIClient {
	return client.NewClient(cfg)
}

// LearningCommandOptions allows dependency injection for testing
type LearningCommandOptions struct {
	ConfigLoader     ConfigLoader
	APIClientFactory APIClientFactory
}

// NewLearningCommand creates a new learning command with default dependencies
func NewLearningCommand() *cobra.Command {
	return NewLearningCommandWithOptions(&LearningCommandOptions{
		ConfigLoader:     &DefaultConfigLoader{},
		APIClientFactory: &DefaultAPIClientFactory{},
	})
}

// NewLearningCommandWithOptions creates a new learning command with injected dependencies
func NewLearningCommandWithOptions(opts *LearningCommandOptions) *cobra.Command {
	cmd := &cobra.Command{
		Use:     "learning",
		Aliases: []string{"learn", "ai"},
		Short:   "Interact with the AI learning system",
		Long:    "Record interactions, get recommendations, and analyze AI learning patterns.",
	}

	cmd.AddCommand(newLearningRecordCommand(opts))
	cmd.AddCommand(newLearningRecommendCommand(opts))
	cmd.AddCommand(newLearningPatternsCommand(opts))
	cmd.AddCommand(newLearningAnalyticsCommand(opts))
	cmd.AddCommand(newLearningSearchCommand(opts))

	return cmd
}

// newLearningRecordCommand creates the learning record subcommand
func newLearningRecordCommand(opts *LearningCommandOptions) *cobra.Command {
	var (
		taskID          int
		agentType       string
		inputContext    string
		executionSteps  string
		outputResult    string
		successScore    float64
		executionTimeMs int
		errorLog        string
	)

	cmd := &cobra.Command{
		Use:   "record",
		Short: "Record an AI agent interaction",
		Long:  "Record details of an AI agent interaction for learning purposes.",
		RunE: func(cmd *cobra.Command, args []string) error {
			if taskID == 0 {
				return fmt.Errorf("task ID is required")
			}
			if agentType == "" {
				return fmt.Errorf("agent type is required")
			}
			if successScore < 0 || successScore > 1 {
				return fmt.Errorf("success score must be between 0 and 1")
			}

			cfg, err := opts.ConfigLoader.Load()
			if err != nil {
				return fmt.Errorf("failed to load config: %w", err)
			}

			apiClient := opts.APIClientFactory.NewClient(cfg)

			// Parse JSON strings
			var contextMap map[string]interface{}
			if inputContext != "" {
				if err := json.Unmarshal([]byte(inputContext), &contextMap); err != nil {
					return fmt.Errorf("invalid input context JSON: %w", err)
				}
			}

			var stepsSlice []map[string]interface{}
			if executionSteps != "" {
				if err := json.Unmarshal([]byte(executionSteps), &stepsSlice); err != nil {
					return fmt.Errorf("invalid execution steps JSON: %w", err)
				}
			}

			var resultMap map[string]interface{}
			if outputResult != "" {
				if err := json.Unmarshal([]byte(outputResult), &resultMap); err != nil {
					return fmt.Errorf("invalid output result JSON: %w", err)
				}
			}

			var errorSlice []map[string]interface{}
			if errorLog != "" {
				if err := json.Unmarshal([]byte(errorLog), &errorSlice); err != nil {
					return fmt.Errorf("invalid error log JSON: %w", err)
				}
			}

			req := models.InteractionRecordRequest{
				TaskID:          taskID,
				AgentType:       agentType,
				InputContext:    contextMap,
				ExecutionSteps:  stepsSlice,
				OutputResult:    resultMap,
				SuccessScore:    successScore,
				ExecutionTimeMs: executionTimeMs,
				ErrorLog:        errorSlice,
			}

			response, err := apiClient.RecordInteraction(req)
			if err != nil {
				return fmt.Errorf("failed to record interaction: %w", err)
			}

			fmt.Printf("✓ Interaction recorded successfully (ID: %s)\n", response.InteractionID)
			if response.PatternExtracted {
				fmt.Printf("✓ Pattern extracted and stored (Hash: %s)\n", response.PatternHash)
			}

			return nil
		},
	}

	cmd.Flags().IntVar(&taskID, "task-id", 0, "ID of the associated task (required)")
	cmd.Flags().StringVar(&agentType, "agent-type", "", "type of AI agent (required)")
	cmd.Flags().StringVar(&inputContext, "input-context", "", "input context as JSON string")
	cmd.Flags().StringVar(&executionSteps, "execution-steps", "", "execution steps as JSON array")
	cmd.Flags().StringVar(&outputResult, "output-result", "", "output result as JSON string")
	cmd.Flags().Float64Var(&successScore, "success-score", 0, "success score (0-1, required)")
	cmd.Flags().IntVar(&executionTimeMs, "execution-time", 0, "execution time in milliseconds")
	cmd.Flags().StringVar(&errorLog, "error-log", "", "error log as JSON array")

	cmd.MarkFlagRequired("task-id")
	cmd.MarkFlagRequired("agent-type")
	cmd.MarkFlagRequired("success-score")

	return cmd
}

// newLearningRecommendCommand creates the learning recommend subcommand
func newLearningRecommendCommand(opts *LearningCommandOptions) *cobra.Command {
	var (
		taskContext   string
		agentType     string
		minConfidence float64
	)

	cmd := &cobra.Command{
		Use:   "recommend",
		Short: "Get AI solution recommendations",
		Long:  "Get solution recommendations based on learned patterns from previous interactions.",
		RunE: func(cmd *cobra.Command, args []string) error {
			if agentType == "" {
				return fmt.Errorf("agent type is required")
			}

			if minConfidence < 0 || minConfidence > 1 {
				return fmt.Errorf("min-confidence must be between 0 and 1")
			}

			cfg, err := opts.ConfigLoader.Load()
			if err != nil {
				return fmt.Errorf("failed to load config: %w", err)
			}

			apiClient := opts.APIClientFactory.NewClient(cfg)

			// Parse task context
			var contextMap map[string]interface{}
			if taskContext != "" {
				if err := json.Unmarshal([]byte(taskContext), &contextMap); err != nil {
					return fmt.Errorf("invalid task context JSON: %w", err)
				}
			}

			req := models.RecommendationRequest{
				TaskContext:   contextMap,
				AgentType:     agentType,
				MinConfidence: minConfidence,
			}

			recommendation, err := apiClient.GetRecommendation(req)
			if err != nil {
				return fmt.Errorf("failed to get recommendation: %w", err)
			}

			// Format output
			outputFormat := getOutputFormat()
			return formatRecommendationOutput(*recommendation, outputFormat)
		},
	}

	cmd.Flags().StringVar(&taskContext, "task-context", "", "task context as JSON string")
	cmd.Flags().StringVar(&agentType, "agent-type", "", "type of AI agent (required)")
	cmd.Flags().Float64Var(&minConfidence, "min-confidence", 0.7, "minimum confidence threshold (0-1)")

	cmd.MarkFlagRequired("agent-type")

	return cmd
}

// newLearningPatternsCommand creates the learning patterns subcommand
func newLearningPatternsCommand(opts *LearningCommandOptions) *cobra.Command {
	var (
		agentType     string
		patternType   string
		minConfidence float64
		limit         int
	)

	cmd := &cobra.Command{
		Use:     "patterns",
		Aliases: []string{"pattern", "p"},
		Short:   "List learned patterns",
		Long:    "List AI learning patterns with optional filtering.",
		RunE: func(cmd *cobra.Command, args []string) error {
			cfg, err := opts.ConfigLoader.Load()
			if err != nil {
				return fmt.Errorf("failed to load config: %w", err)
			}

			apiClient := opts.APIClientFactory.NewClient(cfg)

			// Build query parameters
			params := make(map[string]string)
			if agentType != "" {
				params["agent_type"] = agentType
			}
			if patternType != "" {
				params["pattern_type"] = patternType
			}
			if minConfidence > 0 {
				params["min_confidence"] = fmt.Sprintf("%.2f", minConfidence)
			}
			if limit > 0 {
				params["per_page"] = strconv.Itoa(limit)
			}

			patterns, err := apiClient.GetPatterns(params)
			if err != nil {
				return fmt.Errorf("failed to get patterns: %w", err)
			}

			// Format output
			outputFormat := getOutputFormat()
			return formatPatternsOutput(patterns.Items, outputFormat)
		},
	}

	cmd.Flags().StringVar(&agentType, "agent-type", "", "filter by agent type")
	cmd.Flags().StringVar(&patternType, "pattern-type", "", "filter by pattern type")
	cmd.Flags().Float64Var(&minConfidence, "min-confidence", 0, "minimum confidence threshold")
	cmd.Flags().IntVar(&limit, "limit", 20, "maximum number of patterns to return")

	return cmd
}

// newLearningAnalyticsCommand creates the learning analytics subcommand
func newLearningAnalyticsCommand(opts *LearningCommandOptions) *cobra.Command {
	var timeRange string

	cmd := &cobra.Command{
		Use:     "analytics",
		Aliases: []string{"stats", "metrics"},
		Short:   "Show learning analytics",
		Long:    "Display performance analytics and learning metrics for the AI system.",
		RunE: func(cmd *cobra.Command, args []string) error {
			cfg, err := opts.ConfigLoader.Load()
			if err != nil {
				return fmt.Errorf("failed to load config: %w", err)
			}

			apiClient := opts.APIClientFactory.NewClient(cfg)

			analytics, err := apiClient.GetLearningAnalytics(timeRange)
			if err != nil {
				return fmt.Errorf("failed to get analytics: %w", err)
			}

			// Format output
			outputFormat := getOutputFormat()
			return formatAnalyticsOutput(*analytics, outputFormat)
		},
	}

	cmd.Flags().StringVar(&timeRange, "range", "30d", "time range (7d, 30d, 90d)")

	return cmd
}

// newLearningSearchCommand creates the learning search subcommand
func newLearningSearchCommand(opts *LearningCommandOptions) *cobra.Command {
	var (
		agentType       string
		context         string
		minSuccessScore float64
		limit           int
	)

	cmd := &cobra.Command{
		Use:   "search",
		Short: "Search for similar interactions",
		Long:  "Search for similar AI interactions based on context and criteria.",
		RunE: func(cmd *cobra.Command, args []string) error {
			cfg, err := opts.ConfigLoader.Load()
			if err != nil {
				return fmt.Errorf("failed to load config: %w", err)
			}

			apiClient := opts.APIClientFactory.NewClient(cfg)

			// Parse context
			var contextMap map[string]interface{}
			if context != "" {
				if err := json.Unmarshal([]byte(context), &contextMap); err != nil {
					return fmt.Errorf("invalid context JSON: %w", err)
				}
			}

			req := models.SearchRequest{
				AgentType:       agentType,
				Context:         contextMap,
				MinSuccessScore: minSuccessScore,
				Limit:           limit,
			}

			results, err := apiClient.SearchInteractions(req)
			if err != nil {
				return fmt.Errorf("failed to search interactions: %w", err)
			}

			fmt.Printf("Found %d patterns (showing %d)\n", results.TotalFound, results.Returned)

			// Format output
			outputFormat := getOutputFormat()
			return formatPatternsOutput(results.Patterns, outputFormat)
		},
	}

	cmd.Flags().StringVar(&agentType, "agent-type", "", "filter by agent type")
	cmd.Flags().StringVar(&context, "context", "", "search context as JSON string")
	cmd.Flags().Float64Var(&minSuccessScore, "min-success", 0.7, "minimum success score")
	cmd.Flags().IntVar(&limit, "limit", 10, "maximum number of results")

	return cmd
}

// Formatting functions
func formatRecommendationOutput(recommendation models.LearningRecommendation, format string) error {
	switch format {
	case "json":
		return json.NewEncoder(os.Stdout).Encode(recommendation)
	case "yaml":
		return yaml.NewEncoder(os.Stdout).Encode(recommendation)
	default: // table
		return formatRecommendationTable(recommendation)
	}
}

func formatPatternsOutput(patterns []models.KnowledgePattern, format string) error {
	switch format {
	case "json":
		return json.NewEncoder(os.Stdout).Encode(patterns)
	case "yaml":
		return yaml.NewEncoder(os.Stdout).Encode(patterns)
	default: // table
		return formatPatternsTable(patterns)
	}
}

func formatAnalyticsOutput(analytics models.LearningAnalytics, format string) error {
	switch format {
	case "json":
		return json.NewEncoder(os.Stdout).Encode(analytics)
	case "yaml":
		return yaml.NewEncoder(os.Stdout).Encode(analytics)
	default: // table
		return formatAnalyticsTable(analytics)
	}
}

func formatRecommendationTable(rec models.LearningRecommendation) error {
	w := tabwriter.NewWriter(os.Stdout, 0, 0, 2, ' ', 0)

	fmt.Fprintln(w, "RECOMMENDATION DETAILS")
	fmt.Fprintln(w, "═══════════════════")
	fmt.Fprintf(w, "Pattern Name:\t%s\n", rec.Pattern.Name)
	fmt.Fprintf(w, "Pattern Type:\t%s\n", rec.Pattern.Type)
	fmt.Fprintf(w, "Confidence:\t%.2f\n", rec.Confidence)
	fmt.Fprintf(w, "Usage Count:\t%d\n", rec.Pattern.UsageCount)
	fmt.Fprintf(w, "Success Rate:\t%.1f%%\n", rec.EstimatedSuccessRate*100)

	if rec.Pattern.LastSuccessfulUse != nil {
		fmt.Fprintf(w, "Last Used:\t%s\n", rec.Pattern.LastSuccessfulUse.Format("2006-01-02"))
	}

	fmt.Fprintln(w, "\nRECOMMENDED APPROACH")
	fmt.Fprintln(w, "══════════════════")
	if approach, ok := rec.AdaptedSolution["approach"].(string); ok {
		fmt.Fprintf(w, "Approach:\t%s\n", approach)
	}
	if timeEst, ok := rec.AdaptedSolution["time_estimate"].(string); ok {
		fmt.Fprintf(w, "Time Estimate:\t%s\n", timeEst)
	}

	return w.Flush()
}

func formatPatternsTable(patterns []models.KnowledgePattern) error {
	w := tabwriter.NewWriter(os.Stdout, 0, 0, 2, ' ', 0)
	fmt.Fprintln(w, "ID\tNAME\tTYPE\tCONFIDENCE\tUSAGE\tLAST USED")

	for _, p := range patterns {
		lastUsed := "Never"
		if p.LastSuccessfulUse != nil {
			lastUsed = p.LastSuccessfulUse.Format("2006-01-02")
		}

		fmt.Fprintf(w, "%d\t%s\t%s\t%.2f\t%d\t%s\n",
			p.ID, p.Name, p.Type, p.ConfidenceScore, p.UsageCount, lastUsed)
	}

	return w.Flush()
}

func formatAnalyticsTable(analytics models.LearningAnalytics) error {
	w := tabwriter.NewWriter(os.Stdout, 0, 0, 2, ' ', 0)

	fmt.Fprintln(w, "LEARNING SYSTEM ANALYTICS")
	fmt.Fprintln(w, "═══════════════════════")

	if len(analytics.InteractionMetrics) > 0 {
		fmt.Fprintln(w, "\nINTERACTION METRICS BY AGENT TYPE")
		fmt.Fprintln(w, "AGENT TYPE\tTOTAL\tAVG SUCCESS\tAVG TIME(ms)\tSUCCESSFUL")

		for _, metric := range analytics.InteractionMetrics {
			fmt.Fprintf(w, "%s\t%d\t%.2f\t%.0f\t%d\n",
				metric.AgentType, metric.TotalInteractions, metric.AvgSuccessScore,
				metric.AvgExecutionTime, metric.SuccessfulInteractions)
		}
	}

	if len(analytics.PatternAnalytics) > 0 {
		fmt.Fprintln(w, "\nPATTERN ANALYTICS BY TYPE")
		fmt.Fprintln(w, "PATTERN TYPE\tTOTAL\tAVG CONFIDENCE\tTOTAL USAGE\tLAST USED")

		for _, pattern := range analytics.PatternAnalytics {
			lastUsed := "Never"
			if pattern.LastUsed != nil {
				lastUsed = pattern.LastUsed.Format("2006-01-02")
			}

			fmt.Fprintf(w, "%s\t%d\t%.2f\t%d\t%s\n",
				pattern.PatternType, pattern.TotalPatterns, pattern.AvgConfidence,
				pattern.TotalUsage, lastUsed)
		}
	}

	if analytics.LearningEffectiveness != nil {
		fmt.Fprintln(w, "\nLEARNING EFFECTIVENESS")
		fmt.Fprintf(w, "Total Interactions:\t%d\n", analytics.LearningEffectiveness.TotalInteractions)
		fmt.Fprintf(w, "Patterns Learned:\t%d\n", analytics.LearningEffectiveness.PatternsLearned)
		fmt.Fprintf(w, "Pattern Reuses:\t%d\n", analytics.LearningEffectiveness.PatternReuses)
		fmt.Fprintf(w, "Learning Rate:\t%.2f%%\n", analytics.LearningEffectiveness.LearningRate*100)
		fmt.Fprintf(w, "Reuse Rate:\t%.2f%%\n", analytics.LearningEffectiveness.ReuseRate*100)
	}

	return w.Flush()
}
