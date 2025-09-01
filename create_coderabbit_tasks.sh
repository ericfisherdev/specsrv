#!/bin/bash

# Script to create 70 CodeRabbit tasks (50 nitpick + 20 AI prompts)
# Based on common code review patterns found in GitHub pull requests

CLI="cli/build/specsrv"
PROJECT_ID=1

echo "Creating 50 CodeRabbit nitpick tasks..."

# Nitpick Tasks (1-50)
$CLI tasks create --project $PROJECT_ID --title "Add type hints to function parameters in ApiController" --description "Consider adding type hints to improve code readability and catch type-related bugs early." --priority "low" --tags "nitpick,type-hints,php" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Extract magic numbers into named constants" --description "The numbers 404, 500, and 200 should be extracted into named constants for better maintainability." --priority "medium" --tags "nitpick,constants,magic-numbers" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Simplify conditional expression in task validation" --description "The if-else chain can be simplified using early returns to improve readability." --priority "low" --tags "nitpick,conditionals,readability" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add PHPDoc comments for public methods" --description "Public methods should have comprehensive PHPDoc comments describing parameters, return values, and exceptions." --priority "low" --tags "nitpick,documentation,phpdoc" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Consider using strict comparison operators" --description "Replace loose comparison (==) with strict comparison (===) to avoid type coercion issues." --priority "medium" --tags "nitpick,comparison,type-safety" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Remove unused import statements" --description "Several import statements are not being used in this file and should be removed to keep the code clean." --priority "low" --tags "nitpick,imports,cleanup" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Rename variable to be more descriptive" --description "Variable name 'data' is too generic. Consider using more descriptive names like 'userData' or 'taskData'." --priority "low" --tags "nitpick,naming,variables" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add null checks before method calls" --description "Consider adding null checks before calling methods on potentially null objects to prevent null pointer exceptions." --priority "high" --tags "nitpick,null-safety,defensive-programming" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Use dependency injection instead of direct instantiation" --description "Instead of creating new instances directly, consider using dependency injection for better testability." --priority "medium" --tags "nitpick,dependency-injection,testing" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Extract complex boolean logic into a method" --description "The complex boolean expression should be extracted into a well-named method for better readability." --priority "low" --tags "nitpick,refactoring,methods" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add input validation for email format" --description "Email validation should check for proper format using a regex or validation library." --priority "high" --tags "nitpick,validation,email" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Consider using array destructuring" --description "Array destructuring could make the code more readable when accessing array elements." --priority "low" --tags "nitpick,destructuring,syntax" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add default case to switch statement" --description "Switch statements should always have a default case to handle unexpected values." --priority "medium" --tags "nitpick,switch,error-handling" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Use consistent indentation" --description "Some lines use tabs while others use spaces. Consistent indentation improves code readability." --priority "low" --tags "nitpick,formatting,indentation" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Consider caching expensive database queries" --description "This database query is called multiple times and could benefit from caching to improve performance." --priority "medium" --tags "nitpick,performance,caching" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add try-catch block for exception handling" --description "Database operations should be wrapped in try-catch blocks to handle potential exceptions gracefully." --priority "high" --tags "nitpick,exception-handling,database" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Use more specific HTTP status codes" --description "Instead of generic 400, use more specific status codes like 422 for validation errors." --priority "medium" --tags "nitpick,http-status,api" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Consider using builder pattern for complex objects" --description "Object creation with many parameters could benefit from the builder pattern for better readability." --priority "low" --tags "nitpick,design-patterns,builder" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add rate limiting to API endpoints" --description "Consider adding rate limiting to prevent abuse of public API endpoints." --priority "high" --tags "nitpick,security,rate-limiting" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Use environment variables for configuration" --description "Hardcoded configuration values should be moved to environment variables for better deployment flexibility." --priority "medium" --tags "nitpick,configuration,environment" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add logging for important operations" --description "Critical operations should be logged for debugging and monitoring purposes." --priority "medium" --tags "nitpick,logging,monitoring" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Consider using readonly properties" --description "Properties that are only set in constructor could be marked as readonly for immutability." --priority "low" --tags "nitpick,immutability,properties" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add unit tests for new functionality" --description "New methods should have corresponding unit tests to ensure correctness and prevent regressions." --priority "high" --tags "nitpick,testing,unit-tests" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Extract string literals into constants" --description "String literals used multiple times should be extracted into named constants." --priority "low" --tags "nitpick,constants,strings" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Consider using early return pattern" --description "Multiple nested if statements could be simplified using early returns for better readability." --priority "low" --tags "nitpick,early-return,readability" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add data sanitization for user inputs" --description "User inputs should be sanitized to prevent XSS and injection attacks." --priority "high" --tags "nitpick,security,sanitization" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Use more descriptive variable names" --description "Single-letter variable names like 'i', 'j' should be more descriptive in complex loops." --priority "low" --tags "nitpick,naming,loops" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Consider using array_filter instead of foreach" --description "Array filtering could be more elegantly handled using array_filter function." --priority "low" --tags "nitpick,array-functions,refactoring" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add CSRF protection to forms" --description "Forms should include CSRF tokens to prevent cross-site request forgery attacks." --priority "high" --tags "nitpick,security,csrf" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Consider using interface segregation" --description "Large interface could be split into smaller, more focused interfaces following ISP." --priority "medium" --tags "nitpick,solid-principles,interfaces" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add database transaction for related operations" --description "Related database operations should be wrapped in a transaction for data consistency." --priority "high" --tags "nitpick,database,transactions" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Use consistent naming convention for methods" --description "Method names should follow camelCase convention consistently throughout the codebase." --priority "low" --tags "nitpick,naming,conventions" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Consider using value objects for complex data" --description "Complex data structures could benefit from value objects for better encapsulation." --priority "medium" --tags "nitpick,value-objects,encapsulation" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add API response caching" --description "Frequently requested API responses should be cached to improve performance." --priority "medium" --tags "nitpick,performance,api-caching" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Use proper HTTP verbs for REST endpoints" --description "REST endpoints should use appropriate HTTP verbs (GET, POST, PUT, DELETE) based on operation type." --priority "medium" --tags "nitpick,rest,http-verbs" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add validation for required fields" --description "Required fields should have proper validation to ensure data integrity." --priority "high" --tags "nitpick,validation,required-fields" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Consider using factory pattern for object creation" --description "Complex object creation logic could benefit from factory pattern for better maintainability." --priority "low" --tags "nitpick,design-patterns,factory" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add proper error messages for API responses" --description "API error responses should include descriptive error messages to help client developers." --priority "medium" --tags "nitpick,api,error-messages" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Use consistent quote style" --description "Mix of single and double quotes should be standardized for consistency." --priority "low" --tags "nitpick,formatting,quotes" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Consider using array spread operator" --description "Array merging could be simplified using the spread operator for better readability." --priority "low" --tags "nitpick,arrays,spread-operator" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add proper HTTP response codes for all endpoints" --description "Each endpoint should return appropriate HTTP status codes based on operation result." --priority "medium" --tags "nitpick,http-status,endpoints" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Consider using enum for status values" --description "String status values could be replaced with enums for better type safety." --priority "medium" --tags "nitpick,enums,type-safety" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add database indexes for performance" --description "Frequently queried columns should have database indexes to improve query performance." --priority "medium" --tags "nitpick,database,performance,indexes" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Use proper SQL parameter binding" --description "SQL queries should use parameter binding instead of string concatenation to prevent SQL injection." --priority "high" --tags "nitpick,security,sql-injection" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add proper file upload validation" --description "File uploads should validate file type, size, and content to prevent security issues." --priority "high" --tags "nitpick,security,file-upload" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Consider using pagination for large datasets" --description "Large data queries should implement pagination to improve performance and user experience." --priority "medium" --tags "nitpick,pagination,performance" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add proper session management" --description "User sessions should be properly managed with secure settings and cleanup." --priority "high" --tags "nitpick,security,sessions" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Use consistent error handling patterns" --description "Error handling should follow consistent patterns throughout the application." --priority "medium" --tags "nitpick,error-handling,consistency" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Consider using abstract base class" --description "Common functionality could be moved to an abstract base class to reduce code duplication." --priority "low" --tags "nitpick,inheritance,abstraction" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add proper API versioning" --description "API endpoints should include versioning to support backward compatibility." --priority "medium" --tags "nitpick,api,versioning" --skip-setup

echo "Creating 20 AI-suggested improvement tasks..."

# AI-Suggested Tasks (51-70)
$CLI tasks create --project $PROJECT_ID --title "Implement automated code quality checks" --description "Set up automated tools like PHPStan, Psalm, or similar to catch potential issues early in development." --priority "medium" --tags "ai-suggestion,code-quality,automation" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add comprehensive integration tests" --description "Create integration tests that cover end-to-end user workflows to ensure system components work together properly." --priority "high" --tags "ai-suggestion,testing,integration" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Implement API response caching strategy" --description "Design and implement a comprehensive caching strategy for API responses to improve performance and reduce database load." --priority "medium" --tags "ai-suggestion,performance,caching" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add comprehensive error logging system" --description "Implement structured logging with proper error levels, context, and monitoring to improve debugging and system visibility." --priority "high" --tags "ai-suggestion,logging,monitoring" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Implement user permission system" --description "Create a flexible role-based permission system to control user access to different features and data." --priority "high" --tags "ai-suggestion,security,permissions" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add API documentation generation" --description "Implement automated API documentation generation using tools like Swagger/OpenAPI to keep docs current." --priority "medium" --tags "ai-suggestion,documentation,api" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Create data migration scripts" --description "Develop robust database migration scripts with rollback capabilities for safe schema updates." --priority "medium" --tags "ai-suggestion,database,migrations" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Implement background job processing" --description "Add queue system for handling time-consuming tasks asynchronously to improve user experience." --priority "medium" --tags "ai-suggestion,queues,async" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add comprehensive input validation layer" --description "Create a centralized validation system that can be reused across different parts of the application." --priority "high" --tags "ai-suggestion,validation,architecture" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Implement application health monitoring" --description "Add health check endpoints and monitoring to track application status, database connectivity, and external service dependencies." --priority "medium" --tags "ai-suggestion,monitoring,health-checks" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Create automated backup system" --description "Implement automated database and file backups with retention policies and recovery testing." --priority "high" --tags "ai-suggestion,backup,disaster-recovery" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add performance profiling tools" --description "Integrate performance profiling tools to identify bottlenecks and optimize application performance." --priority "medium" --tags "ai-suggestion,performance,profiling" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Implement feature flag system" --description "Add feature flag functionality to enable gradual rollouts and quick rollbacks of new features." --priority "medium" --tags "ai-suggestion,feature-flags,deployment" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Create API rate limiting system" --description "Implement sophisticated rate limiting with different tiers and user-based limits to prevent abuse." --priority "high" --tags "ai-suggestion,security,rate-limiting" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add comprehensive audit logging" --description "Implement audit trails to track user actions, data changes, and system events for compliance and debugging." --priority "medium" --tags "ai-suggestion,audit,compliance" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Implement data export functionality" --description "Create flexible data export system supporting multiple formats (CSV, JSON, XML) with filtering and scheduling options." --priority "medium" --tags "ai-suggestion,export,data" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add search functionality with full-text indexing" --description "Implement advanced search capabilities with full-text indexing, faceted search, and relevance ranking." --priority "medium" --tags "ai-suggestion,search,indexing" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Create automated testing pipeline" --description "Set up CI/CD pipeline with automated testing, code quality checks, and deployment processes." --priority "high" --tags "ai-suggestion,ci-cd,automation" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Implement WebSocket support for real-time features" --description "Add WebSocket functionality to support real-time notifications and collaborative features." --priority "medium" --tags "ai-suggestion,websockets,real-time" --skip-setup

$CLI tasks create --project $PROJECT_ID --title "Add comprehensive security hardening" --description "Implement security best practices including HTTPS enforcement, security headers, input sanitization, and vulnerability scanning." --priority "high" --tags "ai-suggestion,security,hardening" --skip-setup

echo "✓ Successfully created 70 CodeRabbit tasks (50 nitpick + 20 AI suggestions)"
echo "All tasks have been added to project ID: $PROJECT_ID"