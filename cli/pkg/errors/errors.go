package errors

import (
	stderrors "errors"
	"fmt"
	"strings"
)

// ErrorType represents different types of errors
type ErrorType string

const (
	// ErrorTypeValidation represents validation errors
	ErrorTypeValidation ErrorType = "validation"
	// ErrorTypeNotFound represents not found errors
	ErrorTypeNotFound ErrorType = "not_found"
	// ErrorTypeUnauthorized represents unauthorized errors
	ErrorTypeUnauthorized ErrorType = "unauthorized"
	// ErrorTypeNetwork represents network errors
	ErrorTypeNetwork ErrorType = "network"
	// ErrorTypeConfig represents configuration errors
	ErrorTypeConfig ErrorType = "config"
	// ErrorTypeInternal represents internal errors
	ErrorTypeInternal ErrorType = "internal"
)

// CLIError represents a CLI-specific error
type CLIError struct {
	Type    ErrorType
	Message string
	Cause   error
	Code    int
}

// Error returns the error message
func (e *CLIError) Error() string {
	if e.Cause != nil {
		return fmt.Sprintf("%s: %v", e.Message, e.Cause)
	}
	return e.Message
}

// Unwrap returns the underlying cause
func (e *CLIError) Unwrap() error {
	return e.Cause
}

// NewValidationError creates a new validation error
func NewValidationError(message string, cause error) *CLIError {
	return &CLIError{
		Type:    ErrorTypeValidation,
		Message: message,
		Cause:   cause,
		Code:    400,
	}
}

// NewNotFoundError creates a new not found error
func NewNotFoundError(resource string, id interface{}) *CLIError {
	message := fmt.Sprintf("%s not found", resource)
	if id != nil {
		message = fmt.Sprintf("%s with ID %v not found", resource, id)
	}
	return &CLIError{
		Type:    ErrorTypeNotFound,
		Message: message,
		Code:    404,
	}
}

// NewUnauthorizedError creates a new unauthorized error
func NewUnauthorizedError(message string) *CLIError {
	if message == "" {
		message = "unauthorized access"
	}
	return &CLIError{
		Type:    ErrorTypeUnauthorized,
		Message: message,
		Code:    401,
	}
}

// NewNetworkError creates a new network error
func NewNetworkError(message string, cause error) *CLIError {
	return &CLIError{
		Type:    ErrorTypeNetwork,
		Message: message,
		Cause:   cause,
		Code:    500,
	}
}

// NewConfigError creates a new configuration error
func NewConfigError(message string, cause error) *CLIError {
	return &CLIError{
		Type:    ErrorTypeConfig,
		Message: message,
		Cause:   cause,
		Code:    500,
	}
}

// NewInternalError creates a new internal error
func NewInternalError(message string, cause error) *CLIError {
	return &CLIError{
		Type:    ErrorTypeInternal,
		Message: message,
		Cause:   cause,
		Code:    500,
	}
}

// ValidationErrors represents multiple validation errors
type ValidationErrors struct {
	Errors []string
}

// Error returns the error message
func (e *ValidationErrors) Error() string {
	if len(e.Errors) == 0 {
		return "validation failed"
	}
	if len(e.Errors) == 1 {
		return e.Errors[0]
	}
	return fmt.Sprintf("validation failed: %s", strings.Join(e.Errors, ", "))
}

// Add adds a validation error
func (e *ValidationErrors) Add(err string) {
	e.Errors = append(e.Errors, err)
}

// HasErrors returns true if there are validation errors
func (e *ValidationErrors) HasErrors() bool {
	return len(e.Errors) > 0
}

// IsValidationError checks if an error is a validation error
func IsValidationError(err error) bool {
	var cliErr *CLIError
	if stderrors.As(err, &cliErr) {
		return cliErr.Type == ErrorTypeValidation
	}
	var validationErr *ValidationErrors
	return stderrors.As(err, &validationErr)
}

// IsNotFoundError checks if an error is a not found error
func IsNotFoundError(err error) bool {
	var cliErr *CLIError
	return stderrors.As(err, &cliErr) && cliErr.Type == ErrorTypeNotFound
}

// IsUnauthorizedError checks if an error is an unauthorized error
func IsUnauthorizedError(err error) bool {
	var cliErr *CLIError
	return stderrors.As(err, &cliErr) && cliErr.Type == ErrorTypeUnauthorized
}

// IsNetworkError checks if an error is a network error
func IsNetworkError(err error) bool {
	var cliErr *CLIError
	return stderrors.As(err, &cliErr) && cliErr.Type == ErrorTypeNetwork
}

// IsConfigError checks if an error is a configuration error
func IsConfigError(err error) bool {
	var cliErr *CLIError
	return stderrors.As(err, &cliErr) && cliErr.Type == ErrorTypeConfig
}

// IsInternalError checks if an error is an internal error
func IsInternalError(err error) bool {
	var cliErr *CLIError
	return stderrors.As(err, &cliErr) && cliErr.Type == ErrorTypeInternal
}

// WrapError wraps an existing error with additional context
func WrapError(err error, message string) error {
	if err == nil {
		return nil
	}

	if cliErr, ok := err.(*CLIError); ok {
		return &CLIError{
			Type:    cliErr.Type,
			Message: message,
			Cause:   cliErr,
			Code:    cliErr.Code,
		}
	}

	return &CLIError{
		Type:    ErrorTypeInternal,
		Message: message,
		Cause:   err,
		Code:    500,
	}
}
