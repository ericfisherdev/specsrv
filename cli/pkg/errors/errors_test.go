package errors

import (
	"errors"
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestCLIError(t *testing.T) {
	originalErr := errors.New("original error")
	cliErr := &CLIError{
		Type:    ErrorTypeValidation,
		Message: "validation failed",
		Cause:   originalErr,
		Code:    400,
	}

	assert.Equal(t, "validation failed", cliErr.Error())
	assert.Equal(t, originalErr, cliErr.Unwrap())
}

func TestNewValidationError(t *testing.T) {
	originalErr := errors.New("field required")
	err := NewValidationError("invalid input", originalErr)

	assert.Equal(t, ErrorTypeValidation, err.Type)
	assert.Equal(t, "invalid input", err.Message)
	assert.Equal(t, originalErr, err.Cause)
	assert.Equal(t, 400, err.Code)
}

func TestNewNotFoundError(t *testing.T) {
	err := NewNotFoundError("project", 123)

	assert.Equal(t, ErrorTypeNotFound, err.Type)
	assert.Equal(t, "project with ID '123' not found", err.Message)
	assert.Equal(t, 404, err.Code)
}

func TestNewUnauthorizedError(t *testing.T) {
	err := NewUnauthorizedError("invalid token")

	assert.Equal(t, ErrorTypeUnauthorized, err.Type)
	assert.Equal(t, "invalid token", err.Message)
	assert.Equal(t, 401, err.Code)
}

func TestValidationErrors(t *testing.T) {
	validationErrs := &ValidationErrors{}

	assert.False(t, validationErrs.HasErrors())
	assert.Equal(t, "", validationErrs.Error())

	validationErrs.Add("field1 is required")
	validationErrs.Add("field2 is invalid")

	assert.True(t, validationErrs.HasErrors())
	assert.Contains(t, validationErrs.Error(), "field1 is required")
	assert.Contains(t, validationErrs.Error(), "field2 is invalid")
}

func TestErrorTypeCheckers(t *testing.T) {
	validationErr := NewValidationError("test", nil)
	notFoundErr := NewNotFoundError("resource", 1)
	unauthorizedErr := NewUnauthorizedError("test")
	networkErr := NewNetworkError("test", nil)
	configErr := NewConfigError("test", nil)
	internalErr := NewInternalError("test", nil)

	assert.True(t, IsValidationError(validationErr))
	assert.False(t, IsValidationError(notFoundErr))

	assert.True(t, IsNotFoundError(notFoundErr))
	assert.False(t, IsNotFoundError(validationErr))

	assert.True(t, IsUnauthorizedError(unauthorizedErr))
	assert.False(t, IsUnauthorizedError(validationErr))

	assert.True(t, IsNetworkError(networkErr))
	assert.False(t, IsNetworkError(validationErr))

	assert.True(t, IsConfigError(configErr))
	assert.False(t, IsConfigError(validationErr))

	assert.True(t, IsInternalError(internalErr))
	assert.False(t, IsInternalError(validationErr))
}

func TestWrapError(t *testing.T) {
	// Test wrapping CLI error
	originalErr := NewValidationError("original", nil)
	wrappedErr := WrapError(originalErr, "wrapped message")

	cliErr, ok := wrappedErr.(*CLIError)
	assert.True(t, ok)
	assert.Equal(t, "wrapped message", cliErr.Message)
	assert.Equal(t, ErrorTypeValidation, cliErr.Type)
	assert.Equal(t, 400, cliErr.Code)

	// Test wrapping generic error
	genericErr := errors.New("generic error")
	wrappedGeneric := WrapError(genericErr, "wrapped generic")

	cliErrGeneric, ok := wrappedGeneric.(*CLIError)
	assert.True(t, ok)
	assert.Equal(t, "wrapped generic", cliErrGeneric.Message)
	assert.Equal(t, ErrorTypeInternal, cliErrGeneric.Type)
	assert.Equal(t, 500, cliErrGeneric.Code)
}
